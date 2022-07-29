<?php

namespace Application\Services;

use OAuth2\Storage\Pdo;
use  Application\Services\OAuthScopeService;

class OAuthStorageService extends Pdo
{
    public function __construct($connection, $config = array())
    {
        // Add custom configs
        $config = array_merge(array(
            'orgs_table' => 'oauth_organizations',
        ), $config);
        parent::__construct($connection, $config);
    }


    ##################################
    # CLIENT
    ##################################


    /**
     * plaintext passwords are bad!  Override this for your application
     *
     * @param array $user
     * @param string $password
     * @return bool
     */
    protected function checkPassword($user, $password)
    {
        return $user['password'] == $this->hashPasswordWithSalt($password, $user['salt']);
    }

    /**
     * Custom function to Hash Password
     * @param string $password
     * @param string $salt
     * @return string
     * */
    protected function hashPasswordWithSalt($password, $salt)
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
        if (!$unique) {
            return false;
        }

        $stmt = $this->db->prepare(sprintf('SELECT * FROM %s WHERE (user_id=:unique OR email=:unique) AND password=sha2(CONCAT(user_id,\':\',salt,\':\',:password),256) LIMIT 1', $this->config['user_table']));
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
        if (!$unique) {
            return false;
        }

        $stmt = $this->db->prepare(sprintf('SELECT * FROM %s WHERE (user_id=:unique OR email=:unique) LIMIT 1', $this->config['user_table']));
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
    public function getMultipleUsers($uniques)
    {
        $users = [];
        if (!empty($uniques)) {
            $whereInUserIds = implode(',', array_fill(0, is_array($uniques) ? count($uniques) : 0, '?'));
            $stmt = $this->db->prepare(sprintf('SELECT user_id, email, name, phone, dial_code, scope, cred_updated_at FROM %s WHERE user_id IN (%s) OR email IN (%s);', $this->config['user_table'], $whereInUserIds, $whereInUserIds));
            $stmt->execute(array_merge($uniques, $uniques));
            if ($result = $stmt->fetchAll(\PDO::FETCH_ASSOC)) {
                $users = (array_merge($users, $result));
            }
        }
        return $users;
    }

