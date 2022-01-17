(function() {
    const portal = document.querySelector('form.portal');

    portal.addEventListener("submit", async e => {
        e.preventDefault();
        portal.querySelector('p.error').innerText = '';

        if (await validatePortal()) {
            const data = fieldData();
            PB_API.post(location.href, data).then(res => {
                if (res.data.success == undefined) {
                    portal.querySelector('p.error').innerText = 'An unknown error has occured! (unknown_error)';
                } else if (res.data.success == false) {
                    if (res.data.error == 'invalid_username') {
                        portal.querySelector('input[name=username]').parentNode.classList.add('red-border');
                        portal.querySelector('input[name=username]').parentNode.querySelector('ul').innerHTML = '<li>Your username contains illegal characters!</li>';
                    } else if (res.data.error == 'username_taken') {
                        portal.querySelector('input[name=username]').parentNode.classList.add('red-border');
                        portal.querySelector('input[name=username]').parentNode.querySelector('ul').innerHTML = '<li>This username has already been taken!</li>';
                    } else {
                        portal.querySelector('p.error').innerText = res.data.message + ' (' + res.data.error + ')';
                    }
                } else {
                    let followup = new URLSearchParams(location.search).get('followup');
                    location.href = (followup ? SITE_LOCATION + (followup.substring(0, 1) == '/' ? followup.substring(1) : followup) : SITE_LOCATION + 'pb-dashboard');
                }
            });
        }
    });

    portal.querySelectorAll('.input-field input').forEach(el => el.addEventListener('input', e => {
        if (el.value.length > 0) {
            el.parentNode.classList.remove('red-border');
            el.parentNode.querySelector('ul').innerHTML = '';
        } else {
            if (el.required) {
                el.parentNode.classList.add('red-border');
                el.parentNode.querySelector('ul').innerHTML = '<li>This field cannot be empty!</li>';
            }
        }
    }));

    function fieldData() {
        return {
            firstname: portal.querySelector('input[name=firstname]').value,
            lastname: portal.querySelector('input[name=lastname]').value,
            username: (portal.querySelector('input[name=username]') ? portal.querySelector('input[name=username]').value : null)
        };
    }

    async function validatePortal() {
        var success = true;
        const data = fieldData();
        if (data.firstname.length == 0) {
            portal.querySelector('input[name=firstname]').parentNode.classList.add('red-border');
            portal.querySelector('input[name=firstname]').parentNode.querySelector('ul').innerHTML = '<li>This field cannot be empty!</li>';
            success = false;
        }

        if (data.lastname.length == 0) {
            portal.querySelector('input[name=lastname]').parentNode.classList.add('red-border');
            portal.querySelector('input[name=lastname]').parentNode.querySelector('ul').innerHTML = '<li>This field cannot be empty!</li>';
            success = false;
        }

        if (data.username && portal.querySelector('input[name=username]').required && data.username.length == 0) {
            portal.querySelector('input[name=username]').parentNode.classList.add('red-border');
            portal.querySelector('input[name=username]').parentNode.querySelector('ul').innerHTML = '<li>This field cannot be empty!</li>';
            success = false;
        }

        return success;
    }
})();