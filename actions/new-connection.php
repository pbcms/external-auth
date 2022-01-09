<?php
    use Library\Controller;
    use Library\ModuleConfig;
    use Library\ObjectPropertyWorker;
    use Library\Objects;
    use Helper\Request;
    use Helper\Validate;
    use Helper\ApiResponse as Respond;

    $controller = new Controller;
    $user = $controller->__model('user');
    if (!Request::requireAuthentication()) die();
    if (!$user->check('module.mod_external-auth.manage-connections')) {
        Respond::error('missing_privileges', "You don't have the permissions to create new connections.");
        die();
    }
    
    $body = (object) Request::parseBody();
    $required = array('type', 'name', 'client_id', 'client_secret', 'issuer', 'endpoint_authorization', 'endpoint_token', 'endpoint_userinfo');
    $missing = Validate::listMissing($required, $body);

    if (count($missing) > 0) {
        Respond::error('missing_information', array(
            "message" => 'The following post information is missing from the request: ' . join(',', $missing) . '.',
            "missing_info" => $missing
        ));
    } else {
        if (file_exists(DYNAMIC_DIR . '/modules/external-auth/mechanisms/' . strtolower($body->type) . '.php')) {
            $obj = new Objects();
            if (!$obj->exists('mod-external-auth-connection', strtolower($body->type) . '_' . cleanify($body->name))) {
                $connection = new NewConnection($body->type, cleanify($body->name));
                switch(strtolower(strtolower($body->type))) {
                    case 'oidc':
                        $connection->set('name', $body->name);
                        $connection->set('client_id', $body->client_id);
                        $connection->set('client_secret', $body->client_secret);
                        $connection->set('issuer', $body->issuer);
                        $connection->set('endpoint_authorization', $body->endpoint_authorization);
                        $connection->set('endpoint_token', $body->endpoint_token);
                        $connection->set('endpoint_userinfo', $body->endpoint_userinfo);
                        if ($body->endpoint_end_session) $connection->set('endpoint_end_session', $body->endpoint_end_session);
                        break;
                }

                Respond::success();
            } else {
                Respond::error('name_taken', "The given name is already used, please choose a different name.");
            }
        } else {
            Respond::error('unknown_type', "The requested type, or authentication mechanism, is not supported.");
        }
    }

    class NewConnection extends ObjectPropertyWorker{
        public function __construct($type, $name) {
            $this->init('mod-external-auth-connection', strtolower($type) . '_' . cleanify($name));
        }
    }

    //inspired by: https://stackoverflow.com/a/14114419/9063317
    function cleanify($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        return strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $string)); // Removes special chars.
    }