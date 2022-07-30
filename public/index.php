<?php
define('FCPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
// Prevent cli - use ../console for cli
if (PHP_SAPI === 'cli' or defined('STDIN')) exit(1);
// Load packages
require_once(boolval(getenv('SEPARATE_VENDOR')) ? '/tmp/vendor/autoload.php' : FCPATH . 'vendor/autoload.php');
require_once(FCPATH . 'bootstrap/helpers.php');
require_once(FCPATH . 'bootstrap/constants.php');

use System\App;
use Application\Exceptions\Reporter;

$app = new App();
// Add config files
$app->addConfig('scopes');
$app->addConfig('routes');
// Add error reporter
$app->addErrorReporter(new Reporter);
// Run app
$app->run();
