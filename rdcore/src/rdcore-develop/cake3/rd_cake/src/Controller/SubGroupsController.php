<?php

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

use Exception;
use MethodNotAllowedException;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\ForbiddenException;

class SubGroupsController extends AppController {

    public $main_model       = 'SubGroups';
    public $base    = "Access Providers/Controllers/SubGroups/";

//------------------------------------------------------------------------

    public function initialize()
    {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel('PermanentUsers');
        $this->loadModel('Profiles');
        $this->loadModel('Users');
        $this->loadModel('Realms');

        $this->loadComponent('RealmAcl');

        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations'); 

        $this->loadComponent('LifeSeed', ['models' => ['Users', 'Realms']]);
    }

    //____ BASIC CRUD Actions Manager ________

    public function index() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $query = $this->{$this->main_model}->find();

        if (isset($this->request->query['realm_id'])) {
            $query->where(["SubGroups.realm_id" => $this->request->query['realm_id']]);
        }
        if (isset($this->request->query['idp_id'])) {
            $query->where(["SubGroups.idp_id" => $this->request->query['idp_id']]);
        }

        $callback = function($user, $i, &$row) {
            // Check the permissions of the accessing user to the realm
            $realm_id = $i->{'realm_id'};
            $realm = $this->Realms->get($realm_id);
            if (is_null($realm)) {
                $row = null;
                return;
            }

            try {
                if (!$this->LifeSeed->checkRealmPermByUserAndGroup($realm, $user, 'read')) {
                    throw new ForbiddenException();
                }
            } catch (Exception $e) {
                $row = null;
            }
        };

        $post_callback = function($user, &$items) {
            // Processing to add "(none)" to the combo box choices.
            $append_none = $this->request->query('append_none');
            if (!is_null($append_none) && $append_none) {
                $row = [
                    'id'   => '-1',
                    'name' => __('(none)'),
                ];
                array_unshift($items, $row);
            }
        };

        $this->LifeSeed->index($user, $query, [], [], $callback, $post_callback);
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

        $profile_entity = $this->Profiles->entityBasedOnPost($this->request->data);
        if ($profile_entity) {
            $this->request->data['profile']    = $profile_entity->name;
            $this->request->data['profile_id'] = $profile_entity->id;
        }

