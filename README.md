# Wecari Oauth

## Description
Wecari Authorization server. Manages user authentication and authorization to access Wecari resources

## Specification
- PHP Version - Version >= 8.0
- Framework - Custom MVC build on top of Bshaffer's Oauth 2.0 Server. https://bshaffer.github.io/oauth2-server-php-docs/
- - Oauth 2.0 - See https://auth0.com/intro-to-iam/what-is-oauth-2/ and https://oauth.net/2/
- MYSQL Version - Version >=  8.0
- Docker / Docker Compose - Version >=  3.7 https://docs.docker.com/compose/compose-file/compose-file-v3/#short-syntax-3

## External Services
### Deployment (Staging/Production)
- AWS VPC
- AWS Lambda
- AWS API Gateway
- AWS RDS
- AWS Secret Manager
### Mailing
- AWS SES (Can be replaced with any SMTP provider)
### Error Monitoring / Logging
- Bugsnag
- AWS Cloudwatch
### Security
- Google Recaptcha V3

## Security
### Authentication
- All Resources (`/resources` endpoints) are protected
- - Client Credentials `client_id` and `client_secret` should be present in header or body
- - Client must have `system` scope
- - Oauth Access Token is require for certain Resource routes. Should be present in `Authorization` header
### Authorization 
- Scope based authorization. Uses oauth jwt token scopes to grant access.

## Structure
- `application` - Contains application files used to handle server requests. Follows Model View Controller (MVC) pattern
- `database` - Holds Database ddl sql scripts
- `mysql` - Custom configurations for mysql server on development docker environment
- `nginx` - Custom configurations for nginx server on docker environment
- `php` - Custom configurations for php server on docker environment
- `public` - Contains public facing scripts and files
- - `index.php` - Public facing launch point of the app
- `system` - Contains Codeigniter system framework files
- - `Configs.php` - Contains application environment configs to be accessd anywhere
- - `Helpers.php` - Contains global helper functions
- - `App.php` - Configure and Initialize application
- - `CIPHER.php` - Encryption / Decryption helper class
- - `Scopes.php` - Manage supported oauth scopes
- - `Server.php` - Base controller class for Oauth Server. Processes Oauth requests.

## Deployemnt Steps
### Development (using Docker)
- Install Docker on system. Visit https://docs.docker.com/get-docker/
- Start Local Server (Wecari Oauth)
- - Ensure `.env` file exists. If not run copy `.env.example` to `.env`
- - Ensure `.env` file is populated with the corrent details
- - Run `docker-compose up` or `docker-compose up --scale oauth-php=<NUMBER_OF_INSTANCES>`
- For First time Deployment:
- - Before Starting Server - Generate Oauth Client Credentials for Wecari API and add to the `.env` file. Use `Wecari Oauth` console commands.
- - After Starting Server - Login to the database using the default mysql credentials in the `docker-compose.yml` file.
- - After Starting Server - If database ddl hasn't been automaticaly deployed, run the database ddl sql scripts in the `database` folder.
### Staging / Production (using Serverlsess)
- For First time  Deployment:
- - Set up VPC 
- - Ensure VPC has a private subnet which the RDS database is connected to
- - Set up RDS database.
- - Add database credentials to Secret manager
- - Add app related credentials on Secret manager
- - Set up Domain and add domain to API Gateway custom domain
- - Add VPC's Private Subnet IDs and Security Group to `serverless.yml` file
- - Login to the database using the db credentials.
- - Run the database ddl sql scripts in the `database` folder to create database adn tables.
- Run command `php deploy --dev` for staging or `php deploy --prod` for production
- - Uses Severeless Framework under the hood. See https://serverless.com

## Console Application
- Refer to `application/controllers/Console.php`
### Available Console commands
- `create_org` - Create an Organization. E.g `create_org <org_name>`
- `create_client` - Create an Oauth Client. E.g `create_client <org_id> <client_id> <client_name> <redirect_uri> <scopes> <grant_types>`
- - E.g create_client 2 "wecari_partner_223333" "Wecari Partner" "https://partner.wecari.com/hooks/authorize" "user agent partner" "password authorization_code refresh_token"
- - [CAUTION] Use Principle of Least Privilege Access when assigning scopes, as scopes are also used to define permissions.
- - [CAUTION] Assign specific grant type only if needed. E.g If the client will only be allowing user login, then it won't require, `client_credentials` grant type.
- `update_client_key` - Update an Oauth Client key pair. E.g `update_client_key <client_id>`
- `create_user` - Create a user. E.g `create_user <name> <email> <password> <scopes>`
- - [CAUTION] Use Principle of Least Privilege Access when assigning scopes, as scopes are also used to define permissions.
### Create Console commands
- Go to `application/controllers/Console.php`
- Add commands as functions. E.g `get_user` command = `public function get_user() { ... }`
### Run Console commands
- Start Local Server (See instructions above).
- Go into the application's contaner. Run `docker-compose exec oauth-workspace bash`
- Change directory to application folder. Run `cd /var/www`
- Run `php console <command> <arg1> <arg2> <arg...>`