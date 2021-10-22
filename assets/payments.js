async function cupayRequestSignature({orderId, paymentAmount, gasLimit, contractAddress, targetAddress, abiArray}) {
    if (window.ethereum) {
        window.web3 = new Web3(ethereum);
        try {
            await ethereum.enable();
        } catch (error) {
            document.getElementById('metamask-messages').innerHtml = "Error occured when opening Metamask" + error
        }
    } else if (window.web3) {
        window.web3 = new Web3(web3.currentProvider);
    } else {
        document.getElementById('metamask-messages').innerHtml = 'Please download and install Metamask: <a href="https://metamask.io/">https://metamask.io/</a>'
    }

    const accounts = await ethereum.request({method: 'eth_requestAccounts'});
    console.log('accounts');
    console.log(accounts);
    const fromAddress = accounts[0];
    console.log('fromAddress');
    console.log(fromAddress);

    let amount = paymentAmount * 1E+18
    let input = '0xa9059cbb' + targetAddress.substring(2) + amount.toString(16);
    const tx = JSON.stringify({
        from: fromAddress,
        to: contractAddress,
        gas: gasLimit,
        value: '0x00',
        data: input
    });
    const data = JSON.stringify({
        nonce: '0x00',
        gasPrice: '0x09184e72a000',
        gasLimit: '0x2710',
        to: '0x0000000000000000000000000000000000000000',
        value: '0x00',
        data: '0x7f7465737432000000000000000000000000000000000000000000000000000000600057',
    });
    // web3.personal.sign(web3.toHex(data), fromAddress, function (err,signature) {
    //     if (err){
    //         console.error(err);
    //     }
    //     console.log('signature');
    //     console.log(signature);
    // });
    web3.eth.signTransaction({
        to: contractAddress,
        value: web3.toWei(5, 'ether')
    }, (err, transactionId) => {
        if  (err) {
            console.log('Payment failed', err)
            $('#status').html('Payment failed')
        } else {
            console.log('Payment successful', transactionId)
            $('#status').html('Payment successful')
        }
    })

    // try {
    //     const sign = await ethereum.request({
    //         method: 'personal_sign',
    //         params: [data, fromAddress],
    //     });
    //     console.log('sign');
    //     console.log(sign);
    // } catch (err) {
    //     console.error(err);
    // }
    // web3.eth.accounts.signTransaction(tx, privateKey).then(signed => {
    //     console.log(signed);
    // });
    // const transaction = new EthereumTx(tx);
    // transaction.sign(Buffer.from(pk, ‘hex’))
    // let rawdata = ‘0x’ + transaction.serialize().toString(‘hex’);

}