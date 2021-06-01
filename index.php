<?php
if (defined('STDIN')) {chdir(dirname(__FILE__));} // Set the current directory correctly for CLI requests
define("OAUTH_BASE", __DIR__.DIRECTORY_SEPARATOR, true);
define("OAUTH_BASE_PATH",'system/'); //define System path
/* Initialize composer autoloader*/
if(boolval(getenv('SEPARATE_VENDOR'))){
    require_once('/tmp/vendor/autoload.php');
}
else {
    require_once('vendor/autoload.php');
}
require_once OAUTH_BASE_PATH."App.php";   
(new App)->initialize();
