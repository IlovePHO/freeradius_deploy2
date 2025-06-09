<?php

use Phinx\Migration\AbstractMigration;

class CreateSoftEtherL2tpIpsec extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $exists = $this->hasTable('soft_ether_l2tp_ipsec');
        if (!$exists) {
            $softether_l2tp_ipsec = $this->table('soft_ether_l2tp_ipsec');
            $softether_l2tp_ipsec->addColumn('l2tp_ipsec_enabled', 'boolean', ['null' => false, 'default' => false])
                                 ->addColumn('ipsec_secret', 'string', ['null' => false])
                                 ->addColumn('l2tp_defaulthub', 'string', ['null' => false])
                                 ->save();

            $init_l2tp_ipsec_data = ['id' => null];
            $softether_l2tp_ipsec->insert($init_l2tp_ipsec_data);
            $softether_l2tp_ipsec->saveData();
        }
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $exists = $this->hasTable('soft_ether_l2tp_ipsec');
        if ($exists) {
            $this->table('soft_ether_l2tp_ipsec')->drop()->save();
        }
    }
}
