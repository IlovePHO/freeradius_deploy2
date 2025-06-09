<?php

use Phinx\Migration\AbstractMigration;

class AddJapaneseLanguage extends AbstractMigration
{
    public function up()
    {
        $language = $this->fetchRow('SELECT * FROM languages WHERE id = 5');
        if ($language == false) {
            $this->insertJapanese();
        } else if ($language['name'] != 'Japanese' || $language['iso_code'] != 'ja') {
            $this->updateId5ToJapanese();
        } else {
            // Nothing is done because the value is already there.
        }
    }

    public function down()
    {
        // At the time of rollback, nothing is done.
    }

    private function insertJapanese()
    {
        $table = $this->table('languages');
        $row = [
            'id'       => 5,
            'name'     => 'Japanese',
            'iso_code' => 'ja',
            'rtl'      => 0,
            'created'  => '2022-07-06 10:55:28',
            'modified' => '2022-07-06 10:55:28',
        ];
        $table->insert($row);
        $table->saveData();
    }

    private function updateId5ToJapanese()
    {
        $sql = "UPDATE `languages` SET `name` = 'Japanese', ".
               "`iso_code` = 'ja', `rtl` = 0 WHERE `id` = 5";
        $this->execute($sql);
    }
}
