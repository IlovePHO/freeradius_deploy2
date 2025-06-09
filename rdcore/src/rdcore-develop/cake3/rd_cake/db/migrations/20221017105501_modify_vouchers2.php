<?php

use Phinx\Migration\AbstractMigration;

class ModifyVouchers2 extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('vouchers');
        $table->addColumn('unique_id_type', 'string', ['null' => true, 'after' => 'email'])
            ->addColumn('unique_id', 'string', ['null' => true, 'after' => 'unique_id_type'])
            ->update();
    }
}
