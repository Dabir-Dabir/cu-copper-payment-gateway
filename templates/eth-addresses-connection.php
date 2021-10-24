<script>
	<?php
	$addresses = get_user_meta( get_current_user_id(), 'cu_eth_addresses' );
	if ( is_array( $addresses ) ) : ?>
        let cuAddresses = <?= json_encode($addresses) ?>;
	<?php else : ?>
        let cuAddresses = [];
	<?php endif; ?>

    const cuSecurity = "<?= wp_create_nonce( "cu_security" ); ?>";
    const cuMessage = "<?= get_user_meta( get_current_user_id(), 'cu_eth_token', true ) ?>";
    const cuDisplayMessages = {
        "install-metamask": <?= __( 'Please Install Metamask at First', 'cu-copper-payment-gateway' ) ?>,
        "no-addresses": <?= __( 'You didn\'t add any address', 'cu-copper-payment-gateway' ) ?>
    }

</script>
<div id="cu-connected-addresses" class="cu-connected-addresses">
    <ul class="cu-connected-addresses__list">
		<?php if ( is_array( $addresses ) ) :
			foreach ( $addresses as $address ) : ?>
                <li class="cu-connected-addresses__list">
                    <span class="cu-connected-addresses__span"><?php $address ?></span>
                    <button class="cu-connected-addresses__delete-button">X</button>
                </li>
			<?php endforeach;
		endif; ?>
    </ul>
</div>
<div>
    <button onclick="cupayRequestSignature(cuMessage, cuDisplayMessages)"><?= __( 'Connect Ethereum address', 'cu-copper-payment-gateway' ) ?></button>
</div>
<div id="cu-logs"></div>