        try {
            // Check realm permission
            $realm_id = $this->request->data('realm_id');
            $this->_checkRealmPermission($realm_id, $user, 'create');

            $this->LifeSeed->add($user);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
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
        #$this->LifeSeed->modifyCheckBox(['available_to_siblings']);

        $profile_entity = $this->Profiles->entityBasedOnPost($this->request->data);
        if ($profile_entity) {
            $this->request->data['profile']    = $profile_entity->name;
            $this->request->data['profile_id'] = $profile_entity->id;
        }

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            $sub_group_id = $this->request->data('id');
            $sub_group = $this->SubGroups->get($sub_group_id);
            if (is_null($sub_group)) {
                throw new BadRequestException();
            }

            // Check realm permission
            $realm_id = $sub_group->realm_id;
            $this->_checkRealmPermission($realm_id, $user, 'update');

            // Edit subgroups.
            if ($this->LifeSeed->edit($user)) {
                // Set the profile set for the subgroup to the member as well.
                $sub_group_id = $this->request->data('id');
                $this->_modifyMemberProfile($user, $sub_group_id);
                $connection->commit();
            } else {
                $connection->rollback();
            }
        } catch (Exception $e) {
            $connection->rollback();
            $this->LifeSeed->handleException($e);
        }
    }

    public function delete($id = null) {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            // Get a list of sub_group_id to be deleted.
            $ids = $this->LifeSeed->getIds();

            foreach ($ids as $id) {
                $sub_group = $this->SubGroups->get($id);
                if (is_null($sub_group)) {
                    throw new BadRequestException();
                }
                $realm_id = $sub_group->realm_id;

                // Check realm permission
                $this->_checkRealmPermission($realm_id, $user, 'delete');
            }

            foreach ($ids as $id) {
                // Clear the profiles of PermanentUsers participating in the sub_group.
                $this->_clearMemberProfile($user, $id);
            }

            // Delete subgroups.
            if ($this->LifeSeed->delete($user, $id)) {
                $connection->commit();
            } else {
                $connection->rollback();
            }
        } catch (Exception $e) {
            $connection->rollback();
            $this->LifeSeed->handleException($e);
        }
    }

    // GUI panel menu
    public function menuForGrid(){
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, false, 'SubGroups'); 
		
        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }

    private function _getSubGroupContainPermanentUsers($user, $sub_group_id) {
        $query = $this->SubGroups->find();
        $query->contain('PermanentUsers');
        $query->where(['SubGroups.id' => $sub_group_id]);

        $sub_group = $query->first();
        if ($sub_group === false || !isset($sub_group['permanent_users'])) {
            return false;
        }
        return $sub_group;
    }

    private function _modifyMemberProfile($user, $sub_group_id) {
        $sub_group = $this->_getSubGroupContainPermanentUsers($user, $sub_group_id);
        if ($sub_group === false) {
            return;
        }

        foreach ($sub_group['permanent_users'] as $permanent_user) {
            // Check the write permission for the permanent_user to be processed.
            if (!$this->LifeSeed->checkEntityWritePermByUserAndGroup($permanent_user, $user)) {
                continue;
            }

            $data = [
                'profile'    => $sub_group['profile'],
                'profile_id' => $sub_group['profile_id'],
            ];
            $this->PermanentUsers->patchEntity($permanent_user, $data);
            $this->PermanentUsers->save($permanent_user);
        }
    }

    private function _clearMemberProfile($user, $sub_group_id) {
        $sub_group = $this->_getSubGroupContainPermanentUsers($user, $sub_group_id);
        if ($sub_group === false) {
            return;
        }

        foreach ($sub_group['permanent_users'] as $permanent_user) {
            // Check the write permission for the permanent_user to be processed.
            if (!$this->LifeSeed->checkEntityWritePermByUserAndGroup($permanent_user, $user)) {
                continue;
            }

            $data = [
                'profile'    => '',
                'profile_id' => null,
            ];
            $this->PermanentUsers->patchEntity($permanent_user, $data);
            $this->PermanentUsers->save($permanent_user);
        }
    }

    private function _checkRealmPermission($realm_id, $user, $right = 'read') {
        if (is_null($realm_id)) {
            throw new BadRequestException();
        }

        $realm = $this->Realms->get($realm_id);
        if (is_null($realm)) {
            throw new BadRequestException();
        }

        if (!$this->LifeSeed->checkRealmPermByUserAndGroup($realm, $user, $right)) {
            throw new ForbiddenException();
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

        if(null !== $this->request->getQuery('sub_groups_id')){
            $q_r = $this->{$this->main_model}->find()
                    ->where(['SubGroups.id' => $this->request->getQuery('sub_groups_id')])
                    ->contain(['Users'=> ['fields' => ['Users.username']], 'Realms' =>  ['fields' => ['Realms.name']]])
                    ->first();
            //print_r($q_r);
            if($q_r){
                $data = $q_r;
                // "User name" for Disp
                if($q_r->user !== null){
                    $username = $q_r->user->username;
                    $data['username']  = sprintf("<div class=\"fieldBlue\"> <b>%s</b></div>", $username);
                }else{
                    $data['username']  = "<div class=\"fieldRed\"><i class='fa fa-exclamation'></i> <b>(ORPHANED)</b></div>";
                }
                $data['show_owner']  = $tree;
            }

        }

        $this->set([
            'data'   => $data, //For the form to load we use data instead of the standard items as for grids
            'success' => true,
            '_serialize' => ['success','data']
        ]);
    }
}
