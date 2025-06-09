<?php

use Phinx\Migration\AbstractMigration;

class SupportOauthForExtApi extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change() {
        $this->changeIdps();
        $this->changeIdpOauthClientCredentials();
        $this->changeIdpOauthClientTokens();
    }

    private function changeIdps() {
        $table = $this->table('idps');
        $table->addColumn('name', 'string')
              ->addColumn('type', 'enum', ['values' => 'google_workspace'])
              ->addColumn('auth_type', 'enum', ['values' => 'oauth'])
              ->addColumn('available_to_siblings', 'boolean', ['default' => false])
              ->addColumn('user_id', 'integer')
              ->addColumn('realm_id', 'integer')
              ->addColumn('domain', 'string')
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }

    private function changeIdpOauthClientCredentials() {
        $table = $this->table('idp_oauth_client_credentials');
        $table->addColumn('name', 'string')
              ->addColumn('available_to_siblings', 'boolean', ['default' => false])
              ->addColumn('user_id', 'integer')
              ->addColumn('idp_id', 'integer')
              ->addColumn('credential', 'text')
              ->addColumn('client_secret', 'string', ['null' => true])
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }

    private function changeIdpOauthClientTokens() {
        $table = $this->table('idp_oauth_client_tokens');
        $table->addColumn('user_id', 'integer')
              ->addColumn('idp_oauth_client_credential_id', 'integer')
              ->addColumn('token', 'text')
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }
}
