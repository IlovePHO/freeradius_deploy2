<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class HomeServersController extends AppController {

    public $main_model       = 'HomeServers';
    public $base    = "Access Providers/Controllers/HomeServers/";

//------------------------------------------------------------------------

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed');
    }

    //____ BASIC CRUD Actions Manager ________

    public function index() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $query = $this->{$this->main_model}->find();

        if (isset($this->request->query['home_server_pool_id'])) {
            $query->where(["home_server_pool_id" => $this->request->query['home_server_pool_id']]);
        }

        $this->LifeSeed->index($user, $query);
    }

    public function add() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->LifeSeed->add($user);
    }

    public function edit() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

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

        $menu = $this->GridButtons->returnButtons($user, false, 'HomeServers'); 
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

        if(null !== $this->request->getQuery('home_server_id')){
            $q_r = $this->{$this->main_model}->find()
                    ->where(['HomeServers.id' => $this->request->getQuery('home_server_id')])
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
