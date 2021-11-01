<?php
function cu_uninstall() {
	/**
	 * Delete Payment options
	 * */
	$options = [
		'cu_copper_target_address',
		'cu_copper_contract_address',
		'cu_copper_abi_array',
		'cu_ethereum_net',
		'cu_infura_api_id',
		'cu_infura_api_secret',
		'cu_infura_api_url',
	];

	foreach ( $options as $option_name ) {
		delete_option( $option_name );
	}

	/**
	 * Delete User Metas
	 * */
	$args = array(
		'meta_key' => 'cu_eth_addresses',
		'fields'   => 'ids'
	);

	$user_ids = get_users( $args );
	foreach ( $user_ids as $user ) {
		$user_id = (int) $user;
		delete_user_meta( $user_id, 'cu_eth_addresses' );
	}

	/**
	 * Delete Order metadata
	 * */
	$query = new WC_Order_Query( [
		'limit'    => - 1,
		'return'   => 'ids',
		'meta_key' => 'cu_tx'
	] );
	try {
		$order_ids = $query->get_orders();
		foreach ( $order_ids as $id ) {
			delete_post_meta( 73, 'cu_tx' );
		}
	} catch ( Exception $e ) {
	}
}