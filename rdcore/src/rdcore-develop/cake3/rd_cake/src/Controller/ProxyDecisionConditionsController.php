<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class ProxyDecisionConditionsController extends AppController {

    public $main_model       = 'ProxyDecisionConditions';
    public $base    = "Access Providers/Controllers/ProxyDecisionConditions/";

//------------------------------------------------------------------------

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel('Proxies');
        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed', ['models' => ['Proxies']]);
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

    private function modifyEmptyToNull($value) {
        if (isset($value) && $value == "") {
            return null;
        } else {
            return $value;
        }
    }

    private function modifyEmptyUserNameRegex() {
        if (isset($this->request->data['user_name_regex'])) {
            $this->request->data['user_name_regex'] =
                $this->modifyEmptyToNull(
                    $this->request->data['user_name_regex']);
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

        $this->complementIdByName('query');
        if (isset($this->request->query['proxy_id'])) {
            $query->where(["ProxyDecisionConditions.proxy_id" =>
                $this->request->query['proxy_id']]);
        }

        $this->LifeSeed->index($user, $query, ['proxy' => ['name']]);
    }

    public function add() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        if (isset($this->request->data['user_name_regex'])) {
            $this->request->data['user_name_regex'] =
                $this->modifyEmptyToNull($this->request->data['user_name_regex']);
        }

        $this->complementIdByName();
        $this->modifyEmptyUserNameRegex();
        $this->LifeSeed->add($user);
    }

    public function edit() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        if (isset($this->request->data['user_name_regex'])) {
            $this->request->data['user_name_regex'] =
                $this->modifyEmptyToNull($this->request->data['user_name_regex']);
        }

        $this->complementIdByName();
        $this->modifyEmptyUserNameRegex();
        $this->LifeSeed->edit($user);
    }

    public function delete($id = null) {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->LifeSeed->delete($user, $id);
    }
    public function menuForGrid(){
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, false, 'ProxyDecisionConditions'); 
        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }

    public function view(){
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        
        $data = [];

        if(null !== $this->request->getQuery('proxy_decision_condition_id')){
            $q_r = $this->{$this->main_model}->find()
                    ->where(['ProxyDecisionConditions.id' => $this->request->getQuery('proxy_decision_condition_id')])
                    ->first();
            //print_r($q_r);
            if($q_r){
                $data = $q_r;
            }
            

        }

        $this->set([
            'data'   => $data, //For the form to load we use data instead of the standard items as for grids
            'success' => true,
            '_serialize' => ['success','data']
        ]);

    }
}
