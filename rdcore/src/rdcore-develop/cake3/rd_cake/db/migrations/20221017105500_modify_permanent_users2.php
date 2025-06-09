<?php

use Phinx\Migration\AbstractMigration;

class ModifyPermanentUsers2 extends AbstractMigration
{
    public function change()
    {
        // Note that synchronization will not work for users whose unique_id is lost after a rollback.
        // Deleting the affected users will improve the behavior.
        $table = $this->table('permanent_users');
        $table->addColumn('unique_id_type', 'string', ['null' => true, 'after' => 'user_id'])
            ->update();
    }
}
