<script>
    var cuDataForSign = {
        "orderId": <?= $order_id ?>,
        "paymentAmount": <?= $payment_amount ?>,
        "contractAddress": "<?= get_option('cu_copper_contract_address') ?>",
        "targetAddress": "<?= get_option('cu_copper_target_address') ?>",
        "gasLimit": <?= get_option('cu_copper_gas_limit', 1000) ?>,
        "abiArray": <?= get_option('cu_copper_abi_array') ?>
    }
    console.log('cuDataForSign');
    console.log(cuDataForSign);
</script>
<h2 class="h2thanks"><?= __( 'Use Metamask to Sing this Order Transaction', 'cu-copper-payment-gateway' ) ?></h2>
<span><?= $this->gas_notice ?></span><br>
<div id="metamask-messages"></div>
<div>
    <button onclick="cupayRequestSignature(cuDataForSign)"><?= __( 'Sign the Transaction', 'cu-copper-payment-gateway' ) ?></button>
</div>