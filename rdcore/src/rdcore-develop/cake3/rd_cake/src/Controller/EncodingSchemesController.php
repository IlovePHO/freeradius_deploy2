<?php

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

use Exception;
use MethodNotAllowedException;
use Cake\Network\Exception\InternalErrorException;

class EncodingSchemesController extends AppController {

    public $main_model       = 'EncodingSchemes';
    public $base    = "Access Providers/Controllers/EncodingSchemes/";

    const HMAC_KEY_LENGTH = 32;
    const SUFFIX_LENGTH = 3;
    const SUFFIX_CHAR_TABLE = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz0123456789";

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

        #$this->loadComponent('CommonQuery', [ //Very important to specify the Model
        #    'model' => 'Proxies'
        #]);
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

        $this->LifeSeed->index($user, $query);
    }

    public function add() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        $connection = ConnectionManager::get('default');
        $connection->begin();
        $hmac_key_path = null;
        $fp = null;

        try {
            $this->_fillSuffix();

            $encoding_scheme = $this->LifeSeed->add($user);
            if ($encoding_scheme === false) {
                $connection->rollback();
                return;
            }

            $hmac_key_dir    = Configure::read('ExternalApi.HmacKey.dir');
            $hmac_key_prefix = Configure::read('ExternalApi.HmacKey.prefix');
            $hmac_key_suffix = $encoding_scheme->suffix;
            $hmac_key_ext    = Configure::read('ExternalApi.HmacKey.ext');

            $hmac_key_dir = preg_replace("/\/$/", '', $hmac_key_dir);
            if (strlen($hmac_key_ext) > 0) {
                $hmac_key_ext = preg_replace("/^\.*/", '.', $hmac_key_ext);
            }

            $hmac_key_path   = sprintf("%s/%s%s%s",
                                       $hmac_key_dir, $hmac_key_prefix,
                                       $hmac_key_suffix, $hmac_key_ext);
            if (file_exists($hmac_key_path)) {
                throw new InternalErrorException();
            }

            $key = $this->_generateHmacKey();

            $fp = tmpfile();
            if ($fp === false ||
                fwrite($fp, $key) === false ||
                fflush($fp) === false) {
                throw new InternalErrorException();
            }

            $tmpfile_path = stream_get_meta_data($fp)['uri'];
            if (chmod($tmpfile_path, 0644) === false ||
                rename($tmpfile_path, $hmac_key_path) === false) {
                throw new InternalErrorException();
            }
            fclose($fp);

            $this->_uploadHmacKeyToS3();

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollback();

            if (!is_null($fp)) {
                if (!is_null($hmac_key_path) && file_exists($hmac_key_path)) {
                    unlink($hmac_key_path);
                }
                fclose($fp);
            }

            $this->_handleException($e);
        }
    }

    private function _uploadHmacKeyToS3() {
        $sudo_cmd        = Configure::read('ExternalApi.CmdPath.sudo');
        $sync_hmac_cmd   = Configure::read('ExternalApi.CmdPath.sync_hmac');

        if (is_null($sudo_cmd) || is_null($sync_hmac_cmd) ||
            !file_exists($sync_hmac_cmd)) {
            return;
        }

        $command = sprintf("%s %s up", $sudo_cmd, $sync_hmac_cmd);
        exec($command, $opt, $return_value);
    }

    public function edit() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }

        // Restrict that the correspondence with the hmac key file cannot be
        // changed by edit().
        $suffix = $this->request->data('suffix');
        if (!is_null($suffix)) {
            unset($this->request->data['suffix']);
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

    private function _generateHmacKey() {
        $chars = [];
        for ($i = 0; $i < self::HMAC_KEY_LENGTH; $i++) {
            $chars[$i] = chr(mt_rand(32, 126));
        }
        return join('', $chars);
    }

    private function _generateSuffix($length) {
        $char_table_len = strlen(self::SUFFIX_CHAR_TABLE);
        $chars = [];
        for ($i = 0; $i < $length; $i++) {
            $index = random_int(0, $char_table_len - 1);
            $chars[$i] = self::SUFFIX_CHAR_TABLE[$index];
        }
        return join('', $chars);
    }

    private function _isExistSuffix($suffix) {
        $query = $this->{$this->main_model}->find();
        $query->where(['suffix' => $suffix]);
        $q_r = $query->first();
        return !is_null($q_r);
    }

    private function _fillSuffix() {
        if (is_null($this->request->data('suffix'))) {
            while (true) {
                $suffix = $this->_generateSuffix(self::SUFFIX_LENGTH);
                $exist = $this->_isExistSuffix($suffix);
                if (!$exist) {
                    $this->request->data['suffix'] = $suffix;
                    break;
                }
            }
        }
    }

    private function _handleException($e) {
        if (method_exists($e, 'getAttributes')) {
            $error_attributes = $e->getAttributes();
            if (isset($error_attributes['errors'])) {
                $error_hash = $error_attributes['errors'];
            } else {
                $error_hash = [];
            }

            if (isset($error_attributes['message'])) {
                $message = $error_attributes['message'];
            } else {
                $message = $e->getMessage();
            }
        } else {
            $error_hash = [];
            $message = $e->getMessage();
        }

        $this->set([
            'errors' => $error_hash,
            'success' => false,
            'message' => ['message' => $message],
            '_serialize' => ['errors','success','message']
        ]);
    }
    // GUI panel menu
    public function menuForGrid(){
    
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, false, 'EncodingSchemes'); 

        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }}
