<?php

use Phinx\Migration\AbstractMigration;

class ModifyVouchers extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('vouchers');
        $table->addColumn('email', 'string', ['after' => 'time_cap'])
            ->update();
    }

    public function down()
    {
        $table = $this->table('vouchers');
        $table->removeColumn('email')
            ->update();
    }
}
