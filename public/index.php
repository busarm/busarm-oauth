<?php
// Define start time
define('APP_START_TIME', floor(microtime(true) * 1000));
// Load packages
define('APP_BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once(boolval(getenv('SEPARATE_VENDOR')) ? '/tmp/vendor/autoload.php' : APP_BASE_PATH . 'vendor/autoload.php');

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Enums\Env;
use App\Exceptions\Reporter;

use function Busarm\PhpMini\Helpers\env;

// Get Application Environment
if (env('ENV') == Env::PROD || strtolower(env('ENV')) == "prod" || strtolower(env('STAGE')) == "prod") {
    $env = Env::PROD;
} else if (env('ENV') == Env::TEST || strtolower(env('ENV')) == "dev" || strtolower(env('STAGE')) == "dev") {
    $env = Env::TEST;
} else {
    $env = Env::DEV;
}

// Iniitalize App
$config = (new Config)
    ->setAppPath(APP_BASE_PATH . 'app')
    ->setConfigPath('Configs')
    ->setViewPath('Views');
$app = new App($config, $env);
// Add config files
$app->loadConfig('app');
$app->loadConfig('database');
$app->loadConfig('mail');
$app->loadConfig('services');
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
$app->setErrorReporter(new Reporter);
// Run app
$app->run();
