<?php
require_once "ecrecover-helper.php";

add_action( 'init', 'cupay_test_verification' );
function cupay_test_verification() {

	if ( isset($_GET['cu']) && $_GET['cu'] !== 'test' ) {
		return;
	}

	$presha_str = hex2bin( substr( keccak256( 'string Messageuint32 A number' ), 2 ) . substr( keccak256( 'Hi, Alice!' . pack( 'N', 1337 ) ), 2 ) );
	$signed     = '0x5147f94643843d709bf7c374fb8d619b27da739413f7ab8de5c788a6b7d2d10e53c4789d8a0398dee6c9f6cb69e094fa801cc00fa4d19f3b71b03a7a4b7cfee11c';
	$address    = "0x61eb5b07a56799499f904c247022e580663b0e13";
	cu_log_dump( cupay_verify_transaction_signature( $presha_str, $signed, $address ), "Verification" );

}

function cupay_verify_transaction_signature( $message, $signature, $address ): bool {
	try {
		return $address === ecRecover( keccak256( $message ), $signature );
	} catch ( Exception $e ) {
		return false;
	}
}