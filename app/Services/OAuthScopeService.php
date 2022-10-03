<?php

namespace App\Services;

use OAuth2\Scope;
use OAuth2\Storage\Memory;

/**
 * Created by VSCode.
 * User: Samuel
 * Date: 12/7/2021
 * Time: 12:49 AM
 */
class OAuthScopeService extends Scope
{
    public static $defaultScope = SCOPE_PUBLIC;
    public static $allScopes = [
        SCOPE_OWNER => "Access everything",
        SCOPE_SYSTEM => "Access system resources",
        SCOPE_ADMIN => "Access administrator resources",
        SCOPE_STAFF => "Access staff only resources",
        SCOPE_PARTNER => "Access partner resources",
        SCOPE_AGENT => "Access agent resources",
        SCOPE_USER => "Access user resources",
        SCOPE_DEVELOPER => "Access developer only resources",
        SCOPE_TESTER => "Perform tests and access test resources",
        SCOPE_PUBLIC => "Access any publicly available resource",
        SCOPE_OPENID => "Request access to private user information",
        SCOPE_CLAIM_NAME => "Get user name",
        SCOPE_CLAIM_EMAIL => "Get user email",
        SCOPE_CLAIM_PHONE => "Get user name",
        SCOPE_CLAIM_PROFILE => "Get user profile information",
    ];

    /**
     * @var \OAuth2\Storage\ScopeInterface|\OAuth2\Storage\Memory
     */
    protected $storage;

    const CLAIM_SCOPES = [
        SCOPE_OPENID,
        SCOPE_CLAIM_NAME,
        SCOPE_CLAIM_EMAIL,
        SCOPE_CLAIM_PHONE,
        SCOPE_CLAIM_PROFILE,
    ];

    /**
     * Constructor
     * @param array $scopes ['name' => 'description'][]
     * @param string $defaultScope
     */
    public function __construct($scopes = [], $defaultScope = null)
    {
        if (!empty($scopes)) {
            self::$allScopes = array_unique(array_merge(self::$allScopes, $scopes));
        }
        if (!empty($defaultScope)) {
            self::$defaultScope = $defaultScope;
        }
        parent::__construct(new Memory(array(
            'default_scope' => self::$defaultScope,
            'supported_scopes' => array_keys(self::$allScopes)
        )));
    }

    /**
     * Check if everything in required scope is contained in available scope.
     *
     * @param string $required_scope  - A space-separated string of scopes.
     * @param string $available_scope - A space-separated string of scopes.
     * @return bool                   - TRUE if everything in required scope is contained in available scope and FALSE
     *                                  if it isn't.
     *
     * @see http://tools.ietf.org/html/rfc6749#section-7
     * @inheritDoc
     * @ingroup oauth2_section_7
     */
    public function checkScope($required_scope, $available_scope)
    {
        $required_scope = explode(' ', trim($required_scope));
        $required_scope = in_array(SCOPE_OWNER, $required_scope) ? array_keys(OAuthScopeService::$allScopes) : $required_scope;
        $available_scope = explode(' ', trim($available_scope));
        $available_scope = in_array(SCOPE_OWNER, $available_scope) ? array_keys(OAuthScopeService::$allScopes) : $available_scope;

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
        $scopes = in_array(SCOPE_OWNER, $scopes) ? array_keys(OAuthScopeService::$allScopes) : $scopes;

        $availableScopes =  array_keys(self::$allScopes);

        $found = [];
        foreach ($scopes as $scope) {
            if (in_array(trim($scope), $availableScopes)) {
                $found[$scope] = self::$allScopes[$scope];
            }
        }

        return !empty($found) ? $found : false;
    }

    /**
     * Find openid scope(s). Return with details if exists
     * @param string|array $scopes
     * @return bool|array Bool or Array of available scopes
     */
    public static function findOpenIdScope($scopes)
    {
        $scopes = !is_array($scopes) ? explode(' ', $scopes) : $scopes;
        $scopes = in_array(SCOPE_OWNER, $scopes) ? array_keys(OAuthScopeService::$allScopes) : $scopes;

        if (!in_array(SCOPE_OPENID, $scopes)) return false;

        $found = [];
        foreach ($scopes as $scope) {
            if (in_array(trim($scope), self::CLAIM_SCOPES)) {
                $found[$scope] = self::$allScopes[$scope];
            }
        }

        return !empty($found) ? $found : false;
    }
}
