<?php

class OauthPdo  extends OAuth2\Storage\Pdo
{
    public function __construct($connection, $config = array())
    {
        //Add custom configs
        $config = array_merge(array(
            'configs_table' => 'oauth_configs',
            'orgs_table' => 'oauth_organizations',
        ), $config);
        parent::__construct($connection, $config);
    }

    /**
     * Get Configuration for name
     * @param $name
     * @return string|boolean
     */
    public function getConfig($name)
    {
        $stmt = $this->db->prepare(sprintf('SELECT value FROM %s WHERE name = :name ', $this->config['configs_table']));
        $stmt->execute(compact('name'));
        if ($config = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $config != null && isset($config['value']) ? $config['value'] : false;
        } else
            return false;
    }


    /**Get Configurations
     * @return array
     */
    public function getConfigs()
    {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s', $this->config['configs_table']));
        $stmt->execute();
        if ($configs = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $configs;
        } else
            return [];
    }

    /**
     * @param string $user_id
     * @return array|bool
     */
    public function getUserDetails($user_id)
    {
        return $this->getUser($user_id);
    }

    /**
     * plaintext passwords are bad!  Override this for your application
     *
     * @param array $user
     * @param string $password
     * @return bool
     */
    protected function checkPassword($user, $password)
    {
        return $user['password'] == $this->hashPassword_custom($password, $user['salt']);
    }

    /**
     * Custom function to Hash Password
     * @param string $password
     * @param string $salt
     * @return string
     * */
    protected function hashPassword_custom($password, $salt)
    {
        return hash('sha256', $password . $salt);
    }

    /**
     * @param string $unique (e.g user_id, email, phone)
     * @param string $password
     * @return bool
     */
    public function checkUserCredentials($unique, $password)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * FROM %s WHERE (user_id=:unique OR email=:unique OR phone=:unique) AND password=sha2(CONCAT(user_id,\':\',salt,\':\',:password),256) LIMIT 1', $this->config['user_table']));
        $stmt->execute(array('unique' => $unique, 'password' => $password));

        if ($userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (!empty($userInfo['user_id']))
                return $userInfo;
        }

