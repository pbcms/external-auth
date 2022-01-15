
//OIDC new connection.
(function() {
    const form = document.querySelector('.edit-connection form[type="oidc"]');

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

    form.querySelector('input[name="issuer"]').dispatchEvent(new CustomEvent('input'));

    form.querySelector('a[fetch-issuer-wellknown]').addEventListener('click', function(e) {
        e.preventDefault();
        fetchIssuerWellKnown(true);
    });

    form.addEventListener('submit', e => {
        e.preventDefault();
        var data = {
            system_name: form.querySelector('input[name="system_name"]').value,
            name: form.querySelector('input[name="name"]').value,
            client_id: form.querySelector('input[name="client_id"]').value,
            client_secret: form.querySelector('input[name="client_secret"]').value,
            issuer: form.querySelector('input[name="issuer"]').value,
            endpoint_authorization: form.querySelector('input[name="endpoint_authorization"]').value,
            endpoint_token: form.querySelector('input[name="endpoint_token"]').value,
            endpoint_userinfo: form.querySelector('input[name="endpoint_userinfo"]').value,
            endpoint_end_session: form.querySelector('input[name="endpoint_end_session"]').value,
            scopes: form.querySelector('input[name="scopes"]').value
        };

        PbAuth.apiInstance(false).post(SITE_LOCATION + 'pb-loader/module/external-auth/update-connection', data).then(res => {
            if (res.data && res.data.success) {
                location.reload();
            } else {
                console.log(res.data);
                alert("Error: " + res.data.message + ' (' + res.data.error + ')');
            }
        });
    });

    form.querySelector('button[delete-connection]').addEventListener('click', e => {
        form.querySelector('a[confirmed-delete-connection]').style.display = 'block';
    });

    form.querySelector('a[confirmed-delete-connection]').addEventListener('click', e => {
        e.preventDefault();
        var data = {
            system_name: form.querySelector('input[name="system_name"]').value
        };

        PbAuth.apiInstance(false).post(SITE_LOCATION + 'pb-loader/module/external-auth/delete-connection/', data).then(res => {
            if (res.data && res.data.success) {
                location.href = SITE_LOCATION + 'pb-dashboard/module-config/external-auth';
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