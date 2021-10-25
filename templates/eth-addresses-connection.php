<script>
	<?php
	$cu_addresses = get_user_meta( get_current_user_id(), 'cu_eth_addresses', true );
	$cu_no_addresses_txt = __( 'You didn\'t add any address', 'cu-copper-payment-gateway' );
	if ( is_array( $cu_addresses ) ) : ?>
    let cuAddresses = <?= json_encode( $cu_addresses ) ?>;
	<?php else : ?>
    let cuAddresses = [];
	<?php endif; ?>

    const cuSecurity = "<?= wp_create_nonce( "cu_security" ); ?>";
    const cuMessage = "<?= get_user_meta( get_current_user_id(), 'cu_eth_token', true ) ?>";
    const cuDisplayMessages = {
        "install-metamask": "<?= __( 'Please Install Metamask at First', 'cu-copper-payment-gateway' ) ?>",
        "no-addresses": "<?= $cu_no_addresses_txt ?>"
    }

</script>
<div id="cu-connected-addresses" class="cu-connected-addresses">
    <h3 class="cu-connected-addresses__title"><?= __( 'Bonded addresses', 'cu-copper-payment-gateway' ) ?></h3>

	<?php if ( is_array( $cu_addresses ) && count( $cu_addresses ) > 0 ) : ?>
        <ul class="cu-connected-addresses__list">
			<?php foreach ( $cu_addresses as $address ) : ?>
                <li class="cu-connected-addresses__list" id="cu-address-<?= $address ?>"
                    data-cu-address="<?= $address ?>">
                    <span class="cu-connected-addresses__span"><?= $address ?></span>
                    <button class="cu-connected-addresses__delete-button"
                            onclick="cupayRemoveAddress('<?= $address ?>', cuDisplayMessages)">X
                    </button>
                </li>
			<?php endforeach; ?>
        </ul>
	<?php else : ?>
        <div class="cu-connected-addresses__empty"><?= $cu_no_addresses_txt ?></div>
	<?php endif; ?>

<!--    <h5 class="cu-connected-addresses__account-title">--><?//= __('Current account', 'cu-copper-payment-gateway') ?><!--</h5>-->
<!--    <div class="cu-connected-addresses__account" id="cu-current-provider"></div>-->

</div>
<button class="cu-connected-addresses__connect-button"
        onclick="cupayRequestSignature(cuMessage, cuDisplayMessages)">
	<?= __( 'Connect Ethereum address', 'cu-copper-payment-gateway' ) ?>
</button>

<div class="cu-connected-addresses__logs" id="cu-logs"></div>