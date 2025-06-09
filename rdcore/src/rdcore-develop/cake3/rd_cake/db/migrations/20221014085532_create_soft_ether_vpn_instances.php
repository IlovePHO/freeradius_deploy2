<?php

use Phinx\Migration\AbstractMigration;

class CreateSoftEtherVpnInstances extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $exists = $this->hasTable('soft_ether_vpn_instances');
        if (!$exists) {
            $soft_ether_vpn_instances = $this->table('soft_ether_vpn_instances');
            $soft_ether_vpn_instances->addColumn('ip_address', 'string', ['null' => false])
                                     ->addColumn('admin_name', 'string', ['null' => false])
                                     ->addColumn('password', 'string', ['null' => false])
                                     ->addColumn('config_hash_value', 'string', ['null' => false])
                                     //->addIndex('ip_address', ['unique' => true])
                                     ->save();
        }
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $exists = $this->hasTable('soft_ether_vpn_instances');
        if ($exists) {
            $this->table('soft_ether_vpn_instances')->drop()->save();
        }
    }
}
