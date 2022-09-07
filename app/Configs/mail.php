<?php

use function Busarm\PhpMini\Helpers\env;

/*
|--------------------------------------------------------------------------
| ADD EMAIl CONFIGS
|--------------------------------------------------------------------------
|
*/

define('SMTP_HOST', env("SMTP_HOST"));
define('SMTP_PORT', env("SMTP_PORT"));
define('SMTP_KEY', env("SMTP_KEY"));
define('SMTP_SECRET', env("SMTP_SECRET"));