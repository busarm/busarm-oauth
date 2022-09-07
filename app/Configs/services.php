<?php

use function Busarm\PhpMini\Helpers\env;

/*
|--------------------------------------------------------------------------
| ADD EXTERNAL SERVICES CONFIGS
|--------------------------------------------------------------------------
|
*/

define('BUGSNAG_KEY', env("BUGSNAG_KEY"));
define('RECAPTCHA_SECRET_KEY', env("RECAPTCHA_SECRET_KEY"));
define('RECAPTCHA_CLIENT_KEY', env("RECAPTCHA_CLIENT_KEY"));