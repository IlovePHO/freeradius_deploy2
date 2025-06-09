<?php

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

use MethodNotAllowedException;

class IdpsController extends AppController {

    public $main_model       = 'Idps';
    public $base    = "Access Providers/Controllers/Idps/";

//------------------------------------------------------------------------

    public function initialize()
    {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel('IdpOauthClientCredentials');
        $this->loadModel('Users');

        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed', ['models' => ['Users']]);

        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => 'Idps'
        ]);
    }

    //____ BASIC CRUD Actions Manager ________

    public function index() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $query = $this->{$this->main_model}->find();
        $this->CommonQuery->build_common_query($query, $user, ['Realms']);

        $this->LifeSeed->index($user, $query, ['realm' => ['name']]);
    }

    public function add() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->LifeSeed->modifyUserId($user);
        $this->LifeSeed->setUserIdIfEmpty($user);
        $this->LifeSeed->modifyCheckBox(['available_to_siblings']);

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            $idp = $this->LifeSeed->add($user);
            if ($idp === false) {
                $connection->rollback();
                return;
            }

            $credential = $this->request->data('credential');
            if (!is_null($credential) && !empty($credential)) {
                if (!$this->_addOrEditOauthClientCredentials($user, $idp['id'])) {
                    $connection->rollback();
                    return;
                }
            }

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollback();
        }
    }

    public function edit() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->LifeSeed->modifyUserId($user);
        $this->LifeSeed->setUserIdIfEmpty($user);
        $this->LifeSeed->modifyCheckBox(['available_to_siblings']);

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            $idp = $this->LifeSeed->edit($user);
            if ($idp === false) {
                $connection->rollback();
                return;
            }

            $credential = $this->request->data('credential');
            if (!is_null($credential) && !empty($credential)) {
                if (!$this->_addOrEditOauthClientCredentials($user, $idp['id'])) {
                    $connection->rollback();
                    return;
                }
            }

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollback();
        }
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

        $menu = $this->GridButtons->returnButtons($user, false, 'Idps');

        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }

    private function _addOrEditOauthClientCredentials($user, $idp_id) {
        // The model specification allows multiple credentials to be defined,
        // but the screen specification limits the number of credentials to one.
        $query = $this->IdpOauthClientCredentials->find();
        $query->where(['idp_id' => $idp_id]);
        $entity = $query->first();

        $data = [
            'name'                  => $this->request->data('name'),
            'available_to_siblings' =>
                $this->request->data('available_to_siblings'),
            'user_id'               => $this->request->data('user_id'),
            'idp_id'                => $idp_id,
            'credential'            => $this->request->data('credential'),
        ];

        if (is_null($entity)) {
            $entity = $this->IdpOauthClientCredentials->newEntity($data);
            $type = __('create');
        } else {
            $this->IdpOauthClientCredentials->patchEntity($entity, $data);
            $type = __('update');
        }

        if ($this->IdpOauthClientCredentials->save($entity)) {
            $this->set([
                'success' => true,
                '_serialize' => ['success']
            ]);

            return true;
        } else {
            $error_hash = $this->LifeSeed->generateErrorHashFromEntity($entity);
            $message = __("Could not {0} item", $type);

            $this->set([
                'errors' => $error_hash,
                'success' => false,
                'message' => ['message' => $message],
                '_serialize' => ['errors','success','message']
            ]);

            return false;
        }
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

        if(null !== $this->request->getQuery('idp_id')){
            $q_r = $this->{$this->main_model}->find()
                    ->where(['Idps.id' => $this->request->getQuery('idp_id')])
                    ->contain(['Users'=> ['fields' => ['Users.username']], 'Realms' =>  ['fields' => ['Realms.name']]])
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

    public function types(){
        
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $types = Configure::read('idps.Types');
        $this->set(array(
            'items' => $types,
            'success' => true,
            '_serialize' => array('items','success')
        ));
    }

    public function authTypes(){

        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $type = $this->request->query('type');
        if (is_null($type) || empty($type)) {
            $items = [
                ["value" => "none", "name" => __("(none)")],
                ["value" => "oauth", "name" => "OAuth"],
            ];
        } else {
            if ($type === 'direct') {
                $items = [
                    ["value" => "none", "name" => __("(none)")],
                ];
            } else {
                $items = [
                    ["value" => "oauth", "name" => "OAuth"],
                ];
            }
        }

        $this->set(array(
            'items' => $items,
            'success' => true,
            '_serialize' => array('items','success')
        ));
    }
}
