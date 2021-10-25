<?php
defined( 'ABSPATH' ) || exit;

class Cupay_Payment {
	public string $erc20_method = "a9059cbb";
	public string $error;
	public string $tx;
	/**
	 * @var mixed
	 */
	public $block_hash = null;
	/**
	 * @var mixed
	 */
	public $transactions;
	/**
	 * @var mixed
	 */
	public $uncles;
	public int $transactions_minimum_count = 5;
	/**
	 * @var mixed
	 */
	public $order_timestamp;
	private bool $transaction_check_complete = false;

	public function send_infura_request( string $method, array $params = [] ) {
		$api_url = "https://" . get_option( 'cu_etherium_net' ) . ".infura.io/v3/" . get_option( 'cu_infura_api_id' );
		$ch      = curl_init( $api_url );
		$data    = array(
			'jsonrpc' => '2.0',
			'id'      => 1,
			'method'  => $method,
			'params'  => $params,
		);
		$payload = json_encode( $data );

		/* cUrl request */
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec( $ch );
		curl_close( $ch );

		$res = json_decode( $result, true );
		if ( ! is_array( $res ) ) {
			return false;
		}
		$data = $res['result'];
		if ( $res['jsonrpc'] !== "2.0" || $res['id'] !== 1 || ! is_array( $data ) || empty( $data ) ) {
			return false;
		}

		return $data;
	}

	public function get_data_from_transfer_input( $input ) {
		if ( ! is_string( $input ) || strlen( $input ) !== 138 || substr( $input, 2, 8 ) !== $this->erc20_method ) {
			return false;
		}
		$receiver = '0x' . substr( $input, 34, 40 );
		$amount   = hexdec( substr( $input, 74 ) ) / 1E+18;

		return [
			'receiver' => $receiver,
			'amount'   => $amount,
		];
	}

	public function validate_transaction( $data, $order_id ) {
		if ( strtolower( $data['symbol'] ) !== strtolower( get_option( 'cu_copper_contract_address' ) ) || strtolower( $data['receiver'] ) !== strtolower( get_option( 'cu_copper_target_address' ) ) ) {
			return false;
		}
		$order = wc_get_order( $order_id );
		if ( (float) $data['amount'] !== (float) $order->get_total() ) {
			return false;
		}
		$buyer_addresses = get_user_meta( get_current_user_id(), 'cu_eth_addresses', true );
		if ( ! in_array( $data['sender'], $buyer_addresses ) ) {
			return false;
		}

		return $order->get_date_created()->getOffsetTimestamp();
	}

	public function get_block_hash() {
		$loop = React\EventLoop\Loop::get();
		$loop->addTimer( 10, function() { } );

		$counter = 0;
		$timer   = $loop->addPeriodicTimer( 10, function() use ( &$counter, &$timer, $loop ) {
			if ( $counter > 4 ) {
				$loop->cancelTimer( $timer );
			}
			$counter ++;
			$transaction = $this->send_infura_request( 'eth_getTransactionByHash', [ $this->tx ] );
			cu_log( 'call eth_getTransactionByHash' );
			if ( $transaction['blockHash'] !== null ) {
				$this->block_hash = $transaction['blockHash'];
				$loop->cancelTimer( $timer );
			}

		} );

		$loop->run();
	}

	public function check_block() {
		$loop = React\EventLoop\Loop::get();

		$counter = 0;
		$timer   = $loop->addPeriodicTimer( 10, function() use ( &$counter, &$timer, $loop ) {
			if ( $counter > 10 ) {
				$loop->cancelTimer( $timer );
			}
			$counter ++;

			$block = $this->send_infura_request( 'eth_getBlockByHash', [ $this->block_hash, false ] );
			cu_log( 'call eth_getBlockByHash' );
			cu_log_export( $block, '$block' );
			if ( ! is_array( $block ) || hexdec( $block['timestamp'] ) < $this->order_timestamp ) {
				return false;
			}
			$this->transactions = $block['transactions'];
			$this->uncles       = $block['uncles'];
			if ( is_array( $this->transactions ) && is_array( $this->uncles ) && count( $this->transactions ) >= $this->transactions_minimum_count && count( $this->transactions ) > count( $this->uncles ) ) {
				$this->transaction_check_complete = true;
				$loop->cancelTimer( $timer );
			}

		} );

		$loop->run();
	}

	public function check_transaction( $tx, $order_id = 0, $data = [] ): bool {
		/* Validate tx */
		if ( strlen( $tx ) !== 66 || strpos( $tx, '0x' ) !== 0 ) {
			$this->error = __( 'Incorrect TX', 'cu-copper-payment-gateway' );

			return false;
		}

		/* Get transaction information */
		$transaction = $this->send_infura_request( 'eth_getTransactionByHash', [ $tx ] );
		if ( $transaction === false ) {
			return false;
		}
		$decoded_transfer_data = $this->get_data_from_transfer_input( $transaction['input'] );
		if ( $decoded_transfer_data === false ) {
			return false;
		}

		$transaction_data = [
			'receiver' => $decoded_transfer_data['receiver'],
			'amount'   => (float) $decoded_transfer_data['amount'],
			'symbol'   => $transaction['to'],
			'sender'   => $transaction['from'],
		];

		$this->order_timestamp = $this->validate_transaction( $transaction_data, $order_id );
		if ( $this->order_timestamp === false ) {
			return false;
		}

		$this->tx = $tx;
		if ( $this->block_hash === null ) {
			$this->get_block_hash();
		}

		if ( $this->block_hash === null ) {
			return false;
		}

		$this->check_block();

		return $this->transaction_check_complete;
	}
}