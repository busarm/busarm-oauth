<?php

// Prevent cli - use ../console.php for cli
if (PHP_SAPI === 'cli' or defined('STDIN')) {
    exit(1);
} 

// Path to the front controller (this file) directory
define('FCPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

//define System path
define("OAUTH_BASE_PATH", FCPATH . 'system/');

// Initialize vendor packages
require_once(boolval(getenv('SEPARATE_VENDOR')) ? '/tmp/vendor/autoload.php' : FCPATH . 'vendor/autoload.php');

// Initialize App
require_once OAUTH_BASE_PATH . "App.php";
(new App)->initialize();
