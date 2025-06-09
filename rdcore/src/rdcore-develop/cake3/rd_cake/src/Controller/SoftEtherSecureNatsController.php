<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class SoftEtherSecureNatsController extends AppController {

    public $main_model       = 'SoftEtherSecureNats';
    public $base    = "Access Providers/Controllers/SoftEtherSecureNats/";

//------------------------------------------------------------------------
// public method

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
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

        $query = $this->{$this->main_model}->find()->where(['hub_id' => $this->request->query['virtual_hub_id']])->first();
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

        $entity = $this->{$this->main_model}->find()->where(['hub_id' => $this->request->data('hub_id')])->first();
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

//------------------------------------------------------------------------
// private method

}
