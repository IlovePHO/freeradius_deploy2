<?php

use Phinx\Migration\AbstractMigration;

class LifeSeedMigration2 extends AbstractMigration
{
    public function up()
    {
        $home_server_pools = $this->fetchAll('SELECT * FROM home_server_pools');
        $proxy_realms = $this->fetchAll('SELECT * FROM proxy_realms');
        $this->modifyUpProxies();
        #$this->modifyUpHomeServerPools();
        $this->createProxiesRealms($proxy_realms);
        $this->deleteProxyRealms();
        $this->updateHomeServerPoolIdOfProxies($home_server_pools);
    }

    public function down()
    {
        $proxies = $this->fetchAll('SELECT * FROM proxies');
        $proxies_realms = $this->fetchAll('SELECT * FROM proxies_realms');
        $this->modifyDownProxies();
        #$this->modifyDownHomeServerPools();
        $this->createProxyRealms($proxies_realms);
        $this->deleteProxiesRealms();
        $this->updateProxyIdOfHomeServerPools($proxies);
    }

    private function modifyUpProxies()
    {
        $table = $this->table('proxies');
        $table->addColumn('user_id', 'integer', ['default' => 0, 'after' => 'id'])
            ->addColumn('home_server_pool_id', 'integer', ['default' => 0, 'after' => 'user_id'])
            ->addColumn('available_to_siblings', 'boolean', ['default' => true, 'after' => 'home_server_pool_id'])
            ->addColumn('description', 'string', ['null' => true, 'limit' => 200, 'after' => 'name'])
            ->update();
    }

    private function modifyUpHomeServerPools() {
        $table = $this->table('home_server_pools');
        $table->removeColumn('proxy_id')
            ->addColumn('available_to_siblings', 'boolean', ['null' => false, 'default' => true, 'after' => 'user_id'])
            ->update();
    }

    private function createProxiesRealms($proxy_realms) {
        $this->execute("
            CREATE TABLE IF NOT EXISTS `proxies_realms` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `proxy_id` int(11) NOT NULL,
              `realm_id` int(11) NOT NULL,
              `created` datetime NOT NULL,
              `modified` datetime NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        ");

        $table = $this->table('proxies_realms');
        foreach ($proxy_realms as $proxy_realm) {
            $row = [];
            foreach (array_keys($proxy_realm) as $key) {
                if (!preg_match('/^[\d]+$/', $key)) {
                    $row[$key] = $proxy_realm[$key];
                }
            }
            $table->insert($row);
        }
        $table->saveData();
    }

    private function deleteProxyRealms() {
        $this->table('proxy_realms')->drop()->save();
    }

    private function updateHomeServerPoolIdOfProxies($home_server_pools)
    {
        foreach ($home_server_pools as $home_server_pool) {
            $sql = sprintf("UPDATE `proxies` SET `home_server_pool_id` = %d WHERE `id` = %d",
                           $home_server_pool['id'], $home_server_pool['proxy_id']);
            $this->execute($sql);
        }
    }

    private function modifyDownProxies()
    {
        $table = $this->table('proxies');
        $table->removeColumn('user_id')
            ->removeColumn('home_server_pool_id')
            ->removeColumn('available_to_siblings')
            ->removeColumn('description')
            ->update();
    }

    private function modifyDownHomeServerPools() {
        $table = $this->table('home_server_pools');
        $table->addColumn('proxy_id', 'integer', ['after' => 'user_id'])
            ->removeColumn('available_to_siblings')
            ->update();
    }

    private function updateProxyIdOfHomeServerPools($proxies)
    {
        foreach ($proxies as $proxy) {
            $sql = sprintf("UPDATE `home_server_pools` SET `proxy_id` = %d WHERE `id` = %d",
                           $proxy['id'], $proxy['home_server_pool_id']);
            $this->execute($sql);
        }
    }

    private function createProxyRealms($proxies_realms) {
        $this->execute("
            CREATE TABLE IF NOT EXISTS `proxy_realms` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `proxy_id` int(11) NOT NULL,
              `realm_id` int(11) NOT NULL,
              `created` datetime NOT NULL,
              `modified` datetime NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        ");

        $table = $this->table('proxy_realms');
        foreach ($proxies_realms as $proxy_realm) {
            $row = [];
            foreach (array_keys($proxy_realm) as $key) {
                if (!preg_match('/^[\d]+$/', $key)) {
                    $row[$key] = $proxy_realm[$key];
                }
            }
            $table->insert($row);
        }
        $table->saveData();
    }

    private function deleteProxiesRealms() {
        $this->table('proxies_realms')->drop()->save();
    }
}
