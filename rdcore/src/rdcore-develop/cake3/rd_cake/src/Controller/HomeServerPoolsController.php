<?php
/**
 * Created by G-edit.
 * User: dirkvanderwalt
 * Date: 09/12/2020
 * Time: 00:00
 */

namespace App\Controller;
use App\Controller\AppController;

use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

use Cake\Datasource\ConnectionManager;
use Cake\Network\Exception\BadRequestException;

class HomeServerPoolsController extends AppController{
  
    public $base            = "Access Providers/Controllers/HomeServerPools/";
    protected $owner_tree   = [];
    public $main_model      = 'HomeServerPools';
    protected $short_name   = 'home_server_';
    
    public function initialize(){  
        parent::initialize();
        $this->loadModel('HomeServerPools'); 
        $this->loadModel('HomeServers');      
        $this->loadModel('Users'); 
        $this->loadModel('Nas');
          
        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');
        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => 'HomeServerPools'
        ]);         
        $this->loadComponent('JsonErrors'); 
        $this->loadComponent('TimeCalculations');    
        $this->loadComponent('LifeSeed');
    }
    
     //____ BASIC CRUD Manager ________
    public function index(){

        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }
        $user_id    = $user['id'];
        $query      = $this->{$this->main_model}->find();
        // サーバリスト削除
        //$this->CommonQuery->build_common_query($query,$user,['Users','HomeServers']);

        //===== PAGING (MUST BE LAST) ======
        $limit  = 50;   //Defaults
        $page   = 1;
        $offset = 0;
        if(isset($this->request->query['limit'])){
            $limit  = $this->request->query['limit'];
            $page   = $this->request->query['page'];
            $offset = $this->request->query['start'];
        }
        
        $query->page($page);
        $query->limit($limit);
        $query->offset($offset);

        $total      = $query->count();       
        $q_r        = $query->all();
        $items      = []; 
        // print_r($q_r);
        foreach ($q_r as $i) {
            $owner_id = $i->user_id;
            if (!array_key_exists($owner_id, $this->owner_tree)) {
                $owner_tree = $this->Users->find_parents($owner_id);
            } else {
                $owner_tree = $this->owner_tree[$owner_id];
            }
            
            $action_flags = $this->Aa->get_action_flags($owner_id, $user);
            $row        = array();
            $fields     = $this->{$this->main_model}->schema()->columns();
            foreach($fields as $field){
                $row["$field"]= $i->{"$field"};
                
                if($field == 'created'){
                    $row['created_in_words'] = $this->TimeCalculations->time_elapsed_string($i->{"$field"});
                }
                if($field == 'modified'){
                    $row['modified_in_words'] = $this->TimeCalculations->time_elapsed_string($i->{"$field"});
                }   
            }

            $row['owner']		= $owner_tree;
            // Added workaround for $action_flags= NULL when user != owner
            $row['update'] = isset($action_flags['update']) ? $action_flags['update'] : false;
            $row['delete'] = isset($action_flags['delete']) ? $action_flags['delete'] : false;
            array_push($items, $row);
        }
       
        //___ FINAL PART ___
        $this->set(array(
            'items' => $items,
            'success' => true,
            'totalCount' => $total,
            '_serialize' => array('items','success','totalCount')
        ));
    }
  
    public function add() {
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }     
        $user_id    = $user['id'];

        //Get the creator's id
        if(isset($this->request->data['user_id'])){
            if($this->request->data['user_id'] == '0'){ //This is the holder of the token - override '0'
                $this->request->data['user_id'] = $user_id;
            }
        }
        
        $check_items = array(
			'available_to_siblings'
		);
        foreach($check_items as $i){
            if(isset($this->request->data[$i])){
                $this->request->data[$i] = 1;
            }else{
                $this->request->data[$i] = 0;
            }
        }

	    $items = [
            'name', 'type', 'ipaddr', 'port', 'secret', 'proto', 'status_check',
            'description', 'priority', 'accept_coa'
            #'response_window', 'zombie_period', 'revive_interval'
        ];

	    //How many home servers did the user save
        $hs_save_count = $this->_getHomeServersCount($items);

        $connection = ConnectionManager::get('default');
        $connection->begin();

        $entity = $this->{$this->main_model}->newEntity($this->request->data());
        try {
            $result = true;
            do {
                if (!$this->{$this->main_model}->save($entity)) {
                    $result = false;
                    $message = __('Could not create item');
                    $this->JsonErrors->entityErros($entity, $message);
                    break;
                }

                for ($x = 1; $x <= $hs_save_count; $x++) {
                    if (!$this->_createHomeServers($entity->id, $x, $items)) {
                        $result = false;
                        break;
                    }
                }
            } while (0);

            #$entity->setRedoConfigFile();
            //Check if we need to redo config file (which is NOT remove anything since its not yet added and append the new entry)
            #$this->{$this->main_model}->checkConfigFileRedo($entity);

            if ($result) {
                $this->set(array(
                    'success' => true,
                    '_serialize' => array('success')
                ));
                $connection->commit();
            } else {
                $connection->rollback();
            }
        } catch (\Exception $e) {
            $this->set(array(
                'success' => false,
                'message' => ['message' => $e->getMessage()],
                '_serialize' => array('success','message')
            ));
            $connection->rollback();
        }
    }
    
    public function edit(){  
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }
        $this->_edit($user);      
    }
    
    private function _edit($user) {
        //__ Authentication + Authorization __
        $user_id    = $user['id'];

        //Get the creator's id
        if(isset($this->request->data['user_id'])){
            if($this->request->data['user_id'] == '0'){ //This is the holder of the token - override '0'
                $this->request->data['user_id'] = $user_id;
            }
        }

        $check_items = [
			'available_to_siblings'
		];
        foreach($check_items as $i){
            if(isset($this->request->data[$i])){
                $this->request->data[$i] = 1;
            }else{
                $this->request->data[$i] = 0;
            }
        }

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            $result = true;
            do {
                $entity = $this->{$this->main_model}->get($this->request->data['id']);
                if (!isset($entity)) {
                    $result = false;
                    $message = __('Could not update item');
                    $this->JsonErrors->entityErros($entity,$message);
                    break;
                }

                //We only need to redo it IF the type field changed
                #if ($this->request->data['type'] !== $entity->type) {
                #    $entity->setRedoConfigFile();
                #}
                  
                $this->{$this->main_model}->patchEntity($entity, $this->request->data());
                if (!$this->{$this->main_model}->save($entity)) {
                    $result = false;
                    $message = __('Could not update item');
                    $this->JsonErrors->entityErros($entity,$message);
                    break;
                }

                if (!$this->_processHomeServers()) { //Do the home servers
                    $result = false;
                    break;
                }

                #$redo_config_file = $this->_processHomeServers(); //Do the home servers
                #if($redo_config_file){
                #    $entity->setRedoConfigFile(); //Flag it to redo config file if there was a condition for it
                #}
                #//Check if we need to redo config file
                #$this->{$this->main_model}->checkConfigFileRedo($entity);
            } while (0);

            if ($result) {
                $this->set(array(
                    'success' => true,
                    '_serialize' => array('success')
                ));
                $connection->commit();
            } else {
                $connection->rollback();
            }
        } catch (\Exception $e) {
            $this->set(array(
                'success' => false,
                'message' => ['message' => $e->getMessage()],
                '_serialize' => array('success','message')
            ));
            $connection->rollback();
        }
	}
	
    public function delete(){
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}

        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $user_id   = $user['id'];
        $fail_flag = false;

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            if (isset($this->request->data['id'])){   //Single item delete
                $message = "Single item ".$this->request->data['id'];

                //NOTE: we first check of the user_id is the logged in user OR a sibling of them:
                $entity     = $this->{$this->main_model}->get($this->request->data['id']);
                $owner_id   = $entity->user_id;

                if($owner_id != $user_id){
                    if($this->Users->is_sibling_of($user_id,$owner_id)== true){
                        $this->{$this->main_model}->removeHsPool($entity);
                        $this->{$this->main_model}->delete($entity);
                    }else{
                        $fail_flag = true;
                    }
                }else{
                    $this->{$this->main_model}->removeHsPool($entity);
                    $this->{$this->main_model}->delete($entity);
                }
            }else{                          //Assume multiple item delete
                foreach($this->request->data as $d){
                    $entity     = $this->{$this->main_model}->get($d['id']);
                    $owner_id   = $entity->user_id;
                    if($owner_id != $user_id){
                        if($this->Users->is_sibling_of($user_id,$owner_id) == true){
                            $this->{$this->main_model}->removeHsPool($entity);
                            $this->{$this->main_model}->delete($entity);
                        }else{
                            $fail_flag = true;
                        }
                    }else{
                        $this->{$this->main_model}->removeHsPool($entity);
                        $this->{$this->main_model}->delete($entity);
                    }
                }
            }

            if ($fail_flag == true) {
                $this->set(array(
                    'success'   => false,
                    'message'   => array('message' => __('Could not delete some items')),
                    '_serialize' => array('success','message')
                ));
                $connection->rollback();
            } else {
                $this->set(array(
                    'success' => true,
                    '_serialize' => array('success')
                ));
                $connection->commit();
            }
        } catch (\Exception $e) {
            $this->set(array(
                'success' => false,
                'message' => ['message' => $e->getMessage()],
                '_serialize' => array('success','message')
            ));
            $connection->rollback();
        }
	}

    private function _getHomeServersCount($fields) {
        $hs_save_count = 0;
        for ($i = 1; ; $i++) {
            $is_exist = false;
            foreach ($fields as $field) {
	            if (isset($this->request->data['hs_'.$i.'_'.$field])) {
                    $is_exist = true;
                    break;
                }
            }
            if (!$is_exist) {
                break;
            }
	        $hs_save_count = $i;
        }
        return $hs_save_count;
    }

    private function _createHomeServers($hsp_id, $x, $items) {
        if (isset($this->request->data['hs_'.$x.'_accept_coa'])) {
            $this->request->data['hs_'.$x.'_accept_coa'] = 1;
        } else {
            $this->request->data['hs_'.$x.'_accept_coa'] = 0;
        }

        $new_data = [];
        foreach ($items as $item){
            if (isset($this->request->data['hs_'.$x.'_'.$item])) {
                $new_data[$item] = $this->request->data['hs_'.$x.'_'.$item];
            }
        }
        $new_data['home_server_pool_id'] = $hsp_id;

        $new_hs_ent = $this->{'HomeServers'}->newEntity($new_data);
        if (!$this->{'HomeServers'}->save($new_hs_ent)) {
            $this->_setHomeServerError($new_hs_ent, $x, 'create item');
            return false;
        }

        if ($new_data['accept_coa']) {
            //With ADD we automatically add an entry to the nas table
            $d_nas                  = [];
            $d_nas['nasname']       = $new_data['ipaddr'];
            $d_nas['shortname']     = 'home_server_'.$new_hs_ent->id;
            $d_nas['nasidentifier'] = 'home_server_'.$new_hs_ent->id;
            $d_nas['ports']         = 3799;
            $d_nas['secret']        = $new_data['secret'];

            $e_nas                  = $this->{'Nas'}->find()->where(['Nas.shortname'
                                          => $d_nas['shortname']])->first();
            if ($e_nas) {
                $this->{'Nas'}->patchEntity($e_nas, $d_nas);
                if (!$this->{'Nas'}->save($e_nas)) {
                    $this->_setHomeServerError($e_nas, $x, 'update NAS (FOR COA)');
                    return false;
                }
            } else {
                $e_nas = $this->{'Nas'}->newEntity($d_nas);
                if (!$this->{'Nas'}->save($e_nas)) {
                    $this->_setHomeServerError($e_nas, $x, 'create NAS (FOR COA)');
                    return false;
                }
            }
        }
        #$redo_config_file = true;
        return true;
    }

    private function _updateHomeServers($hsp_id, $x, $items, $hs) {
        if (isset($this->request->data['hs_'.$x.'_accept_coa'])) {
            $this->request->data['hs_'.$x.'_accept_coa'] = 1;
        } else {
            $this->request->data['hs_'.$x.'_accept_coa'] = 0;
        }

        $new_data       = [];
        $changed_flag   = false;
        foreach ($items as $item) {
            if (isset($this->request->data['hs_'.$x.'_'.$item])) {
                $new_data[$item] = $this->request->data['hs_'.$x.'_'.$item];
                if ($this->request->data['hs_'.$x.'_'.$item] != $hs->{$item}) {
                    $changed_flag = true;
                }
            }
        }

        if ($changed_flag == true) {
            $this->{'HomeServers'}->patchEntity($hs, $new_data);
            if (!$this->{'HomeServers'}->save($hs)) {
                $this->_setHomeServerError($hs, $x, 'update item');
                return false;
            }

            $shortname = 'home_server_'.$hs->id;

            // Also if $new_data['accept_coa'] ==0 remove it from Nas
            // and if ==1 save nas
            if ($new_data['accept_coa']) {
                // With ADD we automatically add an entry to the nas table
                $d_nas                  = [];
                $d_nas['nasname']       = $new_data['ipaddr'];
                $d_nas['shortname']     = 'home_server_'.$hs->id;
                $d_nas['nasidentifier'] = 'home_server_'.$hs->id;
                $d_nas['ports']         = 3799;
                $d_nas['secret']        = $new_data['secret'];
                $e_nas                  = $this->{'Nas'}->find()->where(
                                              ['Nas.shortname' =>
                                               $d_nas['shortname']])->first();

                if ($e_nas) {
                    $this->{'Nas'}->patchEntity($e_nas, $d_nas);
                    if (!$this->{'Nas'}->save($e_nas)) {
                        $this->_setHomeServerError($new_hs_ent, $x, 'update NAS (FOR COA)');
                        return false;
                    }
                } else {
                    $e_nas = $this->{'Nas'}->newEntity($d_nas);
                    if (!$this->{'Nas'}->save($e_nas)) {
                        $this->_setHomeServerError($new_hs_ent, $x, 'create NAS (FOR COA)');
                        return false;
                    }
                }
            } else {
                $e_nas = $this->{'Nas'}->find()->where(
                             ['Nas.shortname' => $shortname])->first();
                if ($e_nas) {
                    $this->{'Nas'}->delete($e_nas);
                }
            }
            #$redo_config_file = true;
        }
        return true;
    }

	private function _processHomeServers() {
	    #$redo_config_file = false;
	
	    $hsp_id  = $this->request->data['id'];
	    $current = $this->{$this->main_model}->find()->
                       where(['HomeServerPools.id' => $hsp_id])->
                       contain(['HomeServers'])->first();

	    $check_items = ['accept_coa'];  
	    $items = [
            'name', 'type', 'ipaddr', 'port', 'secret', 'proto', 'status_check',
            'description', 'priority', 'accept_coa'
            #'response_window', 'zombie_period', 'revive_interval'
        ];

	    //How many home servers did the user save
        $hs_save_count = $this->_getHomeServersCount(array_merge($items, ['id']));

        $matched_hs_save = [];
	    if ($current) {
	        $hs_list = $current->home_servers;
	        foreach($hs_list as $hs){
	            $no_match = true;
	            for ($x = 1; $x <= $hs_save_count; $x++){
	                //Match
                    if (isset($this->request->data['hs_'.$x.'_id']) &&
	                    $this->request->data['hs_'.$x.'_id'] == $hs->id) {
                        $no_match = false;
                        $matched_hs_save[] = $x;
                        if (!$this->_updateHomeServers($hsp_id, $x, $items, $hs)) {
                            return false;
                        }
	                }
	            }

	            if ($no_match) {
	                $this->{'HomeServers'}->delete($hs);
	                #$redo_config_file = true;
	            }	        
	        }
	    }

	    for ($x = 1; $x <= $hs_save_count; $x++){
            if (in_array($x, $matched_hs_save)) {
                continue;
            }
            if (!isset($this->request->data['hs_'.$x.'_id']) ||
                $this->request->data['hs_'.$x.'_id'] == '') {
                if (!$this->_createHomeServers($hsp_id, $x, $items)) {
                    return false;
                }
            }
        }
	    
	    #return $redo_config_file;
        return true;
	}

    public function menuForGrid(){
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, false, 'HomeServerPools'); 
        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }

    private function _convertHomeServerErrorHash($error_hash, $index) {
        foreach ($error_hash as $key => $value) {
            $error_hash['hs_'.$index.'_'.$key] = $value;
            unset($error_hash[$key]);
        }
        return $error_hash;
    }

    private function _setHomeServerError($entity, $index, $process) {
        $error_hash = $this->_convertHomeServerErrorHash(
                          $this->LifeSeed->generateErrorHashFromEntity($entity), $index);

        $message = __('Could not '.$process);
        $this->set([
            'errors' => $error_hash,
            'success' => false,
            'message' => ['message' => $message],
            '_serialize' => ['errors','success','message']
        ]);
    }

    public function types(){
        
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $types = Configure::read('HomeServerPools.Types');
        $this->set(array(
            'items' => $types,
            'success' => true,
            '_serialize' => array('items','success')
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

        if(null !== $this->request->getQuery('home_server_pool_id')){
            $q_r = $this->{$this->main_model}->find()
                    ->where(['HomeServerPools.id' => $this->request->getQuery('home_server_pool_id')])
                    ->contain(['Users'=> ['fields' => ['Users.username']]])
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