<?php
/**
 * VK strategy for Opauth
 * based on https://vk.com/dev/authcode_flow_user
 */

class VKStrategy extends OpauthStrategy {
    
    /**
     * Compulsory config keys, listed as unassociative arrays
     */
    public $expects = array('app_id', 'app_secret');

    /**
     * Optional config keys, without predefining any default values.
     */
    public $optionals = array('redirect_uri', 'scope', 'response_type', 'v', 'state', 'display', 'revoke');

    /**
     * Optional config keys with respective default values, listed as associative arrays
     * eg. array('scope' => 'email');
     */
    public $defaults = array(
        'revoke' => 1,
        'v' => '5.65',
        'redirect_uri' => '{complete_url_to_strategy}oauth2callback',
        'scope' => 'email',
        'response_type' => 'code',
        'vk_profile_url' => 'https://vk.com/{domain}'
    );

    /**
     * Auth request
     */
    public function request() {
        $url = 'https://oauth.vk.com/authorize';

        $params = array(
            'client_id' => $this->strategy['app_id']
        );

        foreach ($this->optionals as $key){
            if (!empty($this->strategy[$key])) {
                $params[$key] = $this->strategy[$key];
            }
        }

        $this->clientGet($url, $params);
    }
    
    /**
     * Internal callback to get the code and request que authorization token, after VK's OAuth
     */
    public function oauth2callback() {
        if (array_key_exists('code', $_GET) && !empty($_GET['code'])) {
            $code = $_GET['code'];
            $url = 'https://oauth.vk.com/access_token';

            $params = array(
                'code' => $code,
                'client_id' =>$this->strategy['app_id'],
                'client_secret' => $this->strategy['app_secret'],
                'redirect_uri'=> $this->strategy['redirect_uri']
            );
            $response = $this->serverGet($url, $params, false, $headers);

            $results = json_decode($response);

            if (!empty($results) && !empty($results->access_token)) {
                $profileResponse = $this->getProfile($results->access_token, $results->user_id);
                $profile = $profileResponse['response'][0];

                $this->auth = array(
                    'uid' => $profile['uid'],
                    'info' => array(
                        'name' => sprintf('%s %s', $profile['first_name'], $profile['last_name']),
                        'nickname' => $profile['screen_name'],
                        'email' => isset($results->email) ? $results->email : '',
                        'urls' => array(
                            'vk' => str_replace('{domain}', $profile['domain'], $this->strategy['vk_profile_url'])
                        )
                    ),
                    'credentials' => array(
                        'token' => $results->access_token,
                        'expires' => $results->expires_in ? date('c', time() + $results->expires_in) : null // 0 - no expires
                    ),
                    'raw' => $profile
                );

                $this->mapProfile($profile, 'first_name', 'info.first_name');
                $this->mapProfile($profile, 'last_name', 'info.last_name');
                $this->mapProfile($profile, 'photo_100', 'info.image');

                $this->callback();
            } else {
                $error = array(
                    'code' => 'access_token_error',
                    'message' => 'Failed when attempting to obtain access token',
                    'raw' => array(
                        'response' => $response,
                        'headers' => $headers
                    )
                );

                $this->errorCallback($error);
            }
        } else {
            $error = array(
                'code' => 'oauth2callback_error',
                'raw' => $_GET
            );

            $this->errorCallback($error);
        }
    }
    
    private function getProfile($access_token, $uid) {
        if (empty($this->strategy['profile_fields'])) {
            $this->strategy['profile_fields'] = array('uid', 'first_name', 'last_name', 'screen_name', 'domain', 'photo_100');
        }

        if (is_array($this->strategy['profile_fields'])) {
            $fields = implode(',', $this->strategy['profile_fields']);
        } else {
            $fields = $this->strategy['profile_fields'];
        }

        $userinfo = $this->serverGet('https://api.vk.com/method/users.get', array('access_token' => $access_token, 'fields' => $fields));

        if (!empty($userinfo)) {
            return json_decode($userinfo, true);
        } else {
            $error = array(
                'code' => 'userinfo_error',
                'message' => 'Failed when attempting to query for user information',
                'raw' => array(
                    'response' => $userinfo,
                    'headers' => $headers
                )
            );

            $this->errorCallback($error);
        }
    }
}
?>
