<?php

use Phinx\Migration\AbstractMigration;

class AddSubGroups extends AbstractMigration
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
        $this->changeSubGroups();
    }

    private function changeSubGroups() {
        $table = $this->table('sub_groups');
        $table->addColumn('name', 'string')
              ->addColumn('user_id', 'integer')
              ->addColumn('realm_id', 'integer')
              ->addColumn('idp_id', 'integer')
              ->addColumn('profile', 'string', ['default' => ''])
              ->addColumn('profile_id', 'integer', ['null' => true])
              ->addColumn('unique_id', 'string', ['null' => true])
              ->addColumn('path', 'string', ['null' => true])
              ->addColumn('description', 'string', ['default' => ''])
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }
}
