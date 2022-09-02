<?php

namespace App\Controllers\CLI;

use App\Controllers\OAuthBaseController;
use Exception;
use Busarm\PhpMini\App;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Org extends OAuthBaseController
{
    public function __construct(private App $app)
    {
        parent::__construct(true);
    }

    /**
     * Create Admin Client
     *
     * @param string $org_name
     * @return void
     */
    public function create_org($org_name)
    {
        $result = $this->oauth->storage->setOrganizationDetails($org_name);
        if ($result) {
            $this->app->logger->debug("Successfully Added Organization");
            $this->app->logger->debug("Organizatoin ID = $result");
        } else {
            throw new Exception("Failed to create org");
        }
    }
}
