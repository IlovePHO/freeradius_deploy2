<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class AttributeConvertsController extends AppController {

    public $main_model       = 'AttributeConverts';
    public $base    = "Access Providers/Controllers/AttributeConverts/";

//------------------------------------------------------------------------

    public function initialize()
    {
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

        if (isset($this->request->query['src'])) {
            $query->where(["AttributeConverts.src" =>
                $this->request->query['src']]);
        }
        if (isset($this->request->query['nas_type'])) {
            $query->where(["AttributeConverts.nas_type" =>
                $this->request->query['nas_type']]);
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


    // GUI panel menu
    public function menuForGrid(){
    
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, true, 'AttributeConverts'); 
		
        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }
}