    /**
     * Get User
     * @param $unique
     * @param $excludeScopes
     * @return array|bool
     */
    public function getCustomUser($unique, $excludeScopes = false)
    {
        if (!$unique) {
            return false;
        }

        if ($excludeScopes) {
            $stmt = $this->db->prepare(sprintf('SELECT user_id AS id, email, name, phone, dial_code, cred_updated_at  FROM %s WHERE (user_id=:unique OR email=:unique) LIMIT 1', $this->config['user_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('SELECT user_id AS id, email, name, phone, dial_code, scope, cred_updated_at  FROM %s WHERE (user_id=:unique OR email=:unique) LIMIT 1', $this->config['user_table']));
        }
        $stmt->execute(array('unique' => $unique));
        if (!$userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }
        return $userInfo;
    }

    /**
     * Get User for given claims
     * @param $unique
     * @param $claims
     * @param $excludeScopes
     * @return array|bool
     */
    public function getCustomUserWIthClaims($unique, $claims = [], $excludeScopes = true)
    {
        if (!$unique) {
            return false;
        }

        $fields = [];
        if (in_array(SCOPE_CLAIM_PROFILE, $claims)) {
            $fields[] = 'name';
            $fields[] = 'email';
            $fields[] = 'phone';
            $fields[] = 'dial_code';
        } else {
            if (in_array(SCOPE_CLAIM_NAME, $claims)) {
                $fields[] = 'name';
            }
            if (in_array(SCOPE_CLAIM_EMAIL, $claims)) {
                $fields[] = 'email';
            }
            if (in_array(SCOPE_CLAIM_PHONE, $claims)) {
                $fields[] = 'phone';
                $fields[] = 'dial_code';
            }
        }
        if (!$excludeScopes) {
            $fields[] = 'scope';
        }

        if (!empty($fields)) {
            $stmt = $this->db->prepare(sprintf('SELECT user_id AS id, %s, cred_updated_at  FROM %s WHERE (user_id=:unique OR email=:unique) LIMIT 1', implode(',', $fields), $this->config['user_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('SELECT user_id AS id, cred_updated_at  FROM %s WHERE (user_id=:unique OR email=:unique) LIMIT 1', $this->config['user_table']));
        }

        $stmt->execute(array('unique' => $unique));
        if (!$userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }
        return $userInfo;
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
     * @return string|false - User ID or false if failed
     */
    public function setCustomUser($user_id, $password, $email, $name, $phone, $dial_code, $scope)
    {
        //Create unique Salt string
        $salt = sha1(uniqid($user_id));

        // if it exists, update it.
        if (!empty($user = $this->getUser($user_id)) || !empty($user = $this->getUser($email))) {
            $done = false;
            $user_id = $user['user_id'];
            if (!empty($password)) {
                $stmt = $this->db->prepare(sprintf('UPDATE %s SET password=sha2(CONCAT(:user_id,\':\',:salt,\':\',:password),256), salt=:salt, cred_updated_at=NOW() where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'password', 'salt')) ? true : $done;
            }
            if (!empty($email)) {
                $stmt = $this->db->prepare(sprintf('UPDATE %s SET email=:email where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'email')) ? true : $done;
            }
            if (!empty($name)) {
                $stmt = $this->db->prepare(sprintf('UPDATE %s SET name=:name where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'name')) ? true : $done;
            }
            if (!empty($phone)) {
                $stmt = $this->db->prepare(sprintf('UPDATE %s SET phone=:phone where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'phone')) ? true : $done;
            }
            if (!empty($dial_code)) {
                $stmt = $this->db->prepare(sprintf('UPDATE %s SET dial_code=:dial_code where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'dial_code')) ? true : $done;
            }
            if (!empty($scope)) {
                $stmt = $this->db->prepare(sprintf('UPDATE %s SET scope=:scope where user_id=:user_id', $this->config['user_table']));
                $done = $stmt->execute(compact('user_id', 'scope')) ? true : $done;
            }
            return $done ? $user_id : false;
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (user_id, password, salt, email, name, phone, dial_code, scope) VALUES (:user_id, sha2(CONCAT(:user_id,\':\',:salt,\':\',:password),256), :salt, :email, :name, :phone, :dial_code, :scope)', $this->config['user_table']));
            $done = $stmt->execute(compact('user_id', 'password', 'salt', 'email', 'name', 'phone', 'dial_code', 'scope'));
            return $done ? $user_id : false;
        }
    }


    ##################################
    # CLIENT
    ##################################


    /**
     * @param string $client_id
     * @return array|mixed
     */
    public function getCustomClientDetails($client_id, $org_id = null)
    {
        if ($org_id) {
            $stmt = $this->db->prepare(sprintf('SELECT * from %s where client_id = :client_id and org_id = :org_id', $this->config['client_table']));
            $stmt->execute(compact('client_id', 'org_id'));
        } else {
            $stmt = $this->db->prepare(sprintf('SELECT * from %s where client_id = :client_id', $this->config['client_table']));
            $stmt->execute(compact('client_id'));
        }

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $org_id
     * @param string $client_id
     * @param string $client_name
     * @param null|string $redirect_uri
     * @param null|array  $grant_types
     * @param null|string $scope
     * @param null|string $user_id
     * @param bool $issue_jwt
     * @return bool
     */
    public function setCustomClientDetails($org_id, $client_id, $client_name, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null, $issue_jwt = true)
    {
        // if it exists, update it.
        if ($this->getCustomClientDetails($client_id, $org_id)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET client_secret=:client_secret, client_name=:client_name, redirect_uri=:redirect_uri, grant_types=:grant_types, scope=:scope, user_id=:user_id, issue_jwt=:issue_jwt where client_id=:client_id', $this->config['client_table']));
            return $stmt->execute(compact('client_id', 'client_name', 'client_secret', 'redirect_uri', 'grant_types', 'scope', 'user_id', 'issue_jwt'));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (org_id, client_id, client_name, client_secret, redirect_uri, grant_types, scope, user_id, issue_jwt) VALUES (:org_id, :client_id, :client_name, :client_secret, :redirect_uri, :grant_types, :scope, :user_id, :issue_jwt)', $this->config['client_table']));
            return $stmt->execute(compact('org_id', 'client_id', 'client_name', 'client_secret', 'redirect_uri', 'grant_types', 'scope', 'user_id', 'issue_jwt'));
        }
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
        if ($this->getPublicKey($client_id)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET private_key=:private_key, public_key=:public_key, encryption_algorithm=:encryption_algorithm where client_id=:client_id', $this->config['public_key_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (client_id, private_key, public_key, encryption_algorithm) VALUES (:client_id, :private_key, :public_key, :encryption_algorithm)', $this->config['public_key_table']));
        }
        return $stmt->execute(compact('client_id', 'private_key', 'public_key', 'encryption_algorithm'));
    }

    ##################################
    # ORGANIZATION
    ##################################

    /**
     * @param string $org_id
     * @return array|mixed
     */
    public function getOrganizationDetails($org_id)
    {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s where org_id = :org_id', $this->config['orgs_table']));
        $stmt->execute(compact('org_id'));

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $org_name
     * @param null|string $org_logo
     * @param null|array  $org_id 
     * @return bool|int
     */
    public function setOrganizationDetails($org_name, $logo = null, $org_id = null)
    {
        // if it exists, update it.
        if ($org_id && $this->getOrganizationDetails($org_id)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET org_name=:org_name, logo=:logo where org_id=:org_id', $this->config['orgs_table']));
            return $stmt->execute(compact('org_id', 'org_name', 'logo'));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (org_name, logo) VALUES (:org_name, :logo)', $this->config['orgs_table']));
            return $stmt->execute(compact('org_name', 'logo')) ? $this->db->lastInsertId() : false;
        }
    }


    ##################################
    # SCOPES
    ##################################


    /**
     * Check if User has the requested scope
     * @param $scope
     * @param $user_id
     * @return bool|array Bool or Array of scopes
     */
    public function scopeExistsForUser($scope, $user_id)
    {
        $found = [];
        $stmt = $this->db->prepare(sprintf('SELECT scope FROM %s WHERE user_id = ? LIMIT 1', $this->config['user_table']));
        $stmt->execute([$user_id]);
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $user_scopes = array_map('strtolower', explode(' ', $result['scope']));
            if (in_array(SCOPE_OWNER, $user_scopes)) {
                $found = array_keys(OAuthScopeService::$allScopes);
            } else {
                $scopes = !is_array($scope) ? explode(' ', $scope) : $scope;
                $scopes = in_array(SCOPE_OWNER, $scopes) ? array_keys(OAuthScopeService::$allScopes) : $scopes;
                foreach ($scopes as $scope) {
                    if (in_array(strtolower($scope), $user_scopes)) {
                        $found[] = $scope;
                    } else {
                        return false; // All must exist
                    }
                }
            }
        }
        return !empty($found) ? $found : false;
    }

    /**
     * Check if Client has the requested scope
     * @param $scope
     * @param $client_id
     * @param $return
     * @return bool|array Bool or Array of scopes
     */
    public function scopeExistsForClient($scope, $client_id)
    {
        $found = [];
        $stmt = $this->db->prepare(sprintf('SELECT scope FROM %s WHERE client_id = ? LIMIT 1', $this->config['client_table']));
        $stmt->execute([$client_id]);
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $client_scopes = array_map('strtolower', explode(' ', $result['scope']));
            if (in_array(SCOPE_OWNER, $client_scopes)) {
                $found = array_keys(OAuthScopeService::$allScopes);
            } else {
                $scopes = !is_array($scope) ? explode(' ', $scope) : $scope;
                $scopes = in_array(SCOPE_OWNER, $scopes) ? array_keys(OAuthScopeService::$allScopes) : $scopes;
                foreach ($scopes as $scope) {
                    if (in_array(strtolower($scope), $client_scopes)) {
                        $found[] = $scope;
                    } else {
                        return false; // All must exist
                    }
                }
            }
        }
        return !empty($found) ? $found : false;
    }

    /**
     * Get all scopes
     * @return array|mixed
     */
    public function getAllScopes()
    {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s', $this->config['scope_table']));
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
    }

    /**
     * Get all scopes as list with name as kye and description as value
     * @return array|mixed
     */
    public function getAllScopesList()
    {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s', $this->config['scope_table']));
        $stmt->execute();
        $list = [];
        if($scopes = $stmt->fetchAll(\PDO::FETCH_ASSOC)){
            foreach ($scopes as $scope) {
                $list[$scope['scope']] = $scope['description'];
            }
        }
        return $list;
    }
}
