<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class SoftEtherWireguardController extends AppController {

    public $wg_config_model       = 'SoftEtherWireguardConfigs';
    public $public_keys_model       = 'SoftEtherWireguardPublicKeys';
    public $virtual_hubs_model       = 'SoftEtherVirtualHubs';
    public $users_model       = 'SoftEtherUsers';
    public $base    = "Access Providers/Controllers/SoftEtherWireguard/";

//------------------------------------------------------------------------
// public method

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->wg_config_model);
        $this->loadModel($this->public_keys_model);
        $this->loadModel($this->virtual_hubs_model);
        $this->loadModel($this->users_model);
        // $this->loadModel('Proxies');
        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');
        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        // $this->loadComponent('LifeSeed');
    }
    
    public function view() {
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query = $this->{$this->wg_config_model}->find()->first();
        $data  = $query->toArray();
        unset($data['id']);

        $this->set(array(
            'data' => $data,
            'success' => true,
            '_serialize' => array('data','success')
        ));
    }
 
    public function save() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $entity = $this->{$this->wg_config_model}->find()->first();
        $this->{$this->wg_config_model}->patchEntity($entity, $this->request->data());

        if ($this->{$this->wg_config_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not update item');
            $this->JsonErrors->entityErros($entity,$message);
        }
    }
    
    public function indexPublicKey(){
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query      = $this->{$this->public_keys_model}->find();

        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => $this->public_keys_model,
            'sort_by' => $this->public_keys_model . '.id'
        ]);
        $this->CommonQuery->build_common_query($query, $user, array()); //AP QUERY is sort of different in a way

        $limit = 50;   //Defaults
        $page = 1;
        $offset = 0;
        if (isset($this->request->query['limit'])) {
            $limit = $this->request->query['limit'];
            $page = $this->request->query['page'];
            $offset = $this->request->query['start'];
        }

        $query->page($page);
        $query->limit($limit);
        $query->offset($offset);

        $total = $query->count();
        $q_r = $query->all();

        $items = array();

        foreach ($q_r as $i) {
            array_push($items,array(
                'id'		=> $i['id'], 
                'public_key'	=> $i['public_key'],
                'hub_name'	=> $i['hub_name'], 
                'user_name'	=> $i['user_name'], 
            ));
        }
       
        $this->set(array(
            'items' => $items,
            'success' => true,
            'totalCount' => $total,
            '_serialize' => array('items','success','totalCount')
        ));
    }
 
    public function addPublicKey(){
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $entity = $this->{$this->public_keys_model}->newEntity($this->request->data());

        if ($this->{$this->public_keys_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not add item');
            $this->JsonErrors->entityErros($entity,$message);
        }
    }

    public function deletePublicKey() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $fail_flag = false;

	if(isset($this->request->data['id'])) {   //Single item delete
            $message = "Single item ".$this->request->data('id');

            $entity     = $this->{$this->public_keys_model}->get($this->request->data('id'));   
            $this->{$this->public_keys_model}->delete($entity);
        } else {                          //Assume multiple item delete
            foreach($this->request->data as $d) {
                $entity     = $this->{$this->public_keys_model}->get($d['id']);  
                $this->{$this->public_keys_model}->delete($entity);
            }
        }

        if($fail_flag == true) {
            $this->set(array(
                'success'   => false,
                'message'   => array('message' => __('Could not delete some items')),
                '_serialize' => array('success','message')
            ));
        } else {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        }
    }

    public function getVirtualHubList() {
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query = $this->{$this->virtual_hubs_model}->find();
        $total = $query->count();
        $q_r = $query->all();

        $items = array();

        foreach ($q_r as $i) {
            array_push($items,array(
                'id'		=> $i['id'], 
                'hub_name'	=> $i['hub_name'],
                'password'	=> $i['password'], 
                'default_gateway'	=> $i['default_gateway'], 
                'default_subnet'	=> $i['default_subnet'], 
                'online'	=> $i['online'], 
            ));
        }
       
        $this->set(array(
            'items' => $items,
            'success' => true,
            'totalCount' => $total,
            '_serialize' => array('items','success','totalCount')
        ));
    }

    public function getUserList() {
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query = $this->{$this->users_model}->find()->where(['hub_id' => $this->request->query['virtual_hub_id']]);
        $total = $query->count();
        $q_r = $query->all();

        $items = array();

        foreach ($q_r as $i) {
            array_push($items,array(
                'id'		=> $i['id'], 
                // 'hub_id'	=> $i['hub_id'], 
                'user_name'	=> $i['user_name'],
                'real_name'	=> $i['real_name'], 
                'auth_password'	=> $i['auth_password'], 
                'note'	=> $i['note'], 
            ));
        }
       
        $this->set(array(
            'items' => $items,
            'success' => true,
            'totalCount' => $total,
            '_serialize' => array('items','success','totalCount')
        ));
    }
    // GUI panel menu
    public function menuForGrid(){
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, false, 'SoftEtherWireguardPublicKeys');

        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }

//------------------------------------------------------------------------
// private method

}
