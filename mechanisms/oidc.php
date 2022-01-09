<?php
    namespace AuthMechanism;

    use Library\Objects;
    use Library\Users;
    use Library\Policy;
    use Library\Token;
    use Library\Sessions;
    use Helper\Header;
    use Helper\ApiResponse as Respond;

    class OIDC {
        private $name;
        private $systemName;
        private $client_id;
        private $client_secret;
        private $endpoints = array();

        public function __construct($params, $connection) {
            $obj = new Objects();
            $properties = $obj->properties("mod-external-auth-connection", $connection['name']);
            $this->systemName = $connection['name'];
            foreach($properties as $property) {
                switch($property['property']) {
                    case 'name':
                        $this->name = $property['value'];
                        break;
                    case 'client_id':
                        $this->client_id = $property['value'];
                        break;
                    case 'client_secret':
                        $this->client_secret = $property['value'];
                        break;
                    case 'issuer':
                    case 'endpoint_authorization':
                    case 'endpoint_token':
                    case 'endpoint_userinfo':
                    case 'endpoint_end_session':
                        $endpoint = $property['property'];
                        $this->endpoints[$endpoint] = $property['value'];
                        break;
                }
            }

            $sliced = array_slice($params, 1);
            if (!isset($params[0])) {
                $this->signin($sliced);   
            } else {
                switch($params[0]) {
                    case 'signout':
                        $this->signout($sliced);   
                        break;
                    default:
                        http_response_code(400);
                        die('unknown action');
                }
            }
        }

        private function signin($params) {
            if (isset($_GET['code'])) {
                $token = $this->apiRequest($this->endpoints['endpoint_token'], array(
                    "grant_type" => "authorization_code",
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'redirect_uri' => SITE_LOCATION . 'pb-auth/plugin/' . $this->systemName,
                    'code' => $_GET['code']
                ));

                $user = (object) $this->apiRequest($this->endpoints['endpoint_userinfo'], false, array(
                    'Authorization: Bearer ' . $token->access_token
                ));

                $policy = new Policy();
                $users = new Users();

                if (!$user->email || empty($user->email)) {
                    Respond::error('missing_email', 'no email address was provided by the authentication server.');
                    die();
                }

                $uid = null;
                $res = $users->list(array("email" => $user->email));
                if (count($res) > 0) {
                    $uid = $res[0]['id'];

                    if ($res[0]['status'] == "LOCKED") {
                        Respond::error('user_locked', $this->lang->get('messages.api-auth.create-session.error-user_locked', "The user you are trying to create a session for has been locked by the system or an administrator."));
                        die();
                    }
                } else {
                    if ($policy->get('usernames-enabled') == '1') {
                        $count = 0;
                        if (!$user->preferred_username || empty($user->preferred_username)) {
                            $user->preferred_username = explode('@', $user->email)[0];
                        }

                        $user->preferred_username = str_replace("/[^A-Za-z0-9-_.]/", '', $user->preferred_username);                        
                        $found = $users->list(array("username" => $user->preferred_username));
                        while(count($found) > 0) {
                            $count++;
                            $found = $users->list(array("username" => $user->preferred_username . $count));
                        }

                        if ($count == 0) {
                            $username = $user->preferred_username;
                        } else {
                            $username = $user->preferred_username . $count;
                        }
                    } else {
                        $username = '';
                    }

                    $res = $users->create(array(
                        "firstname" => $user->given_name,
                        "lastname" => $user->family_name,
                        "email" => $user->email,
                        "username" => $username,
                        "type" => $this->systemName,
                        "status" => ($user->email_verified ? "VERIFIED" : "UNVERIFIED")
                    ));

                    if (!$res->success) {
                        Respond::error($res->error, $res);
                        die();
                    } else {
                        $uid = $res->id;
                    }
                }

                $tokens = new Token;
                $sessions = new Sessions;    
                $session = $sessions->create($uid);
                $token = $tokens->create('refresh-token', array("session" => $session));
    
                if ($token->success) {
                    $secure = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? true : false);
                    $url = parse_url(SITE_LOCATION);
                    setcookie('pb-refresh-token', $token->token, 2147483647, $url['path'], $url['host'], $secure, true);
                    Header::Location(SITE_LOCATION . 'pb-dashboard');
                } else {
                    Respond::error($token->error, $this->lang->get('messages.api-auth.create-session.error-token_error', "An error occured while creating the refresh-token."));
                }
            } else {
                Header::Location($this->endpoints['endpoint_authorization'] . '?' . http_build_query(array(
                    'client_id' => $this->client_id,
                    'redirect_uri' => SITE_LOCATION . 'pb-auth/plugin/' . $this->systemName,
                    'response_type' => 'code',
                    'scope' => 'profile email'
                )));

                die();
            }
        }

        private function signout($params) {

        }

        private function apiRequest($url, $post=FALSE, $headers=array()) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
          
            $response = curl_exec($ch);
          
          
            if($post)
              curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
          
            $headers[] = 'Accept: application/json';
          
            //if(session('access_token'))
              //$headers[] = 'Authorization: Bearer ' . session('access_token');
          
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          
            $response = curl_exec($ch);
            return json_decode($response);
        }
    }