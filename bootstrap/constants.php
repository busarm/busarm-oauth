<?php
require_once "helpers.php";

// Define environment variables
const ENV_DEV = "development";
const ENV_PROD = "production";
const ENV_TEST = "testing";

// Regions
const REGION_DEV = "eu-west-2";
const REGION_PROD = "eu-west-2";

// Stages
const STAGE_DEV = "Dev";
const STAGE_PROD = "Prod";

// Allowed Branches
const BRANCHES_PROD = [
    'master',
    'main',
    'production',
    'release-*',
];
const BRANCHES_DEV = [
    'staging',
    'develop',
    'development',
    '*-develop',
    '*-dev',
];

// Application Environment
if (env('ENV') == ENV_PROD || strtolower(env('ENV')) == "prod" || strtolower(env('STAGE')) == "prod") {
    define('ENVIRONMENT', ENV_PROD);
} else if (env('ENV') == ENV_TEST || strtolower(env('ENV')) == "dev" || strtolower(env('STAGE')) == "dev") {
    define('ENVIRONMENT', ENV_TEST);
} else {
    define('ENVIRONMENT', ENV_DEV);
}