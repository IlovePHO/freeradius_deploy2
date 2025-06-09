<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class IdpOauthClientTokensController extends AppController {

    public $main_model       = 'IdpOauthClientTokens';
    public $base    = "Access Providers/Controllers/IdpOauthClientTokens/";

//------------------------------------------------------------------------

    public function initialize()
    {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel('Users');

        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations'); 

        $this->loadComponent('LifeSeed', ['models' => ['Users']]);
    }

    //____ BASIC CRUD Actions Manager ________

    public function index() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $query = $this->{$this->main_model}->find();

        // Remove the tokens because returning them is problematic.
        $this->LifeSeed->index($user, $query, [], ['' => ['token']]);
    }

    public function add() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->LifeSeed->modifyUserId($user);
        $this->LifeSeed->setUserIdIfEmpty($user);
        #$this->LifeSeed->modifyCheckBox(['available_to_siblings']);

        $this->LifeSeed->add($user);
    }

    public function edit() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->LifeSeed->modifyUserId($user);
        $this->LifeSeed->setUserIdIfEmpty($user);
        #$this->LifeSeed->modifyCheckBox(['available_to_siblings']);

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

        $menu = $this->GridButtons->returnButtons($user, true, 'IdpOauthClientTokens'); 
		
        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }
}
