<?php
/*
 * Plugin Name: WooCommerce Cu (Copper) Payment Gateway
 * Version: 0.0.1
 * Description: This Plugin will add Cu (Copper) ERC20 Token Payment Gateway
 * Author: Lado Mikiashvili.
 * Requires at least: 5.5
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CU_ABSPATH' ) ) {
	define( 'CU_ABSPATH', __DIR__ );
}
if ( ! defined( 'CU_URL' ) ) {
	define( "CU_URL", plugins_url( '', __FILE__ ) );
}

class CuCopperPaymentGateway {

	public function __construct() {
		$this->includes();
		$this->set_hooks();
	}

	public function includes() {
		include 'vendor/autoload.php';
		include 'logs.php';
		include 'read-and-verify-signature.php';
		include 'classes/class-cupay-payment.php';
	}

	public function set_hooks() {
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_settings_link' ] );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateway_class' ] );
		add_action( 'init', [ $this, 'thankyou_request' ] );
		add_action( 'plugins_loaded', [ $this, 'init_gateway_class' ] );

		add_shortcode( 'cupay_connect_addresses', [ $this, 'connect_addresses_shortcode' ] );
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
		include 'classes/class-cupay-wc-copper-gateway.php';
	}

	/**
	 * Monitor the payment completion request of the plug-in
	 */
	public function thankyou_request() {
		if ( isset( $_GET['cu'] ) && $_GET['cu'] === 'cu' ) {
			( new Cupay_Payment )->check_transaction( '0xfdb734ba4383d7fd801d6083815aa29cb08f9adcb291abfbd21f05f283cc6bc2', 44 );
		}
		/**
		 * Determine whether the user request is a specific path.
		 * If the path is modified here, it should be modified in payments.js too.
		 */
		if ( $_SERVER["REQUEST_URI"] === '/hook/wc_erc20' ) {
			$data     = $_POST;
			$order_id = $data['orderid'];
			$tx       = $data['tx'];

			$is_payed = ( new Cupay_Payment )->check_transaction( $tx, $order_id, $data );
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

	public function connect_addresses_shortcode( $atts ) {
		$order_id = false;
		if ( isset($atts['order-id']) ) {
			$order_id = (int) $atts['order-id'];
		}
		include CU_ABSPATH . '/templates/pay-order.php';
	}
}

new CuCopperPaymentGateway();