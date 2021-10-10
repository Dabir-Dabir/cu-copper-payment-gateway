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
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'cupay_erc20_add_settings_link' ] );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'cupay_erc20_add_gateway_class' ] );
		add_action( 'init', [ $this, 'cupay_thankyou_request' ] );
		add_action( 'plugins_loaded', [ $this, 'init_erc20_gateway_class' ] );

	}

	/**
	 * Add plugin name setting
	 */
	public function cupay_erc20_add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=wc-settings&tab=checkout">' . __( 'Settings', 'cu-copper-payment-gateway' ) . '</a>';
		$links[]       = $settings_link;

		return $links;
	}

	/**
	 * Add a new Gateway
	 */
	public function cupay_erc20_add_gateway_class( $gateways ) {
		$gateways[] = 'Cupay_WC_Copper_Gateway';

		return $gateways;
	}

	public function init_erc20_gateway_class( ) {
		include 'class-wc-copper-gateway.php';
	}

	public function cupay_get_transaction( $tx ): array {
		$api_url = "https://" . get_option( 'cu_etherium_net' ) . ".infura.io/v3/" . get_option( 'cu_infura_api_id' );
		$ch      = curl_init( $api_url );
		$data    = array(
			'jsonrpc' => '2.0',
			'id'      => 1,
			'method'  => 'eth_getTransactionByHash',
			'params'  => [ $tx ],
		);
		$payload = json_encode( $data );

		/* cUrl request */
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec( $ch );
		curl_close( $ch );

		$res = json_decode( $result, true );
		var_dump( $res, 'infura.io $res' );
		if ( ! is_array( $res ) ) {
			return array();
		}
		$data = $res['result'];
		if ( $res['jsonrpc'] !== "2.0" || $res['id'] !== 1 || ! is_array( $data ) || empty( $data ) ) {
			return array();
		}

		return $data;
	}

	public function cupay_validate_transaction( $tx, $res ): bool {
		if ( $tx !== $res['hash'] || get_option( 'cu_copper_contract_address' ) !== $res['to'] ) {
			return false;
		}

		$target_address = '';
		if ( get_option( 'cu_copper_target_address' ) !== $target_address ) {
			return false;
		}

		return true;
	}

	/**
	 * Monitor the payment completion request of the plug-in
	 */
	public function cupay_thankyou_request() {
		if ( $_GET['cu'] === 'cu' ) {
			$this->cupay_get_transaction( '0xd3c83cc78f06588ea535b164621cc26a38ceeb7d431809f45012835429d64ea4' );
		}
		/**
		 * Determine whether the user request is a specific path.
		 * If the path is modified here, it should be modified in payments.js too.
		 */
		if ( $_POST["REQUEST_URI"] === '/hook/wc_erc20' ) {
			$data     = $_POST;
			$order_id = $data['orderid'];
			$tx       = $data['tx'];

			var_dump( $data, 'post $data' );
			if ( strlen( $tx ) !== 66 || strpos( $tx, '0x' ) !== 0 ) {
				return;
			}

			$res = $this->cupay_get_transaction( $tx );
			if ( ! $this->cupay_validate_transaction( $tx, $res ) ) {
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