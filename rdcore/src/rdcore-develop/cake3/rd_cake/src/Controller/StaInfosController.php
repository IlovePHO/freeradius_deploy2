<?php

namespace App\Controller;

use Cake\Core\Configure;
use MethodNotAllowedException;

class StaInfosController extends AppController {

    public $main_model       = 'StaInfos';
    public $base    = "Access Providers/Controllers/StaInfos/";

//------------------------------------------------------------------------

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        $this->loadModel('Users'); 
        $this->loadModel('PermanentUsers'); 
        $this->loadModel('Vouchers'); 

        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed', ['models' => ['Users']]);

        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => 'StaInfos'
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
        $query->contain('PermanentUsers');
        $query->contain('Vouchers');
        $query->contain('StaConfigs');

        $callback = function($user, $item, &$row) {
            $username = null;
            $user_type = null;
            if (!is_null($row['permanent_user_username'])) {
                $username = $row['permanent_user_username'];
                $user_type = $this->PermanentUsers->alias();
            } else if (!is_null($row['voucher_name'])) {
                $username = $row['voucher_name'];
                $user_type = $this->Vouchers->alias();
            }

            $sta_configs = [];
            foreach ($item->sta_configs as $config) {
                $sta_configs[] = [
                    'name'   => $config->name,
                    'expire' => $config->expire->i18nFormat('yyyy/MM/dd'),
                ];
            }

            $row['username']    = $username;
            $row['user_type']   = $user_type;
            $row['sta_configs'] = $sta_configs;
        };

        $this->LifeSeed->index($user, $query, [
                                   'permanent_user' => ['username'],
                                   'voucher'        => ['name'],
                               ], [], $callback);
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

        $menu = $this->GridButtons->returnButtons($user, false, 'StaInfos'); 

        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }
}
