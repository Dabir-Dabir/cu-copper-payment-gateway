function requestPayment(token) {
    if (window.ethereum) {
        window.web3 = new Web3(ethereum);
        try {
            ethereum.enable();
        } catch (error) {
            console.log(error)
        }

    } else if (window.web3) {
        window.web3 = new Web3(web3.currentProvider);
    } else {
        alert("Please Install Metamask at First！")
        return;
    }

    var formData = new FormData();
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
            location.reload();
        }
    }
    var erc_contract = web3.eth.contract(abiArray);
    var erc_contract_instance = erc_contract.at(contract_address);
    // console.log('Target wallet:', target_address);
    // console.log('Contract address', contract_address);
    erc_contract_instance.transfer(target_address, token * 10e17, function (error, result) {
        // console.log('Transfer error:');
        // console.log(error);
        // console.log('Transfer result:');
        // console.log(result);
        if (error === null && result !== null) {
            console.log("Transaction complete", result);
            formData.append('orderid', order_id);
            formData.append('tx', result);
            xmlhttp.open("POST", "/hook/wc_erc20", true);
            xmlhttp.send(formData);
        }
    });
}

async function cupayRequestSignature(message) {
    if (window.ethereum) {
        window.web3 = new Web3(ethereum);
        try {
            ethereum.enable();
        } catch (error) {
            console.log(error)
        }

    } else if (window.web3) {
        window.web3 = new Web3(web3.currentProvider);
    } else {
        alert("Please Install Metamask at First！")
        return;
    }

    // let address = web3.eth.coinbase;
    // web3.personal.sign(web3.fromUtf8(message), address, console.log);

    // const accounts = await ethereum.request({ method: 'eth_requestAccounts' });
    // const account = accounts[0];
    // const signature = await ethereum.request({ method: 'personal_sign', params: [ message, account ] });
    // console.log(signature);

    let accounts = [];
    await getAccount();

    try {
        const from = accounts[0];
        const msg = `0x${web3.fromUtf8(message).toString('hex')}`;
        const sign = await ethereum.request({
            method: 'personal_sign',
            params: [msg, from, 'Example password'],
        });
        console.log(sign)
    } catch (err) {
        console.error(err);
    }

    async function getAccount() {
        accounts = await ethereum.request({ method: 'eth_requestAccounts' });
    }
}