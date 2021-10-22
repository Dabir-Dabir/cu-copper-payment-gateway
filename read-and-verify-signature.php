<?php
require_once "lib/Keccak/Keccak.php";
require_once "lib/Elliptic/EC.php";
require_once "lib/Elliptic/Curves.php";

use Elliptic\EC;
use kornrunner\Keccak;

// Check if the message was signed with the same private key to which the public address belongs
/**
 * @throws Exception
 */
function pubKeyToAddress( $pubkey ): string {
	return "0x" . substr( Keccak::hash( substr( hex2bin( $pubkey->encode( "hex" ) ), 1 ), 256 ), 24 );
}

function verifySignature( $message, $signature, $address ): bool {
	$msglen = strlen( $message );
	try {
		$hash = Keccak::hash( "\x19Ethereum Signed Message:\n{$msglen}{$message}", 256 );
	} catch ( Exception $e ) {
		return false;
	}
	$sign  = [
		"r" => substr( $signature, 2, 64 ),
		"s" => substr( $signature, 66, 64 )
	];
	$recid = ord( hex2bin( substr( $signature, 130, 2 ) ) ) - 27;
	if ( $recid != ( $recid & 1 ) ) {
		return false;
	}

	$ec = new EC( 'secp256k1' );
	try {
		$pubkey = $ec->recoverPubKey( $hash, $sign, $recid );
	} catch ( Exception $e ) {
		return false;
	}

	try {
		return $address == pubKeyToAddress( $pubkey );
	} catch ( Exception $e ) {
		return false;
	}
}

add_action( 'wp_ajax_cu_add_eth_address_to_account', 'cu_add_eth_address_to_account' );
function cu_add_eth_address_to_account() {
	if ( check_ajax_referer( 'cu_security', 'security' ) !== 1 ) {
		$response = [
			"action" => 'cu_add_eth_address_to_account',
			"done"   => false,
			"error"  => __( 'Weak security!', 'cu-copper-payment-gateway' ),
		];

		echo json_encode( $response );
		die;
	}

	$user_id        = get_current_user_id();
	$message        = get_user_meta( $user_id, 'cu_eth_token', true );
	$sign           = sanitize_text_field( $_POST['sign'] );
	$sender_address = sanitize_text_field( $_POST['sender'] );
	if ( ! verifySignature( $message, $sign, $sender_address ) ) {
		$response = [
			"action" => 'cu_add_eth_address_to_account',
			"done"   => false,
			"error"  => __( 'Incorrect signature!', 'cu-copper-payment-gateway' ),
		];

		echo json_encode( $response );
		die;
	}

	$user_addresses = get_user_meta( $user_id, 'cu_eth_addresses' );
	if ( ! is_array( $user_addresses ) ) {
		$user_addresses = [];
	}
	$user_addresses[] = $sender_address;
	$user_addresses_updated = update_user_meta( $user_id, 'cu_eth_addresses', $user_addresses );
	if(!$user_addresses_updated) {
		$response = [
			"action" => 'cu_add_eth_address_to_account',
			"done"   => false,
			"error"  => __( 'Internal error!', 'cu-copper-payment-gateway' ),
		];

		echo json_encode( $response );
		die;
	}

	$response = [
		"action"  => 'cu_add_eth_address_to_account',
		"done"    => true,
		"success" => __( 'Account added!', 'cu-copper-payment-gateway' ),
		"account" => $sender_address
	];

	echo json_encode( $response );
	die;
}