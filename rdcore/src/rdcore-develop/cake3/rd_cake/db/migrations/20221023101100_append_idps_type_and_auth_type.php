<?php

use Phinx\Migration\AbstractMigration;

class AppendIdpsTypeAndAuthType extends AbstractMigration
{
    public function up()
    {
        $this->_appendIdpsTypeEnum();
    }

    public function down()
    {
    }

    private function _appendIdpsTypeEnum() {
        $sql = sprintf('SHOW TABLES LIKE "idps";');
        $idps_table = $this->fetchRow($sql);
        if ($idps_table !== false) {
            $sql = "ALTER TABLE idps MODIFY COLUMN type ENUM ".
                   "('google_workspace', 'azure_ad', 'direct')";
            $this->execute($sql);
            $sql = "ALTER TABLE idps MODIFY COLUMN auth_type ENUM ('oauth', 'none')";
            $this->execute($sql);
        }
    }
}
