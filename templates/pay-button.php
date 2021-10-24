<script>
    var cuOrderId = <?= $order_id ?>;
    var cuContractAddress = "<?= (string) $this->contract_address ?>";
    var cuAbiArray = <?= $this->abi_array ?>;
    var cuTargetAddress = "<?= $this->target_address ?>";
</script>

<span><?= $this->gas_notice ?></span><br>
<div>
    <button onclick="cupayRequestPayment(<?= (string) $order->get_total() ?>, cuDisplayMessages)"><?= __( 'Open Metamask', 'cu-copper-payment-gateway' ) ?></button>
</div>