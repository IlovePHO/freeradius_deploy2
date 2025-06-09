<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class SoftEtherLocalBridgesController extends AppController {

    public $main_model       = 'SoftEtherLocalBridges';
    public $virtual_hubs_model       = 'SoftEtherVirtualHubs';
    public $interfaces_model		= 'SoftEtherInterfaces';
    public $base    = "Access Providers/Controllers/SoftEtherLocalBridges/";

//------------------------------------------------------------------------
// public method

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel($this->virtual_hubs_model);
        $this->loadModel($this->interfaces_model);
        // $this->loadModel('Proxies');
        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');
        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => $this->main_model,
            'sort_by' => $this->main_model . '.id'
        ]);
        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        // $this->loadComponent('LifeSeed');
    }
    
    public function index(){
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query      = $this->{$this->main_model}->find();
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
                'hub_name'	=> $i['hub_name'],
                'device_name'	=> $i['device_name'], 
                'tap_mode'	=> $i['tap_mode'], 
            ));
        }
       
        $this->set(array(
            'items' => $items,
            'success' => true,
            'totalCount' => $total,
            '_serialize' => array('items','success','totalCount')
        ));
    }
 
    public function add(){
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $entity = $this->{$this->main_model}->newEntity($this->request->data());

        if ($this->{$this->main_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not add item');
            $this->JsonErrors->entityErros($entity,$message);
        }
    }
 
    public function delete() {
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

            $entity     = $this->{$this->main_model}->get($this->request->data('id'));   
            $this->{$this->main_model}->delete($entity);
        } else {                          //Assume multiple item delete
            foreach($this->request->data as $d) {
                $entity     = $this->{$this->main_model}->get($d['id']);  
                $this->{$this->main_model}->delete($entity);
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

    public function getDeviceList() {
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query = $this->{$this->interfaces_model}->find()->where(['tap_mode' => false]);

        $total = $query->count();
        $q_r = $query->all();

        $items = array();

        foreach ($q_r as $i) {
            array_push($items,array(
                'id'		=> $i['id'], 
                'bridge_id'	=> $i['bridge_id'],
                'if_name'	=> $i['if_name'], 
                'tap_mode'	=> $i['tap_mode'],
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

        $menu = $this->GridButtons->returnButtons($user, false, 'SoftEtherLocalBridges');

        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }
//------------------------------------------------------------------------
// private method

}
