<?php

use Phinx\Migration\AbstractMigration;

class ModifyRadacct extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('radacct');
        $table->addColumn('device_id', 'string', ['after' => 'nasidentifier'])
            ->addIndex(['device_id'])
            ->update();
    }

    public function down()
    {
        $table = $this->table('radacct');
        $table->removeColumn('device_id')
            ->removeIndex(['device_id'])
            ->update();
    }
}
