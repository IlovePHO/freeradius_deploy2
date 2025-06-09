<?php

use Phinx\Migration\AbstractMigration;

class CreateSoftEtherLocalBridges extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $exists = $this->hasTable('soft_ether_local_bridges');
        if (!$exists) {
            $softether_local_bridges = $this->table('soft_ether_local_bridges');
            $softether_local_bridges->addColumn('hub_name', 'string', ['null' => false])
                                    ->addColumn('device_name', 'string', ['null' => false])
                                    ->addColumn('tap_mode', 'boolean', ['null' => false, 'default' => false])
                                    ->save();
        }
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $exists = $this->hasTable('soft_ether_local_bridges');
        if ($exists) {
            $this->table('soft_ether_local_bridges')->drop()->save();
        }
    }
}
