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
function cupay_pub_key_to_address( $pubkey ): string {
	return "0x" . substr( Keccak::hash( substr( hex2bin( $pubkey->encode( "hex" ) ), 1 ), 256 ), 24 );
}

function cupay_verify_signature( $message, $signature, $address ): bool {
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
		return $address == cupay_pub_key_to_address( $pubkey );
	} catch ( Exception $e ) {
		return false;
	}
}

add_action( 'wp_ajax_cu_add_eth_address_to_account', 'cupay_add_eth_address_to_account' );
function cupay_add_eth_address_to_account() {
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
	if ( ! cupay_verify_signature( $message, $sign, $sender_address ) ) {
		$response = [
			"action" => 'cu_add_eth_address_to_account',
			"done"   => false,
			"error"  => __( 'Incorrect signature!', 'cu-copper-payment-gateway' ),
		];

		echo json_encode( $response );
		die;
	}

	$user_addresses = get_user_meta( $user_id, 'cu_eth_addresses', true );
	if ( ! is_array( $user_addresses ) ) {
		$user_addresses = [];
	}
	if ( in_array( $sender_address, $user_addresses ) ) {
		$response = [
			"action" => 'cu_add_eth_address_to_account',
			"done"   => false,
			"error"  => __( 'Already added!', 'cu-copper-payment-gateway' ),
		];

		echo json_encode( $response );
		die;
	}
	$user_addresses[] = $sender_address;
	$user_addresses_updated = update_user_meta( $user_id, 'cu_eth_addresses', $user_addresses );
	if ( ! $user_addresses_updated ) {
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

// add_action('init', function() {
// 	update_user_meta( 2, 'cu_eth_addresses', '' );
// });

add_action( 'wp_ajax_cu_remove_eth_address_from_account', 'cupay_cu_remove_eth_address_from_account' );
function cupay_cu_remove_eth_address_from_account() {
	if ( check_ajax_referer( 'cu_security', 'security' ) !== 1 ) {
		$response = [
			"action" => 'cu_remove_eth_address_from_account',
			"done"   => false,
			"error"  => __( 'Weak security!', 'cu-copper-payment-gateway' ),
		];

		echo json_encode( $response );
		die;
	}

	$user_id = get_current_user_id();
	$address = sanitize_text_field( $_POST['address'] );

	$user_addresses = get_user_meta( $user_id, 'cu_eth_addresses', true );
	if ( ! is_array( $user_addresses ) || ! in_array( $address, $user_addresses ) ) {
		$response = [
			"action" => 'cu_remove_eth_address_from_account',
			"done"   => false,
			"error"  => __( 'Didn\'t exist!', 'cu-copper-payment-gateway' ),
		];

		echo json_encode( $response );
		die;
	}
	unset( $user_addresses[ array_search('$address', $user_addresses) ] );
	$user_addresses_updated = update_user_meta( $user_id, 'cu_eth_addresses', $user_addresses );
	if ( ! $user_addresses_updated ) {
		$response = [
			"action" => 'cu_remove_eth_address_from_account',
			"done"   => false,
			"error"  => __( 'Internal error!', 'cu-copper-payment-gateway' ),
		];

		echo json_encode( $response );
		die;
	}

	$response = [
		"action"  => 'cu_remove_eth_address_from_account',
		"done"    => true,
		"success" => __( 'Account removed!', 'cu-copper-payment-gateway' ),
		"account" => $address
	];

	echo json_encode( $response );
	die;
}