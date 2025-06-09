<?php

use Phinx\Migration\AbstractMigration;

class ModifyPermanentUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        // Note that synchronization will not work for users whose unique_id is lost after a rollback.
        // Deleting the affected users will improve the behavior.
        $this->changePermanentUsers();
    }

    private function changePermanentUsers()
    {
        $table = $this->table('permanent_users');
        $table->addColumn('unique_id', 'string', ['null' => true, 'after' => 'user_id'])
            ->addColumn('sub_group_id', 'integer', ['null' => true, 'after' => 'unique_id'])
            ->update();
    }
}
