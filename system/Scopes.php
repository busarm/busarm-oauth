<?php

use OAuth2\Scope;

defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');

/**
 * Created by VSCode.
 * User: Samuel
 * Date: 12/7/2021
 * Time: 12:49 AM
 */
class Scopes extends Scope
{
    const SCOPE_OWNER = '*';
    const SCOPE_SYSTEM = 'system';
    const SCOPE_ADMIN = 'admin';
    const SCOPE_STAFF = 'staff';
    const SCOPE_PARTNER = 'partner';
    const SCOPE_AGENT = 'agent';
    const SCOPE_USER = 'user';
    const SCOPE_DEVELOPER = 'developer';
    const SCOPE_TESTER = 'tester';
    const SCOPE_PUBLIC = 'public';
    const SCOPE_OPENID = 'openid';

    const DEFAULT_SCOPE = 'public';

    const ALL_SCOPES = [
        self::SCOPE_OWNER => "Access everything",
        self::SCOPE_SYSTEM => "Access system resources",
        self::SCOPE_ADMIN => "Access administrator resources",
        self::SCOPE_STAFF => "Access staff only resources",
        self::SCOPE_PARTNER => "Access partner resources",
        self::SCOPE_AGENT => "Access agent resources",
        self::SCOPE_USER => "Access user resources",
        self::SCOPE_DEVELOPER => "Access developer only resources",
        self::SCOPE_TESTER => "Perform tests and access test resources",
        self::SCOPE_PUBLIC => "Access any publicly available resource",
        self::SCOPE_OPENID => "View user information. E.g name, email, phone number",
    ];
  
    /**
     * Check if everything in required scope is contained in available scope.
     *
     * @param string $required_scope  - A space-separated string of scopes.
     * @param string $available_scope - A space-separated string of scopes.
     * @return bool                   - TRUE if everything in required scope is contained in available scope and FALSE
     *                                  if it isn't.
     *
     * @see http://tools.ietf.org/html/rfc6749#section-7
     *
     * @ingroup oauth2_section_7
     */
    public function checkScope($required_scope, $available_scope)
    {
        $required_scope = explode(' ', trim($required_scope));
        $required_scope = in_array(Scopes::SCOPE_OWNER, $required_scope) ? array_keys(Scopes::ALL_SCOPES) : $required_scope;
        $available_scope = explode(' ', trim($available_scope));
        $available_scope = in_array(Scopes::SCOPE_OWNER, $available_scope) ? array_keys(Scopes::ALL_SCOPES) : $available_scope;

        return (count(array_diff($required_scope, $available_scope)) == 0);
    }
    
    /**
     * Find scope(s). Return with details if exists
     * @param string|array $scopes
     * @return bool|array Bool or Array of available scopes
     */
    public static function findScope($scopes)
    {
        $scopes = !is_array($scopes) ? explode(' ', $scopes) : $scopes;
        $scopes = in_array(Scopes::SCOPE_OWNER, $scopes) ? array_keys(Scopes::ALL_SCOPES) : $scopes;

        $availableScopes =  array_keys(self::ALL_SCOPES);

        $found = [];
        foreach($scopes as $scope) {
            if(in_array(trim($scope), $availableScopes)) {
                $found[$scope] = self::ALL_SCOPES[$scope];
            }
        }

        return !empty($found) ? $found : false;
    }
}