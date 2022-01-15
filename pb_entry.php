<?php
    namespace Module;

    use Registry\Event;
    use Registry\Auth as AuthPlugin;
    use Library\Objects;
    use Library\Controller;
    use Library\Policy;
    use Library\Users;
    use Helper\Request;
    use Helper\Validate;
    use Helper\ApiResponse as Respond;
    use Helper\Header;

    class ExternalAuth {
        public function initialize() {
            $obj = new Objects();
            $list = $obj->list('mod-external-auth-connection', -1);
            $complete_signup = function() { $this->completeSignup(); };
            foreach($list as $connection) {
                AuthPlugin::register($connection['name'], function($params) use ($connection, $complete_signup) {
                    if (isset($params[0]) && $params[0] == 'complete-signup') {
                        $complete_signup();
                        die();
                    } else {
                        switch(explode('_', $connection['name'])[0]) {
                            case 'oidc':
                                require_once('mechanisms/oidc.php');
                                new \AuthMechanism\OIDC($params, $connection);
                                break;
                        }
                    }
                });

                Event::listen("auth-button-external-provider", function($data) use ($connection) {
                    $obj = new Objects();
                    $name = $obj->get('mod-external-auth-connection', $connection['name'], 'name');
                    return '<a href="' . SITE_LOCATION . 'pb-auth/plugin/' . $connection['name'] . '" class="button">' . $name . '</a>';
                });
            }

            Event::listen('request-processed', function($req) {
                $obj = new Objects;
                $users = new Users;
                $controller = new Controller;
                $userModel = $controller->__model('user');
                $user = $userModel->info();
                if ($user && $obj->exists('mod-external-auth-connection', $user->type)) {
                    if (intval($users->metaGet($user->id, 'mod-external_auth-signup_completed')) != 1) {
                        if ($req->controller != 'PbAuth' || $req->method != 'Plugin' || count($req->params) < 2 || $req->params[0] != $user->type || $req->params[1] != 'complete-signup') {
                            if ($req->controller != 'PbApi' && $req->controller != 'PbPubfiles' && $req->controller != 'PbLoader') {
                                Header::Location(SITE_LOCATION . 'pb-auth/plugin/' . $user->type . '/complete-signup?' . http_build_query(array(
                                    'followup' => $req->url
                                )));
                                die();
                            }
                        } else {
                            $this->completeSignup();
                            die();
                        }
                    }
                }
            });
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

        private function completeSignup() {
            $policy = new Policy;
            $controller = new Controller;
            $users = new Users;
            $userModel = $controller->__model('user');
            $user = $userModel->info();

            if (intval($users->metaGet($user->id, 'mod-external_auth-signup_completed')) == 1) {
                die('a');
                if (isset($_GET['followup'])) {
                    Header::Location(SITE_LOCATION . (substr($_GET['followup'], 0, 1) == '/' ? substr($_GET['followup'], 1) : $_GET['followup']));
                } else {
                    Header::Location(SITE_LOCATION . 'pb-dashboard');
                }
            } else {
                if (Request::method() == 'POST') {
                    $unameRequired = (intval($policy->get('usernames-required')) == 1);
                    $body = (object) Request::parseBody();
                    $required = array('firstname', 'lastname');

                    if ($unameRequired) array_push($required, 'username');
                    $missing = Validate::listMissing($required, $body);
                    if (count($missing) > 0) {
                        Respond::error('missing_information', array(
                            "message" => 'The following information is missing from the request: ' . join(',', $missing) . '.',
                            "missing_info" => $missing
                        ));
                    } else {
                        if (empty($body->firstname) || empty($body->lastname) || ($unameRequired ? empty($body->username) : false)) {
                            Respond::error('missing_information', array(
                                "message" => 'Some information in the request is empty.'
                            ));
                        } else {
                            $res = $users->update($user->id, (object) Validate::removeUnlisted($required, $body));
                            if ($res->success) {
                                $users->metaSet($user->id, 'mod-external_auth-signup_completed', 1);
                                Respond::success();
                            } else {
                                Respond::error($res->error, $res);
                            }
                        }
                    }
                } else {
                    require_once(DYNAMIC_DIR . '/modules/external-auth/complete-signup.php');
                    $controller->__template('pb-portal', array(
                        'title' => "Complete signup - " . SITE_TITLE,
                        'subtitle' => "Complete signup for " . SITE_TITLE,
                        'description' => SITE_DESCRIPTION,
                        "copyright" => "&copy; " . SITE_TITLE . " " . date("Y"),
                        "body" => array(
                            ['script', 'complete-signup.js', array("origin" => "module:external-auth")]
                        )
                    ));
                }
            }

            die();
        }
    }