<?php
/*
 * Plugin Name: WooCommerce Cu (Copper) Payment Gateway
 * Version: 0.0.1
 * Description: This Plugin will add Cu (Copper) ERC20 Token Payment Gateway
 * Author: Lado Mikiashvili.
 * Requires at least: 5.5
 */

defined( 'ABSPATH' ) || exit;

class CuCopperPaymentGateway {

	public function __construct() {
		$this->includes();
		$this->set_hooks();
	}

	public function includes() {
		include 'logs.php';
	}

	public function set_hooks() {
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_settings_link' ] );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateway_class' ] );
		add_action( 'init', [ $this, 'thankyou_request' ] );
		add_action( 'plugins_loaded', [ $this, 'init_gateway_class' ] );

	}

	/**
	 * Add plugin name setting
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=wc-settings&tab=checkout">' . __( 'Settings', 'cu-copper-payment-gateway' ) . '</a>';
		$links[]       = $settings_link;

		return $links;
	}

	/**
	 * Add a new Gateway
	 */
	public function add_gateway_class( $gateways ) {
		$gateways[] = 'Cupay_WC_Copper_Gateway';

		return $gateways;
	}

	public function init_gateway_class() {
		include 'class-wc-copper-gateway.php';
	}

	public function send_infura_request( string $method, array $params = [] ): array {
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
			return array();
		}
		$data = $res['result'];
		if ( $res['jsonrpc'] !== "2.0" || $res['id'] !== 1 || ! is_array( $data ) || empty( $data ) ) {
			return array();
		}

		return $data;
	}

	public function get_transfer_data( $input ) {
		if ( ! is_string( $input ) || strlen( $input ) !== 138 || substr( $input, 2, 8 ) !== "a9059cbb" ) {
			return false;
		}
		$receiver = '0x' . substr( $input, 34, 40 );
		$amount   = hexdec( substr( $input, 74 ) ) / 1E+18;

		return [
			'receiver' => $receiver,
			'amount'   => $amount,
		];
	}

	public function validate_transaction( $tx, $res ): bool {
		if ( $tx !== $res['hash'] || get_option( 'cu_copper_contract_address' ) !== $res['to'] ) {
			return false;
		}

		$target_address = '';
		if ( get_option( 'cu_copper_target_address' ) !== $target_address ) {
			return false;
		}

		cu_log_dump( $tx, 'Validate transaction' );
		cu_log_dump( $res );

		return true;
	}

	public function check_transaction( $tx, $order_id = 0, $data = [] ): bool {
		/* Validate tx */
		if ( strlen( $tx ) !== 66 || strpos( $tx, '0x' ) !== 0 ) {
			return false;
		}

		/* Get transaction information */
		$transaction = $this->send_infura_request( 'eth_getTransactionByHash', [ $tx ] );
		cu_log_dump( $transaction, '$transaction' );
		$decoded_transfer_data = $this->get_transfer_data( $transaction['input'] );
		$receiver              = $decoded_transfer_data['receiver'];
		$amount                = $decoded_transfer_data['amount'];
		$symbol                = $transaction['to'];
		$sender                = $transaction['from'];

		cu_log_dump( [
			'receiver' => $receiver,
			'amount'   => $amount,
			'symbol'   => $symbol,
			'sender'   => $sender,
		], 'output' );

		$block = $this->send_infura_request( 'eth_getBlockByHash', [ $tx, false ] );

		return true;
	}

	/**
	 * Monitor the payment completion request of the plug-in
	 */
	public function thankyou_request() {
		if ( $_GET['cu'] === 'cu' ) {
			$this->check_transaction( '0x33c941d6504485687ecfaa5c431c4bcb2aeba583bb2f080260727537e817592d' );
		}
		/**
		 * Determine whether the user request is a specific path.
		 * If the path is modified here, it should be modified in payments.js too.
		 */
		if ( $_SERVER["REQUEST_URI"] === '/hook/wc_erc20' ) {
			$data     = $_POST;
			$order_id = $data['orderid'];
			$tx       = $data['tx'];

			$is_payed = $this->check_transaction( $tx, $order_id, $data );
			if ( ! $is_payed ) {
				return;
			}

			/**
			 * Get the order
			 */
			$order = wc_get_order( $order_id );

			/**
			 * Mark order payment completed
			 */
			$order->payment_complete();
			/**
			 * Add order remarks and indicate 'tx' viewing address
			 */
			$order->add_order_note( __( "Order payment completed", 'cu-copper-payment-gateway' ) . "Tx:<a target='_blank' href='http://etherscan.io/tx/" . $tx . "'>" . $tx . "</a>" );
			/**
			 * Need to exit, otherwise the page content will be displayed.
			 * It displays blank when it exits, and prints a section of JSON on the interface.
			 */
			exit();
		}

	}
}

new CuCopperPaymentGateway();