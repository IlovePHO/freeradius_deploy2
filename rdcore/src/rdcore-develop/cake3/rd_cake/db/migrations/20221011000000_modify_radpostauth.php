<?php

use Phinx\Migration\AbstractMigration;

class ModifyRadpostauth extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('radpostauth');
        $table->addColumn('device_id', 'string', ['after' => 'nasname'])
            ->update();
    }

    public function down()
    {
        $table = $this->table('radpostauth');
        $table->removeColumn('device_id')
            ->update();
    }
}
