<?php

use Phinx\Migration\AbstractMigration;

class CreateSoftEtherVirtualHubs extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->createSoftEtherVirtualHubsTable();
        $this->createSoftEtherUsers();
        $this->createSoftEtherSecureNATs();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->deleteSoftEtherVirtualHubsTable();
        $this->deleteSoftEtherUsers();
        $this->deleteSoftEtherSecureNATs();
    }

    private function createSoftEtherVirtualHubsTable()
    {
        $exists = $this->hasTable('soft_ether_virtual_hubs');
        if (!$exists) {
            $softether_virtual_hubs = $this->table('soft_ether_virtual_hubs');
            $softether_virtual_hubs->addColumn('hub_name', 'string', ['null' => false])
                                   ->addColumn('password', 'string', ['null' => false])
                                   ->addColumn('default_gateway', 'string', ['null' => false, 'limit' => 40])
                                   ->addColumn('default_subnet', 'string', ['null' => false, 'limit' => 40])
                                   ->addColumn('online', 'boolean', ['null' => false, 'default' => false])
                                   ->save();
        }
    }

    private function createSoftEtherUsers()
    {
        $exists = $this->hasTable('soft_ether_users');
        if (!$exists) {
            $softether_users = $this->table('soft_ether_users');
            $softether_users->addColumn('hub_id', 'integer', ['null' => false])
                            ->addColumn('user_name', 'string', ['null' => false])
                            ->addColumn('real_name', 'string', ['null' => false])
                            ->addColumn('auth_password', 'string', ['null' => false])
                            ->addColumn('note', 'text', ['null' => false])
                            ->save();
        }
    }

    private function createSoftEtherSecureNATs()
    {
        $exists = $this->hasTable('soft_ether_secure_nats');
        if (!$exists) {
            $softether_secure_nats = $this->table('soft_ether_secure_nats');
            $softether_secure_nats->addColumn('hub_id', 'integer', ['null' => false])
                                  ->addColumn('enabled', 'boolean', ['null' => false, 'default' => false])
                                  ->addColumn('ip_address', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('subnet_mask', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('mac_address', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('dhcp_enabled', 'boolean', ['null' => false, 'default' => false])
                                  ->addColumn('dhcp_lease_ip_start', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('dhcp_lease_ip_end', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('dhcp_subnet_mask', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('dhcp_expire', 'integer', ['null' => false, 'default' => 7200])
                                  ->addColumn('dhcp_gateway_address', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('dhcp_dns_server_address1', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('dhcp_dns_server_address2', 'string', ['null' => false, 'limit' => 40])
                                  ->addColumn('nat_enabled', 'boolean', ['null' => false, 'default' => false])
                                  ->save();
        }
    }

    private function deleteSoftEtherVirtualHubsTable()
    {
        $exists = $this->hasTable('soft_ether_virtual_hubs');
        if ($exists) {
            $this->table('soft_ether_virtual_hubs')->drop()->save();
        }
    }

    private function deleteSoftEtherUsers()
    {
        $exists = $this->hasTable('soft_ether_users');
        if ($exists) {
            $this->table('soft_ether_users')->drop()->save();
        }
    }

    private function deleteSoftEtherSecureNATs()
    {
        $exists = $this->hasTable('soft_ether_secure_nats');
        if ($exists) {
            $this->table('soft_ether_secure_nats')->drop()->save();
        }
    }
}
