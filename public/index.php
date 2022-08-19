<?php
// Define start time
define('APP_START_TIME', floor(microtime(true) * 1000));

// Load packages
define('APP_BASE_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
require_once(boolval(getenv('SEPARATE_VENDOR')) ? '/tmp/vendor/autoload.php' : APP_BASE_PATH . 'vendor/autoload.php');

use System\App;
use App\Exceptions\Reporter;
use App\Helpers\Request;
use App\Helpers\Response;
use System\Env;

// Bootstrap system
App::bootstrap();

// Get Application Environment
if (env('ENV') == Env::PROD || strtolower(env('ENV')) == "prod" || strtolower(env('STAGE')) == "prod") {
    $env = Env::PROD;
} else if (env('ENV') == Env::TEST || strtolower(env('ENV')) == "dev" || strtolower(env('STAGE')) == "dev") {
    $env = Env::TEST;
} else {
    $env = Env::DEV;
}

// Iniitalize App
$app = new App($env);
// Add config files
$app->loadConfig('scopes');
$app->loadConfig('routes');
// Add hooks
$app->beforeStart(function (App $app) {
    // If offline or on maintenance mode
    if (!empty(SYSTEM_START_UP_TIME) && !empty(SYSTEM_SHUT_DOWN_TIME)) {
        $start = new DateTime(SYSTEM_START_UP_TIME);
        $stop = (new DateTime(SYSTEM_SHUT_DOWN_TIME))->sub(DateInterval::createFromDateString('1 day'));
        if (time() < $start->getTimestamp() && time() >= $stop->getTimestamp()) {
            if (MAINTENANCE_MODE) {
                $app->showMessage(503, "System is under maintenance. Please come back on " . $start->format('Y-m-d H:i P'));
            } else {
                $app->showMessage(503, "System is currently offline. Please come back on " . $start->format('Y-m-d H:i P'));
            }
        }
    } else if (MAINTENANCE_MODE) {
        $app->showMessage(503, "System is under maintenance");
    }
});
// Add error reporter
$app->addErrorReporter(new Reporter);
// Run app
$app->run(new Request, new Response);
