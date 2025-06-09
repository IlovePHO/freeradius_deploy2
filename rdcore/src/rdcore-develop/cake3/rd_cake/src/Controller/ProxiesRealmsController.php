<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class ProxiesRealmsController extends AppController {

    public $main_model       = 'ProxiesRealms';
    public $base    = "Access Providers/Controllers/ProxiesRealms/";

//------------------------------------------------------------------------

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel('Proxies');
        $this->loadModel('Realms');
        $this->loadComponent('Aa');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed');
    }

    //____ BASIC CRUD Actions Manager ________

    private function complementIdByName($target = 'data') {
        $modified = false;

        if ($target == 'data') {
            $target_obj = $this->request->data;
        } else {
            $target_obj = $this->request->query;
        }

        if (!isset($target_obj['proxy_id'])) {
            if (isset($target_obj['proxy_name'])) {
                $query = $this->Proxies->find();
                $query->where(["Proxies.name" => $target_obj['proxy_name']]);
                $proxy = $query->first();
                if (isset($proxy)) {
                    $target_obj['proxy_id'] = $proxy->id;
                    $modified = true;
                }
            }
        }

        if (!isset($target_obj['realm_id'])) {
            if (isset($target_obj['realm_name'])) {
                $query = $this->Realms->find()->where(function ($exp, $q) {
                    return $exp->or([
                        'name' => $target_obj['realm_name'],
                        'suffix' => $target_obj['realm_name'],
                    ]);
                });
                $realm = $query->first();
                if (isset($realm)) {
                    $target_obj['realm_id'] = $realm->id;
                    $modified = true;
                }
            }
        }

        if ($modified) {
            if ($target == 'data') {
                $this->request->data = $target_obj;
            } else {
                $this->request->query = $target_obj;
            }
        }
    }

    public function index() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $query = $this->{$this->main_model}->find();
        $query->contain('Proxies');
        $query->contain('Realms');

        $this->complementIdByName('query');
        if (isset($this->request->query['proxy_id'])) {
            $query->where(["ProxiesRealms.proxy_id" => $this->request->query['proxy_id']]);
        }
        if (isset($this->request->query['realm_id'])) {
            $query->where(["ProxiesRealms.realm_id" => $this->request->query['realm_id']]);
        }

        $this->LifeSeed->index($user, $query,
            ['proxy' => ['name'], 'realm' => ['name']]);
    }

    public function add() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        if (isset($this->request->data['proxy_id']) && isset($this->request->data['realm_id'])) {
            //ProxyOperation
            $this->{$this->main_model}->deleteAll([
                "ProxiesRealms.proxy_id" => $this->request->data['proxy_id'],
                "ProxiesRealms.realm_id" => $this->request->data['realm_id']
            ]);
        }
        
        $this->complementIdByName();
        $this->LifeSeed->add($user);
    }

    public function edit() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->complementIdByName();
        $this->LifeSeed->edit($user);
    }

    public function delete($id = null) {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        
        if( !empty($id)){
            $this->LifeSeed->delete($id);
            return;
        } else if (isset($this->request->data['proxy_id']) && isset($this->request->data['realm_id'])) {
            //ProxyOperation
            $this->{$this->main_model}->deleteAll([
                "ProxiesRealms.proxy_id" => $this->request->data['proxy_id'],
                "ProxiesRealms.realm_id" => $this->request->data['realm_id']
            ]);

        }
        $this->set(array(
            'success'       => true,
            '_serialize'    => array('success')
        ));
    }
}
