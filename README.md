# Wecari Oauth

## Description
Wecari Authorization server. Manages user authentication and authorization to access Wecari resources

## Specification
- PHP Version - Version >= 8.0
- Framework - Custom MVC build on top of Bshaffer's Oauth 2 Server. https://bshaffer.github.io/oauth2-server-php-docs/
- MYSQL Version - Version >=  8.0
- Docker / Docker Compose - Version >=  3.7 https://docs.docker.com/compose/compose-file/compose-file-v3/#short-syntax-3


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
- Start Wecari Oauth Server
- - Check readme.md docs in `Wecari Oauth` repo
- Start Local Server (Wecari API)
- - Ensure `.env` file exists. If not run copy `.env.example` to `.env`
- - Ensure `.env` file is populated with the corrent details
- - Run `docker-compose up` or `docker-compose up --scale oauth-php=<NUMBER_OF_INSTANCES>`
- For First time Deployment:
- - Before Starting Server - Generate Oauth Client Credentials for Wecari API and add to the `.env` file. Use `Wecari Oauth` console commands.
- - After Starting Server - Login to the database using the default mysql credentials in the `docker-compose.yml` file.
- - After Starting Server - If database ddl hasn't been automaticaly deployed, run the database ddl sql scripts in the `database` folder.
- - After Starting Server - Add neccesarry app configs to database. e.g Oauth Client Credentials for API - Refer to `application\libraries\utils\Configs.php` for list of db configs - including the oauth credentials.

### Staging / Production (using Serverlsess)
- For First time  Deployment:
- - Set up VPC 
- - Ensure VPC has a private subnet which the RDS database is connected to
- - Set up RDS database.
- - Add database credentials to Secret manager
- - Add app related credentials on Secret manager
- - Generate Oauth Client Credentials for Wecari API and add to Secret manager. Use `Wecari Oauth` console commands.
- - Set up Domain and add domain to API Gateway custom domain
- - Add VPC's Private Subnet IDs and Security Group to `serverless.yml` file
- - Login to the database using the db credentials.
- - Run the database ddl sql scripts in the `database` folder to create database adn tables.
- - Add neccesarry app configs to database. Refer to `application\libraries\utils\Configs.php` for list of db configs
- Run command `php deploy --dev` for staging or `php deploy --prod` for production


## Console Application
- Refer to `application/controllers/Console.php`
### Available Console commands
- `create_org` - Create an Organization. E.g `create_org <org_name>`
- `create_client` - Create an Oauth Client. E.g `create_client <org_id> <client_id> <client_name> <redirect_uri>`
- `update_client_key` - Update an Oauth Client key pair. E.g `update_client_key <client_id>`
- `create_user` - Create a user. E.g `create_user <name> <email> <password> <scopes>`
### Create Console commands
- Go to `application/controllers/Console.php`
- Add commands as functions. E.g `get_user` command = `public function get_user() { ... }`
### Run Console commands
- Start Local Server (See instructions above).
- Go into the application's contaner. Run `docker-compose exec oauth-php bash`
- Change directory to application folder. Run `cd /var/www`
- Run `php console <command> <arg1> <arg2> <arg...>`