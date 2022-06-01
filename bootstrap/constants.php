<?php
require_once "helpers.php";

// Define environment variables
define("ENV_DEV", "development");
define("ENV_PROD", "production");
define("ENV_TEST", "testing");

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
