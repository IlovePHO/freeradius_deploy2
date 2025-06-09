<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class SoftEtherNetworkBridgesController extends AppController {

    public $network_bridges_model	= 'SoftEtherNetworkBridges';
    public $interfaces_model		= 'SoftEtherInterfaces';
    public $base    = "Access Providers/Controllers/SoftEtherNetworkBridges/";

//------------------------------------------------------------------------
// public method

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->network_bridges_model);
        $this->loadModel($this->interfaces_model);
        // $this->loadModel('Proxies');
        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');
        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        // $this->loadComponent('LifeSeed');
    }
    
    public function index(){
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query      = $this->{$this->network_bridges_model}->find();

        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => $this->network_bridges_model,
            'sort_by' => $this->network_bridges_model . '.id'
        ]);
        $this->CommonQuery->build_common_query($query, $user, ['SoftEtherInterfaces']); //AP QUERY is sort of different in a way

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
// debug($q_r);

        foreach ($q_r as $i) {
            array_push($items,array(
                'id'		=> $i['id'], 
                'bridge_name'	=> $i['bridge_name'],
                'if_name_list'	=> $i['if_name'],
                'status'	=> $i['status'], 
                'ip_address'	=> $i['ip_address'], 
                'subnet_mask'	=> $i['subnet_mask'], 
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

        $entity = $this->{$this->network_bridges_model}->newEntity($this->request->data());

        if ($this->{$this->network_bridges_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not add item');
            $this->JsonErrors->entityErros($entity,$message);
        }
    }
 
    public function editStatus(){
        $this->edit();
    }
 
    public function editAddress(){
        $this->edit();
    }
 
    public function edit(){
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $entity = $this->{$this->network_bridges_model}->get($this->request->data('id'));
        $this->{$this->network_bridges_model}->patchEntity($entity, $this->request->data());

        if ($this->{$this->network_bridges_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not update item');
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

            $entity     = $this->{$this->network_bridges_model}->get($this->request->data('id'));   
            $this->{$this->network_bridges_model}->delete($entity);
        } else {                          //Assume multiple item delete
            foreach($this->request->data as $d) {
                $entity     = $this->{$this->network_bridges_model}->get($d['id']);  
                $this->{$this->network_bridges_model}->delete($entity);
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
    
    public function indexInterfaces(){
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query = $this->{$this->interfaces_model}->find()->where(['bridge_id' => $this->request->query['network_bridge_id']]);

        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => $this->interfaces_model,
            'sort_by' => $this->interfaces_model . '.id'
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
 
    public function addInterfaces(){
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $entity = $this->{$this->interfaces_model}->find()->where(['if_name' => $this->request->data('if_name')])->first();
        $entity->bridge_id = $this->request->data('bridge_id');

        if ($this->{$this->interfaces_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not add item');
            $this->JsonErrors->entityErros($entity,$message);
        }
    }

    public function deleteInterfaces() {
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

            $entity     = $this->{$this->interfaces_model}->get($this->request->data('id'));
            $entity->bridge_id = 0;
            if (!($this->{$this->interfaces_model}->save($entity))) {
                $fail_flag = true;
            }
        } else {                          //Assume multiple item delete
            foreach($this->request->data as $d) {
                $entity     = $this->{$this->interfaces_model}->get($d['id']);  
                $entity->bridge_id = 0;
                if (!($this->{$this->interfaces_model}->save($entity))) {
                    $fail_flag = true;
                }
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

    public function getInterfaceList() {
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query = $this->{$this->interfaces_model}->find()->where(['bridge_id' => 0]);

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

        $menu = $this->GridButtons->returnButtons($user, false, 'SoftEtherNetworkBridges');

        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }
    // GUI Interfaces panel menu
    public function menuForGridInterfaces(){
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, false, 'SoftEtherInterfaces');

        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }
//------------------------------------------------------------------------
// private method

}
