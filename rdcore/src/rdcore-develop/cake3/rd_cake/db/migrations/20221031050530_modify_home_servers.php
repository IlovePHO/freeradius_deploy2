<?php

use Phinx\Migration\AbstractMigration;

class ModifyHomeServers extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('home_servers');
        $table->addColumn('name', 'string', ['after' => 'id'])
            ->addColumn('proto', 'string', ['after' => 'type'])
            ->addColumn('status_check', 'string', ['after' => 'port'])
            ->addColumn('priority', 'integer', ['after' => 'status_check'])
            ->addColumn('description', 'string', ['after' => 'accept_coa'])
            ->update();
    }

    public function down()
    {

    }
}
