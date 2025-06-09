<?php

use Phinx\Migration\AbstractMigration;

class CreateSoftEtherWireguard extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->createSoftEtherWireguardConfigs();
        $this->createSoftEtherWireguardPublicKeys();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->deleteSoftEtherWireguardConfigs();
        $this->deleteSoftEtherWireguardPublicKeys();
    }

    private function createSoftEtherWireguardConfigs()
    {
        $exists = $this->hasTable('soft_ether_wireguard_configs');
        if (!$exists) {
            $softether_wireguard_configs = $this->table('soft_ether_wireguard_configs');
            $softether_wireguard_configs->addColumn('enabled', 'boolean', ['null' => false, 'default' => false])
                                        ->addColumn('preshared_key', 'string', ['null' => false])
                                        ->addColumn('private_key', 'string', ['null' => false])
                                        ->save();

            $init_wireguard_configs_data = ['id' => null];
            $softether_wireguard_configs->insert($init_wireguard_configs_data);
            $softether_wireguard_configs->saveData();
        }
    }

    private function createSoftEtherWireguardPublicKeys()
    {
        $exists = $this->hasTable('soft_ether_wireguard_public_keys');
        if (!$exists) {
            $softether_wireguard_public_keys = $this->table('soft_ether_wireguard_public_keys');
            $softether_wireguard_public_keys->addColumn('public_key', 'string', ['null' => false])
                                            ->addColumn('hub_name', 'string', ['null' => false])
                                            ->addColumn('user_name', 'string', ['null' => false])
                                            ->save();
        }
    }

    private function deleteSoftEtherWireguardConfigs()
    {
        $exists = $this->hasTable('soft_ether_wireguard_configs');
        if ($exists) {
            $this->table('soft_ether_wireguard_configs')->drop()->save();
        }
    }

    private function deleteSoftEtherWireguardPublicKeys()
    {
        $exists = $this->hasTable('soft_ether_wireguard_public_keys');
        if ($exists) {
            $this->table('soft_ether_wireguard_public_keys')->drop()->save();
        }
    }
}
