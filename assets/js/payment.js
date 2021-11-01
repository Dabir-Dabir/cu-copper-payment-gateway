/**
 * Bond Ethereum address to account
 * */
async function cupayRequestSignature(payData) {
    let {message, displayMessages, security, ajaxurl} = payData;
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
        let logsElement = document.getElementById('cu-pay__logs');
        logsElement.innerHTML = displayMessages['install-metamask'];
        logsElement.classList.add('has_error');
        return;
    }

    let accounts = await ethereum.request({method: 'eth_requestAccounts'});
    let from;
    let sign;
    try {
        from = accounts[0];
        sign = await ethereum.request({
            method: 'personal_sign',
            params: [message, from],
        });
    } catch (err) {
        console.error(err);
        return;
    }

    const data = {
        'action': 'cu_add_eth_address_to_account',
        'sign': sign,
        'sender': from,
        'security': security
    };

    jQuery.post(ajaxurl, data, function (response) {
        cupayHandleRequestSignatureResponse(JSON.parse(response), payData);
    });
}

function cupayHandleRequestSignatureResponse(data, payData) {
    let logsElement = document.getElementById('cu-pay__logs');
    let connectedAddresses = document.getElementById('cu-connected-addresses');
    let connectedAddressesList = document.getElementsByClassName('cu-connected-addresses__list')[0];

    if (data['action'] !== 'cu_add_eth_address_to_account') {
        return;
    }

    if (!data['done']) {
        logsElement.classList.add('has_error');
        logsElement.innerHTML = data['error'];
        return;
    }

    if (logsElement.classList.contains('has_error')) {
        logsElement.classList.remove('has_error');
    }

    logsElement.innerHTML = data['success'];

    let listItem = document.createElement('li');
    listItem.classList.add('cu-connected-addresses__list-item');
    listItem.dataset.cuAddress = data['account'];
    listItem.id = 'cu-address-' + data['account'];

    let span = document.createElement('span')
    span.classList.add('cu-connected-addresses__span');
    span.innerHTML = data['account'];
    listItem.append(span);

    let deleteButton = document.createElement('button')
    deleteButton.classList.add('cu-connected-addresses__delete-button');
    deleteButton.onclick = function () {
        cupayRemoveAddress(data['account'], payData);
    }
    deleteButton.innerHTML = 'X';
    listItem.append(deleteButton);

    if (cuAddresses.length > 0) {
        connectedAddressesList.append(listItem);
    } else {
        const node = document.getElementsByClassName('cu-connected-addresses__empty')[0];
        node.parentNode.removeChild(node);

        let list = document.createElement('ul')
        list.classList.add('cu-connected-addresses__list');

        list.append(listItem);
        connectedAddresses.append(list);
    }

    cuAddresses.push(data['account']);
    cupaySetButtonText();
}

/**
 * Remove Ethereum address from account
 * */
async function cupayRemoveAddress(address, {displayMessages, security, ajaxurl}) {
    const data = {
        'action': 'cu_remove_eth_address_from_account',
        'address': address,
        'security': security
    };

    jQuery.post(ajaxurl, data, function (response) {
        cupayHandleRemoveAddressResponse(JSON.parse(response), displayMessages);
    });
}

function cupayHandleRemoveAddressResponse(data, displayMessages) {
    let connectedAddressesElement = document.getElementById('cu-connected-addresses');
    let logsElement = document.getElementById('cu-pay__logs');
    let connectedAddressesList = document.getElementsByClassName('cu-connected-addresses__list')[0];

    if (data['action'] !== 'cu_remove_eth_address_from_account') {
        return;
    }

    if (!data['done']) {
        logsElement.classList.add('has_error');
        logsElement.innerHTML = data['error'];
        return;
    }

    if (logsElement.classList.contains('has_error')) {
        logsElement.classList.remove('has_error');
    }

    logsElement.innerHTML = data['success'];

    let nodeId = 'cu-address-' + data['account'];
    const node = document.getElementById(nodeId);
    node.parentNode.removeChild(node);

    const index = cuAddresses.indexOf(data['account']);
    if (index > -1) {
        cuAddresses.splice(index, 1);
    }

    if (cuAddresses.length < 1) {
        connectedAddressesElement.removeChild(connectedAddressesList);

        let div = document.createElement('div')
        div.classList.add('cu-connected-addresses__empty');
        div.innerHTML = displayMessages['no-addresses'];
        connectedAddressesElement.append(div);
    }
    if (cuPayHasButton) {
        cupaySetButtonText();
    }
}

/**
 * Payment
 * */
function cupayRequestPayment({
                                 amount,
                                 displayMessages,
                                 security,
                                 orderId,
                                 abiArray,
                                 contractAddress,
                                 targetAddress,
                                 ajaxurl
                             }) {
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
        let logsElement = document.getElementById('cu-pay__logs');
        logsElement.innerHTML = displayMessages['install-metamask'];
        logsElement.classList.add('has_error');
        return;
    }

    var erc_contract = web3.eth.contract(abiArray);
    var erc_contract_instance = erc_contract.at(contractAddress);
    erc_contract_instance.transfer(targetAddress, amount * 1E18, function (error, result) {
        if (error === null && result !== null) {
            const data = {
                'action': 'cu_check_transaction',
                'order_id': orderId,
                'tx': result,
                'security': security
            };
            console.log(result);
            jQuery.post(ajaxurl, data, function (response) {
                console.log('Response');
                console.log(response);
            });
        }
    });
}

/**
 * Controller
 * */
async function cupaySetButtonText() {
    let message = cuPayData['displayMessages'];
    let button = document.getElementById('cu-pay__pay-button');

    if (!window.ethereum || !window.web3) {
        button.innerHTML = message['pay-order-btn'];
        button.disabled = true;
        let logs = document.getElementById('cu-pay__logs');
        logs.innerHTML = message['install-metamask'];
        return;
    }

    let accounts = await ethereum.request({method: 'eth_requestAccounts'});
    let currentAccount = accounts[0];

    if (!currentAccount) {
        button.innerHTML = message['connect-metamask-btn'];
        return;
    }

    if (!cuAddresses.includes(currentAccount)) {
        button.innerHTML = message['bound-account-btn'];
        return;
    }

    button.innerHTML = message['pay-order-btn'];
}

async function cupayShowCurrentAccount() {
    let accounts = await ethereum.request({method: 'eth_requestAccounts'});
    let currentAccount = accounts[0];
    let element = document.getElementById('cu-pay__current-provider-account');
    if (!currentAccount) {
        element.innerHTML = '...';
        return;
    }

    element.innerHTML = currentAccount;
}

async function cupayPay(payData) {
    let {displayMessages} = payData;
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
        let logsElement = document.getElementById('cu-pay__logs');
        logsElement.innerHTML = displayMessages['install-metamask'];
        logsElement.classList.add('has_error');
        return;
    }

    let accounts = await ethereum.request({method: 'eth_requestAccounts'});
    let current_account = accounts[0];
    if (!cuAddresses.includes(current_account)) {
        cupayRequestSignature(payData);
    } else {
        cupayRequestPayment(payData);
    }
}
