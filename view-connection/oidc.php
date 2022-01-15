<?php
    use \Library\Assets;
    use \Library\Objects;

    //Load system assets before custom assets, needs SITE_LOCATION.
    \Core::SystemAssets();

    $assets = new Assets;
    $assets->registerHead('style', "view-connection.css", array("origin" => "module:external-auth", "permanent" => true));
    $assets->registerBody('script', "view-connection.js", array("origin" => "module:external-auth", "permanent" => true));

    $obj = new Objects();
    if ($obj->exists("mod-external-auth-connection", $params[0])) {
        $properties = $obj->properties("mod-external-auth-connection", $params[0]);
        $connection = (object) array(
            "systemName" => $params[0],
            "endpoints" => array()
        );

        foreach($properties as $property) {
            switch($property['property']) {
                case 'name':
                    $connection->name = $property['value'];
                    break;
                case 'client_id':
                    $connection->client_id = $property['value'];
                    break;
                case 'client_secret':
                    $connection->client_secret = $property['value'];
                    break;
                case 'issuer':
                case 'endpoint_authorization':
                case 'endpoint_token':
                case 'endpoint_userinfo':
                case 'endpoint_end_session':
                    $endpoint = $property['property'];
                    $connection->endpoints[$endpoint] = $property['value'];
                    break;
                case 'scopes':
                    $connection->scopes = $property['value'];
                    break;
            }
        }
    } else {
        echo 'The requested connection does not exist. <a href="' . SITE_LOCATION . 'pb-dashboard/module-config/external-auth">Go back.</a>';
    }

    if (isset($connection)) {
?>

    <section class="page-introduction">
        <h1>
            <?php echo $connection->name; ?>
        </h1>
        <p>
            Manage the connection. <a href="<?php echo SITE_LOCATION; ?>pb-dashboard/module-config/external-auth">Go back.</a>
        </p>
    </section>

    <section class="edit-connection transparent no-padding">
        <form type="oidc">
            <h3>
                <span>A</span> Connection details
            </h3>
            <input type="hidden" name="system_name" value="<?php echo $params[0]; ?>">
            <input type="text" name="name" value="<?php echo $connection->name; ?>" placeholder="Display name *" required>
            <input type="text" name="client_id" value="<?php echo $connection->client_id; ?>" placeholder="Client ID *" required>
            <input type="text" name="client_secret" value="<?php echo $connection->client_secret; ?>" placeholder="Client Secret *" required>

            <h3>
                <span>B</span> Endpoint configuration
            </h3>
                
            <input type="text" class="field-manual" name="issuer" value="<?php echo $connection->endpoints['issuer']; ?>" placeholder="Issuer URL *" required>
            <a href="#" fetch-issuer-wellknown>
                Fetch & overwrite
            </a>
            <input type="text" class="field-endpoint" name="endpoint_authorization" value="<?php echo $connection->endpoints['endpoint_authorization']; ?>" placeholder="Authorization endpoint *" required>
            <input type="text" class="field-endpoint" name="endpoint_token" value="<?php echo $connection->endpoints['endpoint_token']; ?>" placeholder="Token endpoint *" required>
            <input type="text" class="field-endpoint" name="endpoint_userinfo" value="<?php echo $connection->endpoints['endpoint_userinfo']; ?>" placeholder="Userinfo endpoint *" required>
            <input type="text" class="field-endpoint" name="endpoint_end_session" value="<?php echo (isset($connection->endpoints['endpoint_end_session']) ? $connection->endpoints['endpoint_end_session'] : ''); ?>" placeholder="End session endpoint">
            <input type="text" name="scopes" value="<?php echo $connection->scopes; ?>" placeholder="Scopes">
            
            <h3>
                <span>C</span> Save & test
            </h3>

            <div class="buttons">
                <button>
                    Update connection
                </button>
                <button type="button" delete-connection>
                    Delete connection
                </button>
                <a href="#" confirmed-delete-connection>
                    Yes, I want to delete this connection.
                </a>
            </div>
        </form>
    </section>

<?php 
    } 
?>