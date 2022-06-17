<?php

use Database\BaseMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class App extends BaseMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->execute("ALTER DATABASE CHARACTER SET 'latin1';");
        $this->execute("ALTER DATABASE COLLATE='latin1_swedish_ci';");

        $this->table('oauth_access_tokens', [
                'id' => false,
                'primary_key' => ['access_token'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('access_token', 'string', [
                'null' => false,
                'limit' => 767,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('client_id', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'access_token',
            ])
            ->addColumn('user_id', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'client_id',
            ])
            ->addColumn('expires', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'user_id',
            ])
            ->addColumn('scope', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'expires',
            ])
            ->addColumn('date_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'scope',
            ])
            ->addIndex(['client_id'], [
                'name' => 'token_client_fk_idx',
                'unique' => false,
            ])
            ->addIndex(['user_id'], [
                'name' => 'token_user_fk_idx',
                'unique' => false,
            ])
            ->addForeignKey('client_id', 'oauth_clients', 'client_id', [
                'constraint' => 'token_client_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'oauth_users', 'user_id', [
                'constraint' => 'token_user_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->create();

        $this->table('oauth_authorization_codes', [
                'id' => false,
                'primary_key' => ['authorization_code'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('authorization_code', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('client_id', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'authorization_code',
            ])
            ->addColumn('user_id', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'client_id',
            ])
            ->addColumn('redirect_uri', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'user_id',
            ])
            ->addColumn('expires', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'redirect_uri',
            ])
            ->addColumn('scope', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'expires',
            ])
            ->addColumn('id_token', 'string', [
                'null' => true,
                'limit' => 1024,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'scope',
            ])
            ->addIndex(['client_id'], [
                'name' => 'authorization_client_fk_idx',
                'unique' => false,
            ])
            ->addIndex(['user_id'], [
                'name' => 'authorization_user_fk_idx',
                'unique' => false,
            ])
            ->addForeignKey('client_id', 'oauth_clients', 'client_id', [
                'constraint' => 'authorization_client_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'oauth_users', 'user_id', [
                'constraint' => 'authorization_user_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->create();

        $this->table('oauth_clients', [
                'id' => false,
                'primary_key' => ['client_id'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('client_id', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('client_name', 'string', [
                'null' => true,
                'limit' => 45,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'client_id',
            ])
            ->addColumn('client_secret', 'string', [
                'null' => true,
                'limit' => 1024,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'client_name',
            ])
            ->addColumn('org_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'client_secret',
            ])
            ->addColumn('redirect_uri', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'org_id',
            ])
            ->addColumn('grant_types', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'redirect_uri',
            ])
            ->addColumn('scope', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'grant_types',
            ])
            ->addColumn('user_id', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'scope',
            ])
            ->addColumn('date_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'user_id',
            ])
            ->addColumn('issue_jwt', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'date_created',
            ])
            ->addIndex(['user_id'], [
                'name' => 'client_user_fk_idx',
                'unique' => false,
            ])
            ->addIndex(['org_id'], [
                'name' => 'client_org_fk_idx',
                'unique' => false,
            ])
            ->addForeignKey('org_id', 'oauth_organizations', 'org_id', [
                'constraint' => 'client_org_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'oauth_users', 'user_id', [
                'constraint' => 'client_user_fk',
                'update' => 'NO_ACTION',
                'delete' => 'NO_ACTION',
            ])
            ->create();

        $this->table('oauth_jti', [
                'id' => false,
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('issuer', 'string', [
                'null' => false,
                'limit' => 80,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('subject', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'issuer',
            ])
            ->addColumn('audiance', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'subject',
            ])
            ->addColumn('expires', 'timestamp', [
                'null' => false,
                'after' => 'audiance',
            ])
            ->addColumn('jti', 'string', [
                'null' => false,
                'limit' => 2000,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'expires',
            ])
            ->create();

        $this->table('oauth_jwt', [
                'id' => false,
                'primary_key' => ['client_id'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('client_id', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('subject', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'client_id',
            ])
            ->addColumn('public_key', 'string', [
                'null' => false,
                'limit' => 4096,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'subject',
            ])
            ->addForeignKey('client_id', 'oauth_clients', 'client_id', [
                'constraint' => 'jwt_client_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->create();

        $this->table('oauth_organizations', [
                'id' => false,
                'primary_key' => ['org_id'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('org_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
            ])
            ->addColumn('org_name', 'string', [
                'null' => true,
                'limit' => 45,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'org_id',
            ])
            ->addColumn('date_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'org_name',
            ])
            ->addColumn('logo', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'date_created',
            ])
            ->create();

        $this->table('oauth_public_keys', [
                'id' => false,
                'primary_key' => ['client_id'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('client_id', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('public_key', 'string', [
                'null' => true,
                'limit' => 4096,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'client_id',
            ])
            ->addColumn('private_key', 'string', [
                'null' => true,
                'limit' => 4096,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'public_key',
            ])
            ->addColumn('encryption_algorithm', 'string', [
                'null' => true,
                'default' => 'RS256',
                'limit' => 100,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'private_key',
            ])
            ->addForeignKey('client_id', 'oauth_clients', 'client_id', [
                'constraint' => 'publick_keys_client_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->create();

        $this->table('oauth_refresh_tokens', [
                'id' => false,
                'primary_key' => ['refresh_token'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('refresh_token', 'string', [
                'null' => false,
                'limit' => 512,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('client_id', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'refresh_token',
            ])
            ->addColumn('user_id', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'client_id',
            ])
            ->addColumn('expires', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'user_id',
            ])
            ->addColumn('scope', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'expires',
            ])
            ->addIndex(['client_id'], [
                'name' => 'refresh_client_fk_idx',
                'unique' => false,
            ])
            ->addIndex(['user_id'], [
                'name' => 'refresh_user_fk_idx',
                'unique' => false,
            ])
            ->addForeignKey('client_id', 'oauth_clients', 'client_id', [
                'constraint' => 'refresh_client_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'oauth_users', 'user_id', [
                'constraint' => 'refresh_user_fk',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->create();

        $this->table('oauth_scopes', [
                'id' => false,
                'primary_key' => ['scope'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('scope', 'string', [
                'null' => false,
                'limit' => 80,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('type', 'string', [
                'null' => true,
                'limit' => 45,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'scope',
            ])
            ->addColumn('description', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'type',
            ])
            ->addColumn('is_default', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'description',
            ])
            ->create();

        $this->table('oauth_users', [
                'id' => false,
                'primary_key' => ['user_id'],
                'engine' => 'InnoDB',
                'encoding' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('user_id', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
            ])
            ->addColumn('name', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'user_id',
            ])
            ->addColumn('email', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'name',
            ])
            ->addColumn('dial_code', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'email',
            ])
            ->addColumn('phone', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'dial_code',
            ])
            ->addColumn('password', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'phone',
            ])
            ->addColumn('salt', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'password',
            ])
            ->addColumn('scope', 'text', [
                'null' => false,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'collation' => 'latin1_swedish_ci',
                'encoding' => 'latin1',
                'after' => 'salt',
            ])
            ->addColumn('date_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'scope',
            ])
            ->addColumn('cred_updated_at', 'timestamp', [
                'null' => true,
                'after' => 'date_created',
            ])
            ->addIndex(['email'], [
                'name' => 'email_UNIQUE',
                'unique' => true,
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
