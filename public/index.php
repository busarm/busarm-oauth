<?php
if (defined('STDIN')) {chdir(dirname(dirname(__FILE__)));} // Set the current directory correctly for CLI requests
define("OAUTH_BASE", dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR);
define("OAUTH_BASE_PATH",'../system/'); //define System path

// Initialize App
require_once OAUTH_BASE_PATH."App.php";   
(new App)->initialize();
