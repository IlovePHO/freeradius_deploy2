<?php

use Phinx\Migration\AbstractMigration;

class CreateSoftEtherNetworkBridges extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->createSoftEtherNetworkBridgesTable();
        $this->createSoftEtherInterfaces();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->deleteSoftEtherNetworkBridgesTable();
        $this->deleteSoftEtherInterfaces();
    }

    private function createSoftEtherNetworkBridgesTable()
    {
        $exists = $this->hasTable('soft_ether_network_bridges');
        if (!$exists) {
            $softether_network_bridges = $this->table('soft_ether_network_bridges');
            $softether_network_bridges->addColumn('bridge_name', 'string', ['null' => false])
                                      ->addColumn('status', 'boolean', ['null' => false, 'default' => false])
                                      ->addColumn('ip_address', 'string', ['null' => false, 'limit' => 40])
                                      ->addColumn('subnet_mask', 'string', ['null' => false, 'limit' => 40])
                                      ->save();
        }
    }

    private function createSoftEtherInterfaces()
    {
        $exists = $this->hasTable('soft_ether_interfaces');
        if (!$exists) {
            $softether_interfaces = $this->table('soft_ether_interfaces');
            $softether_interfaces->addColumn('bridge_id', 'integer', ['null' => false])
                                 ->addColumn('if_name', 'string', ['null' => false])
                                 ->addColumn('tap_mode', 'boolean', ['null' => false, 'default' => false])
                                 ->save();
        }
    }

    private function deleteSoftEtherNetworkBridgesTable()
    {
        $exists = $this->hasTable('soft_ether_network_bridges');
        if ($exists) {
            $this->table('soft_ether_network_bridges')->drop()->save();
        }
    }

    private function deleteSoftEtherInterfaces()
    {
        $exists = $this->hasTable('soft_ether_interfaces');
        if ($exists) {
            $this->table('soft_ether_interfaces')->drop()->save();
        }
    }
}
