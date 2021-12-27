<?php
/*
Plugin Name: peg63.546u Copper Payment Gateway
Version: 0.0.1
Description: This Plugin will add ERC20 peg63.546u Copper (CU) Token Payment Gateway to your store.
Plugin URI: https://wordpress.org/plugins/peg63-546u-copper-payment-gateway
WC requires at least: 5.5.1
WC tested up to: 5.6.0
Requires at least: 5.5
Author: firstRateiT
Author URI: https://firstrateit.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: cu-copper-payment-gateway
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
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notices' ], 15 );
			return;
		}

		$this->includes();
		$this->set_hooks();

		add_shortcode( 'cupay_connect_addresses', [ $this, 'connect_addresses_shortcode' ] );
	}

	public function admin_notices() {
		echo '<div class="error"><h4>' . __( '<b>peg63.546u Copper Payment</b>: You need to <strong>install and activate</strong> <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>', 'cu-copper-payment-gateway' ) . '</h4></div>';
	}

	public function includes() {
		include 'vendor/autoload.php';
		include 'logs.php';
		include 'classes/class-cupay-payment.php';
		include 'ajax.php';
		include 'uninstall.php';
	}

	public function set_hooks() {
		add_action( 'admin_init', [ $this, 'add_privacy_suggestion_text' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_settings_link' ] );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateway_class' ] );
		add_action( 'plugins_loaded', [ $this, 'init_gateway_class' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_payment_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_payment_scripts' ], 20 );
		add_action( 'woocommerce_available_payment_gateways', [ $this, 'disable_gateway_for_unlogged_users' ] );
	}

	function disable_gateway_for_unlogged_users( $args ) {
		if ( ! is_user_logged_in() && isset( $args['cupay_erc20'] ) ) {
			unset( $args['cupay_erc20'] );
		}

		return $args;
	}

	/**
	 * Load JavaScript for payment at the front desk
	 */
	public function register_payment_scripts(): void {
		wp_register_style( 'cupay_style', CU_URL . '/assets/css/cupay.css' );

		wp_register_script( 'cupay_web3', CU_URL . '/assets/js/web3.min.js', array( 'jquery' ), 1.1, true );
		wp_register_script( 'cupay_payment', CU_URL . '/assets/js/payment.js', array(
			'jquery',
			'cupay_web3'
		), 1.0, true );
	}

	public function enqueue_payment_scripts(): void {
		if ( is_edit_account_page() ) {
			wp_enqueue_style( 'cupay_style' );
			wp_enqueue_script( 'cupay_payment' );
		}
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
	public function init_gateway_class() {
		include 'classes/class-cupay-wc-copper-gateway.php';
	}

	public function add_gateway_class( $gateways ) {
		$gateways[] = 'Cupay_WC_Copper_Gateway';

		return $gateways;
	}

	public function connect_addresses_shortcode( $atts ) {
		$order_id = false;
		if ( isset( $atts['order-id'] ) ) {
			$order_id = (int) $atts['order-id'];
		}
		include CU_ABSPATH . '/templates/pay-order.php';
	}

	public function add_privacy_suggestion_text() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		ob_start(); ?>
        <h2><?= __( 'What data we save?', 'cu-copper-payment-gateway' ) ?></h2>
        <p><?= __( 'If you bound the Ethereum address(es) to your account we will save it (them) until you remove it (them).', 'cu-copper-payment-gateway' ) ?></p>
        <h2><?= __( 'Why do we save your Ethereum Address(es)?', 'cu-copper-payment-gateway' ) ?></h2>
        <p><?= __( 'It\'s (they\'re) used to identify the payer of the order.', 'cu-copper-payment-gateway' ) ?></p>
        <p class="privacy-policy-tutorial"><strong>
				<?= __( 'Do not delete your Ethereum Account until all payments are complete, or you may lose your payment!', 'cu-copper-payment-gateway' ) ?>
            </strong></p>
		<?php
		$content = ob_get_clean();

		wp_add_privacy_policy_content( 'WooCommerce peg63.546u Copper Payment Gateway', $content );
	}
}

new CuCopperPaymentGateway();