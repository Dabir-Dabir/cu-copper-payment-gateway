async function cupayRequestSignature(message, displayMessages) {
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
        'security': cuSecurity
    };

    let ajaxurl = "/wp-admin/admin-ajax.php";
    jQuery.post(ajaxurl, data, function (response) {
        console.log(JSON.parse(response));
        cupayHandleRequestSignatureResponse(JSON.parse(response), displayMessages);
    });
}

function cupayHandleRequestSignatureResponse(data, displayMessages) {
    let logsElement = document.getElementById('cu-logs');
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

    if(logsElement.classList.contains('has_error')) {
        logsElement.classList.remove('has_error');
    }

    logsElement.innerHTML = data['success'];

    let listItem = document.createElement('li');
    listItem.classList.add('cu-connected-addresses__list-item');
    listItem.dataset.cuAddress = data['account'];

    let span = document.createElement('span')
    span.classList.add('cu-connected-addresses__span');
    span.innerHTML = data['account'];
    listItem.append(span);

    let deleteButton = document.createElement('span')
    deleteButton.classList.add('cu-connected-addresses__delete-button');
    deleteButton.innerHTML = 'X';
    listItem.append(deleteButton);

    connectedAddressesList.append(listItem);
}