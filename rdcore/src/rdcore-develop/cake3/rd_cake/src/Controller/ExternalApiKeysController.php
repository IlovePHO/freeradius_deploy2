<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class ExternalApiKeysController extends AppController {

    public $main_model       = 'ExternalApiKeys';
    public $base    = "Access Providers/Controllers/ExternalApiKeys/";

//------------------------------------------------------------------------

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel('Users'); 

        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed', ['models' => ['Users']]);

        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => 'ExternalApiKeys',
            'no_available_to_siblings' => true,
        ]);
    }

    //____ BASIC CRUD Actions Manager ________

    public function index(){
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        $query = $this->{$this->main_model}->find();
        $this->CommonQuery->build_common_query($query, $user, ['Realms', 'Profiles']);

        $this->LifeSeed->index($user, $query, ['realm' => ['name'], 'profile' => ['name']]);
    }

    public function add() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->LifeSeed->modifyUserId($user);
        $this->LifeSeed->setUserIdIfEmpty($user);

        $uuid = $this->LifeSeed->generateUuid();
        $this->request->data['api_key'] = $uuid;

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

        if (isset($this->request->data['api_key'])) {
            unset($this->request->data['api_key']);
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

        $menu = $this->GridButtons->returnButtons($user, false, 'ExternalApiKeys'); 

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
        $user_id    = $user['id'];
        
        $tree     = false;     
        $entity = $this->Users->get($user_id); 
        if($this->Users->childCount($entity) > 0){
            $tree = true;
        }
        
        $data = [];

        if(null !== $this->request->getQuery('external_api_key_id')){
            $q_r = $this->{$this->main_model}->find()
                    ->where(['ExternalApiKeys.id' => $this->request->getQuery('external_api_key_id')])
                    ->contain(['Users'=> ['fields' => ['Users.username']], 'Realms' =>  ['fields' => ['Realms.name']], 'Profiles' =>  ['fields' => ['Profiles.name']] ])
                    ->first();
            //print_r($q_r);
            if($q_r){
                $data = $q_r;
            }
            // "User name" for Disp
            if($q_r->user !== null){
                $username = $q_r->user->username;
                $data['username']  = sprintf("<div class=\"fieldBlue\"> <b>%s</b></div>", $username);
            }else{
                $data['username']  = "<div class=\"fieldRed\"><i class='fa fa-exclamation'></i> <b>(ORPHANED)</b></div>";
            }
            $data['show_owner']  = $tree;

        }

        $this->set([
            'data'   => $data, //For the form to load we use data instead of the standard items as for grids
            'success' => true,
            '_serialize' => ['success','data']
        ]);
    }
}
