<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class SoftEtherL2tpController extends AppController {

    public $main_model       = 'SoftEtherL2tpIpsec';
    public $virtual_hubs_model       = 'SoftEtherVirtualHubs';
    public $base    = "Access Providers/Controllers/SoftEtherL2tp/";

//------------------------------------------------------------------------
// public method

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel($this->virtual_hubs_model);
        // $this->loadModel('Proxies');
        $this->loadComponent('Aa');
        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => $this->main_model,
            'sort_by' => $this->main_model . '.id'
        ]);
        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        // $this->loadComponent('LifeSeed');
    }
    
    public function view() {
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query = $this->{$this->main_model}->find()->first();
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

        $entity = $this->{$this->main_model}->find()->first();
        $this->{$this->main_model}->patchEntity($entity, $this->request->data());

        if ($this->{$this->main_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not update item');
            $this->JsonErrors->entityErros($entity,$message);
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

//------------------------------------------------------------------------
// private method

}
