<script>
    var order_id = <?= $order_id ?>;
    var contract_address = "<?= (string) $this->contract_address ?>";
    var abiArray = <?= $this->abi_array ?>;
    var target_address = "<?= $this->target_address ?>";
</script>
<h2 class="h2thanks"><?= __( 'Use Metamask to Pay this Order', 'cu-copper-payment-gateway' ) ?></h2>
<?= __( 'Click Button Below, Pay this order.', 'cu-copper-payment-gateway' ) ?><br>
<span style="margin:5px 0px;"><?= $this->gas_notice ?></span><br>
<div>
    <button onclick="requestPayment(<?= (string) $order->get_total() ?>)"><?= __( 'Open Metamask', 'cu-copper-payment-gateway' ) ?></button>
</div>