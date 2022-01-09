//New connection tabs
(function() {
    document.querySelectorAll('.create-new-connection .types a[add-connection]').forEach(el => el.addEventListener('click', e => {
        var type = el.getAttribute('add-connection');
        var form = document.querySelector('.creation-forms form[type="' + type + '"]');
        if (form) {
            if (form.style.display == 'block') {
                document.querySelector('.existing-connections').style.display = 'block';
                document.querySelectorAll('.create-new-connection .types a[add-connection]').forEach(el => el.style.backgroundColor = '#000');
                document.querySelectorAll('.creation-forms form[type]').forEach(el => el.style.display = 'none');
            } else {
                document.querySelector('.existing-connections').style.display = 'none';
                document.querySelectorAll('.create-new-connection .types a[add-connection]').forEach(el => el.style.backgroundColor = '#000');
                document.querySelectorAll('.create-new-connection .types a[add-connection="' + type + '"]').forEach(el => el.style.backgroundColor = '#555');
                document.querySelectorAll('.creation-forms form[type]').forEach(el => el.style.display = 'none');
                form.style.display = 'block'
            }
        } else {
            document.querySelector('.existing-connections').style.display = 'block';
            document.querySelectorAll('.create-new-connection .types a[add-connection]').forEach(el => el.style.backgroundColor = '#000');
            document.querySelectorAll('.creation-forms form[type]').forEach(el => el.style.display = 'none');
        }
    }));
})();

//OIDC new connection.
(function() {
    const form = document.querySelector('.creation-forms form[type="oidc"]');

    form.querySelector('input[name="name"]').addEventListener('input', e => {
        if (e.target.value == '') {
            form.querySelector('input[name="name"] + .redirect_url').innerText = '';
            form.querySelector('input[name="name"] + .redirect_url').style.display = 'none';
        } else {
            form.querySelector('input[name="name"] + .redirect_url').innerHTML = '<b>Redirect URI:</b> ' + SITE_LOCATION + 'pb-auth/plugin/oidc_' + e.target.value.toLowerCase().replace(' ', '-').replace(/[^A-Za-z0-9\-]/g, '');
            form.querySelector('input[name="name"] + .redirect_url').style.display = 'block';
        }
    });

    let issuerInputCounter = 0;
    form.querySelector('input[name="issuer"]').addEventListener('input', function() {
        issuerInputCounter++;
        var current = issuerInputCounter;
        setTimeout(() => {
            if (issuerInputCounter == current) {
                fetchIssuerWellKnown();
            }
        }, 350);
    });

    form.querySelector('a[fetch-issuer-wellknown]').addEventListener('click', function(e) {
        e.preventDefault();
        fetchIssuerWellKnown(true);
    });

    form.addEventListener('submit', e => {
        e.preventDefault();
        var data = {
            type: 'oidc',
            name: form.querySelector('input[name="name"]').value,
            client_id: form.querySelector('input[name="client_id"]').value,
            client_secret: form.querySelector('input[name="client_secret"]').value,
            issuer: form.querySelector('input[name="issuer"]').value,
            endpoint_authorization: form.querySelector('input[name="endpoint_authorization"]').value,
            endpoint_token: form.querySelector('input[name="endpoint_token"]').value,
            endpoint_userinfo: form.querySelector('input[name="endpoint_userinfo"]').value,
            endpoint_end_session: form.querySelector('input[name="endpoint_end_session"]').value
        };

        PbAuth.apiInstance(false).post(SITE_LOCATION + 'pb-loader/module/external-auth/new-connection', data).then(res => {
            if (res.data.success) {
                location.reload();
            } else {
                console.log(res.data);
                alert("Error: " + res.data.message + ' (' + res.data.error + ')');
            }
        });
    });

    function fetchIssuerWellKnown(overwrite = false) {
        var base = form.querySelector('input[name="issuer"]').value;
        if (base.slice(-1) != '/') base += '/';
        axios.get(base + '.well-known/openid-configuration').then(res => {
            console.log(res);
            if (processWellKnownResponse(res, overwrite)) {
                form.querySelector('a[fetch-issuer-wellknown]').style.display = 'block';
            } else {
                throw new Error();
            }
        }).catch(e => {
            axios.get(new URL(base).origin + '/.well-known/openid-configuration').then(res => {
                console.log(res);
                if (processWellKnownResponse(res, overwrite)) {
                    form.querySelector('a[fetch-issuer-wellknown]').style.display = 'block';
                } else {
                    form.querySelector('a[fetch-issuer-wellknown]').style.display = 'none';
                }
            });
        })
    }

    function processWellKnownResponse(res, overwrite) {
        var success = false;
        if (res.status == 200 && res.data) {
            if (res.data.authorization_endpoint && (overwrite | form.querySelector('input[name="endpoint_authorization"]').value == '')) {
                form.querySelector('input[name="endpoint_authorization"]').value = res.data.authorization_endpoint;
            }

            if (res.data.token_endpoint && (overwrite | form.querySelector('input[name="endpoint_token"]').value == '')) {
                form.querySelector('input[name="endpoint_token"]').value = res.data.token_endpoint;
            }

            if (res.data.userinfo_endpoint && (overwrite | form.querySelector('input[name="endpoint_userinfo"]').value == '')) {
                form.querySelector('input[name="endpoint_userinfo"]').value = res.data.userinfo_endpoint;
            }

            if (res.data.end_session_endpoint && (overwrite | form.querySelector('input[name="endpoint_end_session"]').value == '')) {
                form.querySelector('input[name="endpoint_end_session"]').value = res.data.end_session_endpoint;
            }

            success = (res.data.authorization_endpoint || res.data.token_endpoint || res.data.userinfo_endpoint || res.data.end_session_endpoint);
        } else {
            success = false;
        }

        return success;
    }
})();

