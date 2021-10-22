<?php
defined( 'ABSPATH' ) || exit;

class Cupay_Payment {
	public string $erc20_method = "a9059cbb";
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

	public function get_data_for_transfer_input( $amount ):string {
		$amount = $amount * 1E+18;
		$amount_hash = dechex($amount);
		return '0x' . $this->erc20_method . get_option('') . $amount_hash;
	}


	public function validate_transaction( $data, $order_id ): bool {
		if ( strtolower( $data['symbol'] ) !== strtolower( get_option( 'cu_copper_contract_address' ) ) || strtolower( $data['receiver'] ) !== strtolower( get_option( 'cu_copper_target_address' ) ) ) {
			return false;
		}
		$order = wc_get_order( $order_id );
		if ( (float) $data['amount'] !== (float) $order->get_total() ) {
			return false;
		}

		// $buyer = $order->get_user()->user_email;
		// if ( $data['sender'] !== $buyer) {
		// 	return false;
		// }
		cu_log( 'p 2' );

		return true;
	}

	public function check_transaction( $tx, $order_id = 0, $data = [] ): bool {
		/* Validate tx */
		if ( strlen( $tx ) !== 66 || strpos( $tx, '0x' ) !== 0 ) {
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

		$order_timestamp = $this->validate_transaction( $transaction_data, $order_id );

		cu_log_export( $order_timestamp, '$order_timestamp' );

		$block = $this->send_infura_request( 'eth_getBlockByHash', [ $tx, false ] );

		return true;
	}
}