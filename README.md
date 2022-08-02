# Busarm Oauth

## Description

Busarm Authorization server. Manages user authentication and authorization to access Busarm resources

## Specification

-   PHP Version - Version >= 8.0
-   Framework - Custom MVC build on top of Bshaffer's Oauth 2.0 Server. https://bshaffer.github.io/oauth2-server-php-docs/
    -   OAuth 2.0 - See https://auth0.com/intro-to-iam/what-is-oauth-2/ and https://oauth.net/2/
-   MYSQL Version - Version >= 8.0
-   Docker / Docker Compose - Version >= 3.7 https://docs.docker.com/compose/compose-file/compose-file-v3/#short-syntax-3

## Documentation

### API

https://documenter.getpostman.com/view/20461972/UVyytYJ5

## External Services

### Deployment (Staging/Production)

-   AWS VPC
-   AWS Lambda
-   AWS API Gateway
-   AWS RDS
-   AWS Secret Manager

### Mailing

-   AWS SES (Can be replaced with any SMTP provider)

### Error Monitoring / Logging

-   Bugsnag
-   AWS Cloudwatch

### Security

-   Google Recaptcha V3

## Security

### Authentication

-   All Resources (`/resources` endpoints) are protected
    -   Client Credentials `client_id` and `client_secret` should be present in header or body
    -   Client must have `system` scope
    -   Oauth Access Token is require for certain Resource routes. Should be present in `Authorization` header

### Authorization

-   Scope based authorization. Uses oauth jwt token scopes to grant access.

## Structure

-   `app` - Contains application files used to handle server requests. Follows Model View Controller (MVC) pattern
-   `bootstrap` - Contains scripts to start up the application
-   `database` - Holds database setups and migrations
-   `mysql` - Custom configurations for mysql server on development docker environment
-   `nginx` - Custom configurations for nginx server on docker environment
-   `php` - Custom configurations for php server on docker environment
-   `public` - Contains public facing scripts and files
    -   `index.php` - Public facing launch point of the app
-   `system` - Contains system framework files
-   `workspace` - Contains docker set up for console workspace environment

## Database Migration

-   Uses [Phinx by Cake PHP](https://book.cakephp.org/phinx/)

### Console migration script

-   `php console migrate` - Run migrations for all databases
-   `php console rollback` - Run migrations rollback for database

### Composer migration scripts

-   `composer migration:create` - Runs the migration:create script as defined in composer.json.
-   `composer migration:generate` - Runs the migration:generate script as defined in composer.json.
-   `composer migration:migrate` - Runs the migration:migrate script as defined in composer.json.
-   `composer migration:migrate-fake` - Runs the migration:migrate-fake script as defined in composer.json.
-   `composer migration:rollback` - Runs the migration:rollback script as defined in composer.json.
-   `composer migration:seed:create` - Runs the migration:seed:create script as defined in composer.json.
-   `composer migration:seed:run` - Runs the migration:seed:run script as defined in composer.json.
-   `composer migration:status` - Runs the migration:status script as defined in composer.json.

## Development Instructions

### Database

-   DON'T USE COMPOSITE KEYS. Composite keys are not supported my a lot of services, hence use a single primary key and make the intended composit keys a unique index

## Deployemnt Steps

### Development (using Docker)

-   Install Docker on system. Visit https://docs.docker.com/get-docker/
-   Set up Environment Variables
    -   Ensure `.env` file exists. If not run copy `.env.example` to `.env`
    -   Ensure `.env` file is populated with the corrent details
-   Start Local Server (Busarm Oauth)
    -   Run `docker-compose up` or `docker-compose up --scale oauth-php=<NUMBER_OF_INSTANCES>`
-   For First time Deployment:
-   Set Up Local Server
    -   Access php container. Run `docker-compose exec api-php bash`
    -   Go to app folder. Run `cd /var/www`
    -   Install composer. Run `composer install`
-   Set up database
    -   Run migration script
        -   Access workspace container. Run `docker-compose exec oauth-workspace bash`
        -   Go to app folder. Run `cd /var/www`
        -   Run migration. Run `php console migrate`

### Staging / Production (using Serverlsess)

-   For First time Deployment:
    -   Set up Cloud environment (AWS):
        -   Set up VPC
        -   Ensure VPC has a private subnet which the RDS database is connected to
        -   Set up RDS database.
        -   Add database credentials to Secret manager
        -   Add app related credentials on Secret manager
        -   Set up Domain and add domain to API Gateway custom domain
        -   Add VPC's Private Subnet IDs and Security Group to `serverless.yml` file
    -   Set up Serverless on PC. Visit https://serverless.com
        -   Configure Serverless credentials with profile name `busarm`
-   Set up database
-   Set up AWS Codepipeline for auto serverless deployment
    -   See https://github.com/Busarm/busarm-aws-cf/blob/master/cicd-build-ext.yaml
-   Push changes to repo.
    -   Push to `develop` branch for Staging
    -   Push to `master` branch for Production

## Console Application

### Create Console commands

-   Go to `/console`
-   Add a new command

```php
.....
$app->addCommands([
    ....,
    new \Symfony\Component\Console\Command\Command(),
]);
```

### Run Console commands (Locally)

-   Start Local Server (See instructions above).
-   Go into the application's contaner. Run `docker-compose exec oauth-workspace bash`
-   Change directory to application folder. Run `cd /var/www`
-   List available commands `php console list`
-   Get command help `php console <command> --help`
-   Run `php console <command> <arg1> <arg2> <arg...>`

### Run Console commands (Staging/Production)

-   Set up Serverless profile.
    -   See https://www.serverless.com/framework/docs/providers/aws/cli-reference/config-credentials
-   Run command: `composer bref:cli -- -p <PROFILE> -r <REGION> <SERVICE NAME>-<STAGE>-<FUNCTION> -- <command> <arg1> <arg2> <arg...>` .
    -   e.g `composer bref:cli -- -p busarm -r eu-west-2 Busarm-Oauth-Dev-Console -- client:create --org=1 --name="Test APP" --redirect="https://app.test.com/hook/oauth/authorize" --scope="openid name email phone public user tester" --grants="password client_credential refresh_token authorization_code"`
