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
        Respond::error('missing_privileges', "You don't have the permissions to update connections.");
        die();
    }
    
    $body = (object) Request::parseBody();
    $required = array('system_name');
    $missing = Validate::listMissing($required, $body);

    if (count($missing) > 0) {
        Respond::error('missing_information', array(
            "message" => 'The following post information is missing from the request: ' . join(',', $missing) . '.',
            "missing_info" => $missing
        ));
    } else {
        $connection = new Connection($body->system_name);
        $obj = new Objects();
        if ($obj->exists('mod-external-auth-connection', $body->system_name)) {
            switch(strtolower(explode('_', $body->system_name)[0])) {
                case 'oidc':
                    if (isset($body->name))                    $connection->set('name', $body->name);
                    if (isset($body->client_id))               $connection->set('client_id', $body->client_id);
                    if (isset($body->client_secret))           $connection->set('client_secret', $body->client_secret);
                    if (isset($body->issuer))                  $connection->set('issuer', $body->issuer);
                    if (isset($body->endpoint_authorization))  $connection->set('endpoint_authorization', $body->endpoint_authorization);
                    if (isset($body->endpoint_token))          $connection->set('endpoint_token', $body->endpoint_token);
                    if (isset($body->endpoint_userinfo))       $connection->set('endpoint_userinfo', $body->endpoint_userinfo);
                    if (isset($body->endpoint_end_session))    $connection->set('endpoint_end_session', $body->endpoint_end_session);
                    if (isset($body->scopes))                  $connection->set('scopes', $body->scopes);
                    break;
            }

            Respond::success();
        } else {
            Respond::error('unknown_connection', "The requested connection does not exist.");
        }
    }

    class Connection extends ObjectPropertyWorker{
        public function __construct($systemName) {
            $this->init('mod-external-auth-connection', $systemName);
        }
    }