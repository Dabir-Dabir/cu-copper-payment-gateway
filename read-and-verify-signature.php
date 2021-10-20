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

	$message   = 'Example `personal_sign` message';
	$signature = '0x504af5495bfc76f61192b48aaaa76992c15102623aa3a625d12c17cc3f9659720cae1f7f26ab875800e746b25a4be84758fe4eaa8f5e5b0f8c040ffecb1722171c';
	$address   = '0x158bd234c1a42e926b7004e162199444e09ec40a';

	// $message   = 'Example `personal_sign` message4';
	// $signature = '0x7e6d24077ae9d94635b1420ac2fdda65ce1aa68b1673f8330dad04668fc1121430de4e3bac675d2c22e5f4816b29b2b54dcfbe2e2cd320befd937c039d09f7a11b';
	// $address   = '0x158bd234c1a42e926b7004e162199444e09ec40a';
	//
	// if ( verifySignature( $message, $signature, $address ) ) {
	// 	cu_log( 'Is verified' );
	// } else {
	// 	cu_log( 'Not verified' );
	// }

	$presha_str = hex2bin(substr(keccak256('string Messageuint32 A number'), 2) . substr(keccak256('Hi, Alice!'. pack('N', 1337)), 2));
	$hex = keccak256($presha_str);
	$signed = '0x5147f94643843d709bf7c374fb8d619b27da739413f7ab8de5c788a6b7d2d10e53c4789d8a0398dee6c9f6cb69e094fa801cc00fa4d19f3b71b03a7a4b7cfee11c';
	cu_log(ecRecover($hex, $signed), "\n");
}
