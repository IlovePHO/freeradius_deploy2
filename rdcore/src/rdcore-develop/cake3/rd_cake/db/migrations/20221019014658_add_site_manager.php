<?php

use Phinx\Migration\AbstractMigration;

use Cake\I18n\Time;

class AddSiteManager extends AbstractMigration
{
    public function up()
    {
        $this->_addSiteManagers();
    }

    public function down()
    {
        // At the time of rollback, nothing is done.
    }

    private function _fetchSiteManagersGroup()
    {
        $sql = 'SELECT * FROM groups WHERE name = "Site Managers"';
        return $this->fetchRow($sql);
    }

    private function _fetchGroupAro($group_id)
    {
        $sql = sprintf('SELECT * FROM aros WHERE model = "Groups" '.
                       'AND foreign_key = %d', $group_id);
        return $this->fetchRow($sql);
    }

    private function _addSiteManagers()
    {
        $site_managers_group = $this->_fetchSiteManagersGroup();
        if ($site_managers_group === false) {
            $this->_insertSiteManagersGroup();
            $site_managers_group = $this->_fetchSiteManagersGroup();
        }

        $site_managers_aro = $this->_fetchGroupAro($site_managers_group['id']);
        if ($site_managers_aro === false) {
            $this->_insertGroupAro($site_managers_group['id']);
        }
    }

    private function _insertSiteManagersGroup()
    {
        $now = Time::now();
        $now_str = $now->i18nFormat('yyyy-MM-dd HH:mm:ss');

        $table = $this->table('groups');
        $row = [
            'name'     => 'Site Managers',
            'created'  => $now_str,
            'modified' => $now_str,
        ];
        $table->insert($row);
        $table->saveData();
    }

    private function _insertGroupAro($group_id)
    {
        $sql = 'SELECT * FROM aros ORDER by rght DESC limit 1';
        $last_aro = $this->fetchRow($sql);
        if ($last_aro === false) {
            return;
        }

        $last_max_rght = intval($last_aro['rght']);

        $table = $this->table('aros');
        $row = [
            'parent_id'   => null,
            'model'       => 'Groups',
            'foreign_key' => $group_id,
            'alias'       => null,
            'lft'         => $last_max_rght + 1,
            'rght'        => $last_max_rght + 2,
        ];
        $table->insert($row);
        $table->saveData();
    }
}