        return false;
    }

    /**
     * Get User
     * @param string $unique (e.g user_id, email, phone)
     * @return array|bool
     */
    public function getUser($unique)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * FROM %s WHERE (user_id=:unique OR email=:unique OR phone=:unique) LIMIT 1', $this->config['user_table']));
        $stmt->execute(array('unique' => $unique));
        if (!$userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }
        return $userInfo;
    }


    /**
     * Get User
     * @param $user_id
     * @return array|bool
     */
    public function getSingleUserInfo($unique)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT user_id, email, name, phone, dial_code, scope  FROM %s WHERE (user_id=:unique OR email=:unique OR phone=:unique) LIMIT 1', $this->config['user_table']));
        $stmt->execute(array('unique' => $unique));

        if (!$userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }
        return $userInfo;
    }



    /**
     * Get Users
     * @param array $uniques
     * @return array|bool
     */
    public function getMultipleUserInfo($uniques)
    {
        $users = [];
        if (!empty($uniques)) {
            $whereInUserIds = implode(',', array_fill(0, is_array($uniques) ? count($uniques) : 0, '?'));
            $stmt = $this->db->prepare(sprintf('SELECT user_id, email, name, phone, dial_code, scope FROM %s WHERE user_id IN (%s) OR email IN (%s) OR phone IN (%s);', $this->config['user_table'], $whereInUserIds, $whereInUserIds, $whereInUserIds));
            $stmt->execute(array_merge($uniques, $uniques, $uniques));
            if ($result = $stmt->fetchAll(\PDO::FETCH_ASSOC)) {
                $users = (array_merge($users, $result));
            }
        }
        return $users;
    }


    /**
     * plaintext passwords are bad!  Override this for your application
     *
     * @param string $user_id
     * @param string $password
     * @param string $email
     * @param string $name
     * @param string $phone
     * @param string $dial_code
     * @param string $scope
     * @return bool
     */
    public function setUser_custom($user_id, $password, $email, $name, $phone, $dial_code, $scope)
    {
        //Create unique Salt string
        $salt = sha1(uniqid($user_id));

        // if it exists, update it.
        if ($this->getUser($user_id)) {
            $done = false;
            if (!empty($password)) {
                $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=sha2(CONCAT(:user_id,\':\',:salt,\':\',:password),256), salt=:salt where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'password', 'salt')) ? true : $done;
            }
            if (!empty($email)) {
                $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET email=:email where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'email')) ? true : $done;
            }
            if (!empty($name)) {
                $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET name=:name where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'name')) ? true : $done;
            }
            if (!empty($phone)) {
                $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET phone=:phone where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'phone')) ? true : $done;
            }
            if (!empty($dial_code)) {
                $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET dial_code=:dial_code where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'dial_code')) ? true : $done;
            }
            if (!empty($scope)) {
                $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET scope=:scope where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'scope')) ? true : $done;
            }

            return $done;
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (user_id, password, salt, email, name, phone, dial_code, scope) VALUES (:user_id, sha2(CONCAT(:user_id,\':\',:salt,\':\',:password),256), :salt, :email, :name, :phone, :dial_code, :scope)', $this->config['user_table']));
            return $stmt->execute(compact('user_id', 'password', 'salt', 'email', 'name', 'phone', 'dial_code', 'scope'));
        }
    }

    
    /**
     * @param string $client_id
     * @param null|string $client_secret
     * @param null|string $org_id
     * @param null|string $redirect_uri
     * @param null|array  $grant_types
     * @param null|string $scope
     * @param null|string $user_id
     * @param bool $issue_jwt
     * @return bool
     */
    public function setClientDetails_custom($client_id, $client_secret = null, $org_id = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null, $issue_jwt = false)
    {
        // if it exists, update it.
        if ($this->getClientDetails($client_id)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_secret=:client_secret, org_id=:org_id, redirect_uri=:redirect_uri, grant_types=:grant_types, scope=:scope, user_id=:user_id , issue_jwt=:issue_jwt where client_id=:client_id', $this->config['client_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (client_id, client_secret, org_id, redirect_uri, grant_types, scope, user_id, issue_jwt) VALUES (:client_id, :client_secret, :org_id, :redirect_uri, :grant_types, :scope, :user_id, :issue_jwt)', $this->config['client_table']));
        }

        return $stmt->execute(compact('client_id', 'client_secret', 'org_id', 'redirect_uri', 'grant_types', 'scope', 'user_id', 'issue_jwt'));
    }


    /**
     * @param string $scope
     * @return bool
     */
    public function scopeExists($scope)
    {
        $scope = explode(' ', $scope);
        $whereIn = implode(',', array_fill(0, count($scope), '?'));
        $stmt = $this->db->prepare(sprintf('SELECT count(scope) as count FROM %s WHERE scope IN (%s)', $this->config['scope_table'], $whereIn));
        $stmt->execute($scope);
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $result['count'] == count($scope);
        }

        return false;
    }


    /**
     * Check if User has the requested scope
     * @param $scope
     * @param $user_id
     * @return bool
     */
    public function scopeExistsForUser($scope, $user_id)
    {
        $stmt = $this->db->prepare(sprintf('SELECT scope FROM %s WHERE user_id = ? LIMIT 1', $this->config['user_table']));
        $stmt->execute([$user_id]);
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $found = true;
            $scopes = explode(' ', $scope);
            foreach ($scopes as $sc) {
                if (strpos(trim(strtolower($result['scope'])), trim(strtolower($sc))) === false) {
                    $found = false;
                }
            }
            return $found;
        }
        return false;
    }


    /**
     * Check if Client has the requested scope
     * @param $scope
     * @param $client_id
     * @return bool
     */
    public function scopeExistsForClient($scope, $client_id)
    {
        $stmt = $this->db->prepare(sprintf('SELECT scope FROM %s WHERE client_id = ? LIMIT 1', $this->config['client_table']));
        $stmt->execute([$client_id]);
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $found = true;
            $scopes = explode(' ', $scope);
            foreach ($scopes as $sc) {
                if (strpos(trim(strtolower($result['scope'])), trim(strtolower($sc))) === false) {
                    $found = false;
                }
            }
            return $found;
        }
        return false;
    }


    /**
     * @param mixed $client_id
     * @param $private_key
     * @param $public_key
     * @param $encryption_algorithm 
     * @return bool
     */
    public function setClientPublickKey($client_id, $private_key, $public_key, $encryption_algorithm = "RS256")
    {
        if($this->getPublicKey($client_id)){
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET private_key=:private_key, public_key=:public_key, encryption_algorithm=:encryption_algorithm where client_id=:client_id', $this->config['public_key_table']));
        }
        else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (client_id, private_key, public_key, encryption_algorithm) VALUES (:client_id, :private_key, :public_key, :encryption_algorithm)', $this->config['public_key_table']));
        }
        return $stmt->execute(compact('client_id', 'private_key', 'public_key', 'encryption_algorithm'));
    }
}
