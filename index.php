<?php

define("OAUTH_BASE", __DIR__.DIRECTORY_SEPARATOR, true);
define("OAUTH_BASE_PATH",'system/',true); //define System path
require_once 'vendor/autoload.php'; /* Initialize composer autoloader*/
require_once OAUTH_BASE_PATH."OAUTH_APP.php";   
(new OAUTH_APP)->initialize();
