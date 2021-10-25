function cupayRequestPayment(amount, displayMessages) {
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
        let logsElement = document.getElementById('cu-logs');
        logsElement.innerHTML = displayMessages['install-metamask'];
        logsElement.classList.add('has_error');
        return;
    }
    
    var erc_contract = web3.eth.contract(cuAbiArray);
    var erc_contract_instance = erc_contract.at(cuContractAddress);
    erc_contract_instance.transfer(cuTargetAddress, amount * 1E18, function (error, result) {
        if (error === null && result !== null) {
            const data = {
                'action': 'cu_check_transaction',
                'order_id': cuOrderId,
                'tx': result,
                'security': cuSecurity
            };
            console.log(result);
            let ajaxurl = "/wp-admin/admin-ajax.php";
            jQuery.post(ajaxurl, data, function (response) {
                console.log('Response');
                console.log(response);
            });
        }
    });
}

function cupaySetButtonText() {
    let button = document.getElementById('');
    if (window.ethereum) {
        button.innerHTML = 'Connetc Metamask';
        return;
    } else if (!window.web3) {
        button.innerHTML = 'Install Metamask';
        return;
    }

    button.innerHTML = 'Bound account';
}

jQuery(window).load(() => {
    // cupaySetButtonText();
});