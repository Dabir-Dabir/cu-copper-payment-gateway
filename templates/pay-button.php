<script>
    var cuSecurity = "<?= wp_create_nonce( "cu_security" ); ?>";
    var order_id = <?= $order_id ?>;
    var contract_address = "<?= (string) $this->contract_address ?>";
    var abiArray = <?= $this->abi_array ?>;
    var target_address = "<?= $this->target_address ?>";
    let message = "<?= get_user_meta( get_current_user_id(), 'cu_eth_token', true ) ?>";
</script>

<div>
    <button onclick="cupayRequestSignature(message, <?= get_current_user_id() ?>)"><?= __( 'Connect Ethereum address', 'cu-copper-payment-gateway' ) ?></button>
</div>

<h2 class="h2thanks"><?= __( 'Use Metamask to Pay this Order', 'cu-copper-payment-gateway' ) ?></h2>
<?= __( 'Click Button Below, Pay this order.', 'cu-copper-payment-gateway' ) ?><br>
<span><?= $this->gas_notice ?></span><br>
<div>
    <button onclick="cupayRequestPayment(<?= (string) $order->get_total() ?>)"><?= __( 'Open Metamask', 'cu-copper-payment-gateway' ) ?></button>
</div>