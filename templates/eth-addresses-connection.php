<script>
    var cuSecurity = "<?= wp_create_nonce( "cu_security" ); ?>";
    let message = "<?= get_user_meta( get_current_user_id(), 'cu_eth_token', true ) ?>";
</script>
<div id="cu-connected-addresses"></div>
<div>
    <button onclick="cupayRequestSignature(message)"><?= __( 'Connect Ethereum address', 'cu-copper-payment-gateway' ) ?></button>
</div>
<div id="cu-logs"></div>