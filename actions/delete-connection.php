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
        Respond::error('missing_privileges', "You don't have the permissions to delete connections.");
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
        $obj = new Objects();
        if ($obj->exists('mod-external-auth-connection', $body->system_name)) {
            $obj->purge('mod-external-auth-connection', $body->system_name);
            Respond::success();
        } else {
            Respond::error('unknown_connection', "The requested connection does not exist.");
        }
    }