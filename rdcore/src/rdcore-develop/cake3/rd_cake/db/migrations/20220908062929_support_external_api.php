<?php

use Phinx\Migration\AbstractMigration;

class SupportExternalApi extends AbstractMigration
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
    public function change()
    {
        $this->changeExternalApiKeys();
        $this->changeEncodingSchemes();
        $this->changeStaConfigs();
        $this->changeStaConfigsRealms();
        $this->changeStaConfigsSubGroups();
        $this->changeStaInfos();
        $this->changeStaConfigsStaInfos();
    }

    private function changeExternalApiKeys() {
        $table = $this->table('external_api_keys');
        $table->addColumn('name', 'string')
              ->addColumn('user_id', 'integer')
              ->addColumn('realm_id', 'integer')
              ->addColumn('profile_id', 'integer')
              ->addColumn('api_key', 'string')
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }

    private function changeEncodingSchemes() {
        // Indicates key generation and expiration date.
        // Common among multiple realms.
        $table = $this->table('encoding_schemes');
        $table->addColumn('name', 'string')
              ->addColumn('suffix', 'string')
              ->addColumn('expire', 'datetime')
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }

    private function changeStaConfigs() {
        // Stores connection information to StaConfig.
        // This is set for each realm or subgroup.
        $table = $this->table('sta_configs');
        $table->addColumn('name', 'string')
              ->addColumn('user_id', 'integer')
              ->addColumn('available_to_siblings', 'boolean', ['default' => false])
              ->addColumn('ssid', 'string', ['null' => true])
              ->addColumn('eap_method', 'string')
              ->addColumn('home_domain', 'string')
              ->addColumn('rcoi', 'string')
              ->addColumn('friendly_name', 'string')
              ->addColumn('encoding_scheme_id', 'integer')
              ->addColumn('expire', 'datetime')
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }

    private function changeStaConfigsRealms() {
        // Realm and StaConfig connection settings are tied together.
        // Users who belong to a realm are candidates for the settings
        // available when creating/updating eap-config profiles.
        $table = $this->table('sta_configs_realms');
        $table->addColumn('sta_config_id', 'integer')
              ->addColumn('realm_id', 'integer')
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }

    private function changeStaConfigsSubGroups() {
        // Tie subgroups to StaConfig connection settings.
        // Users who are members of subgroups are candidates for
        // available settings when creating/updating eap-config profiles.
        $table = $this->table('sta_configs_sub_groups');
        $table->addColumn('sta_config_id', 'integer')
              ->addColumn('sub_group_id', 'integer')
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }

    private function changeStaInfos() {
        // Information about the published eap-config
        // profile is stored for each user's device.
        $table = $this->table('sta_infos');
        $table->addColumn('device_type', 'string')
              ->addColumn('device_unique_id', 'string')
              ->addColumn('short_unique_id', 'string')
              ->addColumn('device_token', 'string')
              ->addColumn('permanent_user_id', 'integer', ['null' => true, 'default' => null])
              ->addColumn('voucher_id', 'integer', ['null' => true, 'default' => null])
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }

    private function changeStaConfigsStaInfos() {
        // Added to allow multiple sta_configs to be associated with one sta_info.
        $table = $this->table('sta_configs_sta_infos');
        $table->addColumn('sta_config_id', 'integer')
              ->addColumn('sta_info_id', 'integer')
              ->addColumn('salt', 'string')
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }
}
