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

add_action( 'init', 'cupay_test_verification' );
function cupay_test_verification() {

	if ( $_GET['cu'] !== 'test' ) {
		return;
	}

	$message   = 'Example `personal_sign` message';
	$signature = '0xf1bf495718845dce275c15708f0698400b900e392d0d158bd54597eb1feb987d44c5e0c5586e5b80f30ceadb192200b80390e0c6448e20f69c72112d5b0707f81b';
	$address   = '0xd95b8691d6e84a544229a8463d7ba8d1caf0042e';

	if ( verifySignature( $message, $signature, $address ) ) {
		cu_log( 'Is verified' );
	} else {
		cu_log( 'Not verified' );
	}
}
