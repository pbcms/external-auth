<?php
    use \Library\Assets;
    use \Library\Objects;

    //Load system assets before custom assets, needs SITE_LOCATION.
    \Core::SystemAssets();

    $assets = new Assets;
    $assets->registerHead('style', "configurator.css", array("origin" => "module:external-auth", "permanent" => true));
    $assets->registerBody('script', "configurator.js", array("origin" => "module:external-auth", "permanent" => true));

    $obj = new Objects();
    $list = $obj->list('mod-external-auth-connection', -1);
    $tableContent = '';

    foreach($list as $connection) {
        $type = explode('_', $connection['name'])[0];
        switch($type) {
            case 'oidc':
                $type = 'OIDC';
                break;
        }

        $name = $obj->get('mod-external-auth-connection', $connection['name'], 'name');
        $tableContent .= '<tr id="' . $connection['name'] . '"><td>' . $name . '</td><td>' . $type . '</td><td><a href="' . SITE_LOCATION . 'pb-dashboard/module-config/external-auth/' . $connection['name'] . '">View & Manage</a></td></tr>';
    }
?>

<section class="page-introduction">
    <h1>
        External authentication.
    </h1>
    <p>
        Manage connections to external authentication endpoints.
    </p>
</section>

<section class="create-new-connection transparent">
    <h3>
        <span>+</span> Add a new connection
    </h3>
    <div class="types buttons">
        <a href="#" add-connection="oidc">
            OpenID Connect
        </a>
    </div>
    <div class="creation-forms">
        <form type="oidc">
            <h3>
                <span>A</span> Connection details
            </h3>
            <input type="text" name="name" placeholder="Display name *" required>
            <p class="redirect_url"></p>
            <input type="text" name="client_id" placeholder="Client ID *" required>
            <input type="text" name="client_secret" placeholder="Client Secret *" required>

            <h3>
                <span>B</span> Endpoint configuration
            </h3>
            
            <input type="text" class="field-manual" name="issuer" placeholder="Issuer URL *" required>
            <a href="#" fetch-issuer-wellknown>
                Fetch & overwrite
            </a>
            <input type="text" class="field-endpoint" name="endpoint_authorization" placeholder="Authorization endpoint *" required>
            <input type="text" class="field-endpoint" name="endpoint_token" placeholder="Token endpoint *" required>
            <input type="text" class="field-endpoint" name="endpoint_userinfo" placeholder="Userinfo endpoint *" required>
            <input type="text" class="field-endpoint" name="endpoint_end_session" placeholder="End session endpoint">
            <input type="text" name="scopes" placeholder="Scopes *" value="profile email">
        
            <h3>
                <span>C</span> Save & test
            </h3>

            <div class="buttons">
                <button>
                    Save connection
                </button>
            </div>
        </form>
    </div>
</section>

<section class="transparent existing-connections">
    <table>
        <thead>
            <th>
                Name
            </th>
            <th>
                Type
            </th>
            <th>
                Actions
            </th>
        </thead>
        <tbody>
            <?php
                echo $tableContent;
            ?>
        </tbody>
    </table>
</section>