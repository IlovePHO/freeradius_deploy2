<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class StaConfigsController extends AppController {

    public $main_model       = 'StaConfigs';
    public $base    = "Access Providers/Controllers/StaConfigs/";

//------------------------------------------------------------------------

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel('Users'); 
        $this->loadModel('Realms');
        $this->loadModel('SubGroups'); 
        $this->loadModel('StaConfigsRealms'); 
        $this->loadModel('StaConfigsSubGroups'); 

        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed', ['models' => ['Users']]);

        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => 'StaConfigs'
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
        $query->contain('Realms');
        $query->contain('SubGroups');

        $callback = function($user, $item, &$row) {
            $realms = [];
            foreach ($item->realms as $realm) {
                $realms[] = [
                    'name'   => $realm->name,
                ];
            }

            $sub_groups = [];
            foreach ($item->sub_groups as $sub_group) {
                $realm = $this->Realms->get($sub_group->realm_id);
                $sub_groups[] = [
                    'name'   => sprintf("%s(%s)", $sub_group->name, $realm->name),
                ];
            }

            $row['realms']      = $realms;
            $row['sub_groups']  = $sub_groups;
            $row['expire']      = $item->expire->i18nFormat('yyyy/MM/dd');
        };

        $this->LifeSeed->index($user, $query, [], [], $callback);
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

        // Input values are checked by StaConfigsTable::validationDefault().

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
        $this->LifeSeed->modifyCheckBox(['available_to_siblings']);

        // Input values are checked by StaConfigsTable::validationDefault().

        $this->LifeSeed->edit($user);
    }

    public function editRealms() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $sta_config_id = $this->request->data('id');
        $realm_id      = $this->request->data('realm_id');
        $enable        = $this->request->data('enable');

        $query = $this->StaConfigsRealms->find();
        $query->where(['sta_config_id' => $sta_config_id]);
        $query->where(['realm_id' => $realm_id]);
        $entity = $query->first();

        $success = true;
        if ($enable) {
            if (is_null($entity)) {
                $data = [
                    'sta_config_id' => $sta_config_id,
                    'realm_id' => $realm_id,
                ];
                $entity = $this->StaConfigsRealms->newEntity($data);
                $success = $this->StaConfigsRealms->save($entity);
            }
        } else {
            if (!is_null($entity)) {
                $success = $this->StaConfigsRealms->delete($entity);
            }
        }

        $this->set(array(
            'success' => $success,
            '_serialize' => array('success')
        ));
    }

    public function editSubGroups() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $sta_config_id = $this->request->data('id');
        $sub_group_id  = $this->request->data('sub_group_id');
        $enable        = $this->request->data('enable');

        $query = $this->StaConfigsSubGroups->find();
        $query->where(['sta_config_id' => $sta_config_id]);
        $query->where(['sub_group_id' => $sub_group_id]);
        $entity = $query->first();

        $success = true;
        if ($enable) {
            if (is_null($entity)) {
                $data = [
                    'sta_config_id' => $sta_config_id,
                    'sub_group_id' => $sub_group_id,
                ];
                $entity = $this->StaConfigsSubGroups->newEntity($data);
                $success = $this->StaConfigsSubGroups->save($entity);
            }
        } else {
            if (!is_null($entity)) {
                $success = $this->StaConfigsSubGroups->delete($entity);
            }
        }

        $this->set(array(
            'success' => $success,
            '_serialize' => array('success')
        ));
    }

    public function delete($id = null) {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $this->LifeSeed->delete($user, $id);
    }

    public function getEapMethodList() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $items = [
            ['name' => 'PEAP', 'value' => 'peap'],
            ['name' => 'EAP-TTLS/MS-CHAPv2', 'value' => 'eap-ttls/mschapv2'],
            ['name' => 'EAP-TTLS/MS-CHAP', 'value' => 'eap-ttls/mschap'],
            ['name' => 'EAP-TTLS/PAP', 'value' => 'eap-ttls/pap'],
        ];

        $this->set(array(
            'success' => true,
            'items' => $items,
            '_serialize' => array('success', 'items')
        ));
    }

    // GUI panel menu
    public function menuForGrid(){
    
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, false, 'StaConfigs'); 

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
        if(null !== $this->request->getQuery('sta_config_id')){
            $q_r = $this->{$this->main_model}->find()
                    ->where(['StaConfigs.id' => $this->request->getQuery('sta_config_id')])
                   // ->contain(['Users'=> ['fields' => ['Users.username']]])
                    ->contain([
                        'Users'=> ['fields' => ['Users.username']],
                        'Realms' =>  ['fields' => ['Realms.id']],
                        'SubGroups' =>  ['fields' => ['SubGroups.id']]
                    ])
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
            
            // ids & remove '_joinData'
            foreach ( ['realms','sub_groups'] as $column) {
                /*foreach ($data[$column] as &$r) {
                    unset($r['_joinData']);
                }*/
                $ids = [];
                foreach ($data[$column] as $r){
                    $ids[] = $r['id'];
                }
                $data[$column] = $ids;
            }

        }

        $this->set([
            'data'   => $data, //For the form to load we use data instead of the standard items as for grids
            'success' => true,
            '_serialize' => ['success','data']
        ]);
    }
}
