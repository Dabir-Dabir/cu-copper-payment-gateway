<?php
defined( 'ABSPATH' ) || exit;
?>
<script>
    let cuAddresses = [];
	<?php
	$cu_addresses = get_user_meta( get_current_user_id(), 'cu_eth_addresses', true );
	$cu_no_addresses_txt = __( 'You didn\'t add any address', 'cu-copper-payment-gateway' );
	if ( is_array( $cu_addresses ) ) : ?>
    cuAddresses = <?= json_encode( $cu_addresses ) ?>;
	<?php endif; ?>

    let cuPayHasButton = false;
    const cuPayData = {
        "security": "<?= wp_create_nonce( "cu_security" ); ?>",
        "message": "<?= get_user_meta( get_current_user_id(), 'cu_eth_token', true ) ?>",
        "addresses": cuAddresses,
        "ajaxurl": "/wp-admin/admin-ajax.php",
        "displayMessages": {
            "install-metamask": "<?= __( 'Please Install MetaMask at First', 'cu-copper-payment-gateway' ) ?>",
            "no-addresses": "<?= $cu_no_addresses_txt ?>",
            "connect-metamask-btn": "<?= __( 'Connetc MetaMask', 'cu-copper-payment-gateway' ) ?>",
            "install-metamask-btn": "<?= __( 'Install MetaMask', 'cu-copper-payment-gateway' ) ?>",
            "bound-account-btn": "<?= __( 'Bound Account', 'cu-copper-payment-gateway' ) ?>",
            "pay-order-btn": "<?= __( 'Pay Order', 'cu-copper-payment-gateway' ) ?>"
        }
    }

	<?php if ($order_id) :
	$order = wc_get_order( $order_id );
	?>
    cuPayHasButton = true;
    cuPayData.amount = <?= (string) $order->get_total() ?>;
    cuPayData.orderId = <?= $order_id ?>;
    cuPayData.contractAddress = "<?= get_option( 'cu_copper_contract_address' ) ?>";
    cuPayData.abiArray = <?= get_option( 'cu_copper_abi_array', [] ) ?>;
    cuPayData.targetAddress = "<?= get_option( 'cu_copper_target_address' ) ?>";


    jQuery(window).load(() => {
        if (window.ethereum) {
            cupayShowCurrentAccount();
        }
        cupaySetButtonText();

        if (window.ethereum) {
            window.ethereum.on('accountsChanged', function (accounts) {
                cupayShowCurrentAccount();
                cupaySetButtonText();
            });
        }
    });
	<?php endif; ?>
</script>
<div class="cu-pay" id="cu-pay">

	<?php if ( $order_id ) : ?>
        <h5 class="cu-pay__current-provider">
            <span class="cu-pay__current-provider-title"><?= __( 'Current account', 'cu-copper-payment-gateway' ) ?>:</span>
            <span class="cu-pay__current-provider-account" id="cu-pay__current-provider-account">...</span>
        </h5>

        <h6 class="cu-connected-addresses__gas-notice"><?= get_option('cu_gas_notice') ?></h6>

        <button class="cu-pay__pay-button" id="cu-pay__pay-button"
                onclick="cupayPay(cuPayData)">
			<?= __( 'Connect MetaMask', 'cu-copper-payment-gateway' ) ?>
        </button>
	<?php endif; ?>

    <div class="cu-pay__logs" id="cu-pay__logs"></div>

    <div class="cu-connected-addresses" id="cu-connected-addresses">
        <h3 class="cu-connected-addresses__title"><?= __( 'Bonded addresses', 'cu-copper-payment-gateway' ) ?></h3>

		<?php if ( is_array( $cu_addresses ) && count( $cu_addresses ) > 0 ) : ?>
            <ul class="cu-connected-addresses__list">
				<?php foreach ( $cu_addresses as $address ) : ?>
                    <li class="cu-connected-addresses__list" id="cu-address-<?= $address ?>"
                        data-cu-address="<?= $address ?>">
                        <span class="cu-connected-addresses__span"><?= $address ?></span>
                        <button class="cu-connected-addresses__delete-button"
                                onclick="cupayRemoveAddress('<?= $address ?>',cuPayData)">X</button>
                    </li>
				<?php endforeach; ?>
            </ul>
		<?php else : ?>
            <div class="cu-connected-addresses__empty"><?= $cu_no_addresses_txt ?></div>
		<?php endif; ?>
    </div>

</div>