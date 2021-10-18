<?php
defined( 'ABSPATH' ) || exit;

class Cupay_Registration {
	public function __construct() {
		$this->set_hooks();
	}

	public function set_hooks() {
		add_action( 'woocommerce_register_post', [ $this, 'validate_fields' ], 10, 3 );
	}

	public function validate_fields( $username, $email, $errors ): void {
		if(strlen())
		if ( sanitize_email( $recovery_email ) === sanitize_email( $email ) ) {
			$errors->add( 'recovery_email_error', esc_html__( 'The recovery email and general emails should be different!', 'fri-core' ) );
		}
	}

}