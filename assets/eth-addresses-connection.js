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

    let accounts = await ethereum.request({ method: 'eth_requestAccounts' });
    let from;
    let sign;
    try {
        from = accounts[0];
        sign = await ethereum.request({
            method: 'personal_sign',
            params: [message, from],
        });
        console.log(sign)
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
    jQuery.post(ajaxurl, data, function(response) {
        alert('Got this from the server: ' + response);
    });
}