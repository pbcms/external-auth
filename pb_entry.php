<?php
    namespace Module;

    use Registry\Event;
    use Registry\Auth as AuthPlugin;
    use Library\Header;
    use Library\Objects;
    use Library\Controller;

    class ExternalAuth {
        public function initialize() {
            $obj = new Objects();
            $list = $obj->list('mod-external-auth-connection', -1);
            foreach($list as $connection) {
                AuthPlugin::register($connection['name'], function($params) use ($connection) {
                    switch(explode('_', $connection['name'])[0]) {
                        case 'oidc':
                            require_once('mechanisms/oidc.php');
                            new \AuthMechanism\OIDC($params, $connection);
                            break;
                    }
                });

                Event::listen("auth-button-external-provider", function($data) use ($connection) {
                    $obj = new Objects();
                    $name = $obj->get('mod-external-auth-connection', $connection['name'], 'name');
                    return '<a href="' . SITE_LOCATION . 'pb-auth/plugin/' . $connection['name'] . '" class="button">' . $name . '</a>';
                });
            }
        }

        public function requestHandler($params) {
            if (isset($params[0])) {
                $actionName = $params[0];
                if (file_exists(DYNAMIC_DIR . '/modules/external-auth/actions/' . $actionName . '.php')) {
                    $params = array_slice($params, 1);
                    require_once DYNAMIC_DIR . '/modules/external-auth/actions/' . $actionName . '.php';
                } else {
                    die('unknown action.');
                }
            } else {
                Header::Location(SITE_LOCATION . 'pb-dashboard/module-config/external-auth');
            }
        }

        public function configurator($params) {
            $controller = new Controller;
            $user = $controller->__model('user');
            if ($user->check('module.mod_external-auth.manage-connections')) {
                if (isset($params[0])) {
                    if (file_exists(DYNAMIC_DIR . '/modules/external-auth/view-connection/' . explode('_', $params[0])[0] . '.php')) {
                        require_once(DYNAMIC_DIR . '/modules/external-auth/view-connection/' . explode('_', $params[0])[0] . '.php');
                    } else {
                        echo 'The requested connection does not exist. <a href="' . SITE_LOCATION . 'pb-dashboard/module-config/external-auth">Go back.</a>';
                    }
                } else {
                    require_once('configurator.php');
                }
            } else {
                ?>
                    <section class="page-introduction">
                        <h1>
                            Missing privileges.
                        </h1>
                        <p>
                            You are not permitted to manage connections.
                        </p>
                    </section>
                <?php
            }
        }
    }