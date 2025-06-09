<?php

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;

use Exception;
use MethodNotAllowedException;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;

class ExternalApiController extends AppController {

    public $main_model       = 'Users';
    public $base    = "Access Providers/Controllers/ExternalApi/";

    const EAP_METHOD_TYPE_EAP_TLS            = 13;
    const EAP_METHOD_TYPE_EAP_TTLS           = 21;
    const EAP_METHOD_TYPE_PEAP               = 25;
    const EAP_METHOD_TYPE_EAP_MSCHAPV2       = 26;

    const NON_EAP_AUTH_METHOD_TYPE_PAP       = 1;
    const NON_EAP_AUTH_METHOD_TYPE_MSCHAP    = 2;
    const NON_EAP_AUTH_METHOD_TYPE_MSCHAPV2  = 3;

    const TTLS_INNER_AUTHENTICATION_PAP      = 'PAP';
    const TTLS_INNER_AUTHENTICATION_MSCHAP   = 'MSCHAP';
    const TTLS_INNER_AUTHENTICATION_MSCHAPV2 = 'MSCHAPv2';

    const PROFILE_USERNAME_VERSION           = 1;

    const PROFILE_TEMPLATE_DIR               = './src/Template/ExternalApi/';
    const PROFILE_TEMPLATE_FILE_EAP_CONFIG   = 'eap-config.xml.twig';
    const PROFILE_TEMPLATE_FILE_MOBILECONFIG = 'mobileconfig.xml.twig';
    const PROFILE_TEMPLATE_FILE_WINDOWS      = 'windows.xml.twig';

    const DEVICE_TYPE_ANDROID                = 'android';
    const DEVICE_TYPE_IOS                    = 'ios';
    const DEVICE_TYPE_MACOS                  = 'macos';
    const DEVICE_TYPE_WINDOWS                = 'windows';

    const PROFILE_TYPE_EAP_CONFIG            = 'eap-config';
    const PROFILE_TYPE_MOBILECONFIG          = 'mobileconfig';
    const PROFILE_TYPE_WINDOWS               = 'windows';

    const CONTENT_TYPE_EAP_CONFIG            = 'application/eap-config';
    const CONTENT_TYPE_MOBILECONFIG          = 'application/x-apple-aspen-config';
    const CONTENT_TYPE_WINDOWS               = 'text/xml';

    const DOWNLOAD_FILE_NAME_EAP_CONFIG      = 'profile.eap-config';
    const DOWNLOAD_FILE_NAME_MOBILECONFIG    = 'profile.mobileconfig';
    const DOWNLOAD_FILE_NAME_WINDOWS         = 'profile.xml';

    // The format of expire in Voucher::add() is "m/d/Y",
    // but since it is difficult to use in Japan, change it to "Y/m/d".
    const VOUCHER_EXPIRE_FORMAT              = 'Y/m/d';

    // The following parameters are set in cake3/rd_cake/config/RadiusDesk.php
    const CONFIG_KEY_HMAC_KEY_DIR            = 'ExternalApi.HmacKey.dir';
    const CONFIG_KEY_HMAC_KEY_PREFIX         = 'ExternalApi.HmacKey.prefix';
    const CONFIG_KEY_HMAC_KEY_EXT            = 'ExternalApi.HmacKey.ext';

    const CONFIG_KEY_RADIUS_CA_CERT_PATH     = 'ExternalApi.Radius.ca_cert_path';
    const CONFIG_KEY_RADIUS_SERVER_CN        = 'ExternalApi.Radius.server_cn';

    const CONFIG_KEY_SUDO_COMMAND_PATH       = 'ExternalApi.CmdPath.sudo';
    const CONFIG_KEY_OPENSSL_COMMAND_PATH    = 'ExternalApi.CmdPath.openssl';
    const CONFIG_KEY_XMLSEC1_COMMAND_PATH    = 'ExternalApi.CmdPath.xmlsec1';

    const CONFIG_KEY_MOBILECONFIG_PL_UUID    = 'ExternalApi.Mobileconfig.pl_uuid';
    const CONFIG_KEY_MOBILECONFIG_PLID_PREFIX = 'ExternalApi.Mobileconfig.plid_prefix';
    const CONFIG_KEY_MOBILECONFIG_CA_CERT_NAME = 'ExternalApi.Mobileconfig.ca_cert_name';
    const CONFIG_KEY_MOBILECONFIG_DESCRIPTION = 'ExternalApi.Mobileconfig.description';
    const CONFIG_KEY_MOBILECONFIG_PAYLOAD_DISPLAY_NAME =
        'ExternalApi.Mobileconfig.payload_display_name';
    const CONFIG_KEY_MOBILECONFIG_CHAIN_PATH = 'ExternalApi.Mobileconfig.signer_chain_path';
    const CONFIG_KEY_MOBILECONFIG_CERT_PATH  = 'ExternalApi.Mobileconfig.signer_cert_path';
    const CONFIG_KEY_MOBILECONFIG_KEY_PATH   = 'ExternalApi.Mobileconfig.signer_key_path';

    const CONFIG_KEY_WINDOWS_CARRIER_ID      = 'ExternalApi.Windows.carrier_id';
    const CONFIG_KEY_WINDOWS_SUBSCRIBER_ID   = 'ExternalApi.Windows.subscriver_id';
    const CONFIG_KEY_WINDOWS_AUTHOR_ID       = 'ExternalApi.Windows.author_id';
    const CONFIG_KEY_WINDOWS_TRUSTED_ROOT_CA_HASH = 'ExternalApi.Windows.trusted_root_ca_hash';
    const CONFIG_KEY_WINDOWS_SIGNER_CERT_PFX_PATH = 'ExternalApi.Windows.signer_cert_pfx_path';
    const CONFIG_KEY_WINDOWS_PFX_PASSWD      = 'ExternalApi.Windows.pfx_password';
    const CONFIG_KEY_WINDOWS_CA_FILE         = 'ExternalApi.Windows.ca_file';

//------------------------------------------------------------------------

    public function initialize() {
        parent::initialize();
        #$this->loadModel($this->main_model);
        $this->loadModel('Users');
        $this->loadModel('Groups');
        $this->loadModel('Realms');
        $this->loadModel('Profiles');

        $this->loadModel('ExternalApiKeys');
        $this->loadModel('EncodingSchemes');
        $this->loadModel('StaConfigs');
        $this->loadModel('StaConfigsRealms');
        $this->loadModel('StaConfigsSubGroups');
        $this->loadModel('StaInfos');
        $this->loadModel('StaConfigsStaInfos');
        $this->loadModel('PermanentUsers');
        $this->loadModel('Vouchers');

        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('VoucherGenerator');
        $this->loadComponent('TimeCalculations');
        $this->loadComponent('MailTransport');

        $this->loadComponent('LifeSeed', ['models' => ['Users']]);

        #$this->loadComponent('CommonQuery', [ //Very important to specify the Model
        #    'model' => 'ExternalApi'
        #]);
    }

    public function getPermanentUsers() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            // Obtain API key from queries and data.
            $api_key = $this->_getRequestApiKey();
            if (is_null($api_key)) {
                throw new BadRequestException();
            }

            $realm    = $api_key->realm;
            $profile  = $api_key->profile;

            $username = $this->_getRequestParam('username');
            $email    = $this->_getRequestParam('email');
            if (is_null($username) && is_null($email)) {
                throw new BadRequestException();
            }

            $items = $this->_getPermanentUsers($user, $username, $email, $realm, $profile);
            $total = count($items);

            //___ FINAL PART ___
            $this->set([
                'items' => $items,
                'success' => true,
                'totalCount' => $total,
                '_serialize' => ['items', 'success', 'totalCount']
            ]);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function createPermanentUser() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            // Obtain API key from queries and data.
            $api_key = $this->_getRequestApiKey();
            if (is_null($api_key)) {
                throw new BadRequestException();
            }

            $realm    = $api_key->realm;
            $profile  = $api_key->profile;

            $username = $this->_getRequestParam('username');
            $email    = $this->_getRequestParam('email');
            if (is_null($username) || empty($username) ||
                is_null($email) || empty($email)) {
                throw new BadRequestException();
            }

            $unique_id = $this->_getRequestParam('unique_id');
            $unique_id_type = $this->_getRequestParam('unique_id_type');
            if (is_null($unique_id) || empty($unique_id)) {
                $unique_id_type = null;
            } else if (is_null($unique_id_type) || empty($unique_id_type)) {
                throw new BadRequestException();
            }

            $success = $this->_createPermanentUser($user, $username, $email, $realm, $profile,
                                                   $unique_id_type, $unique_id);

            $this->set([
                'success'    => $success,
                'username'   => $username,
                '_serialize' => ['success', 'username'],
            ]);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function deletePermanentUser() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            // Obtain API key from queries and data.
            $api_key = $this->_getRequestApiKey();
            if (is_null($api_key)) {
                throw new BadRequestException();
            }

            $username = $this->_getRequestParam('username');
            if (is_null($username)) {
                throw new BadRequestException();
            }

            $success = false;
            $permanent_user = $this->_findPermanentUserByUsername($username);
            if (!is_null($permanent_user) &&
                $permanent_user->user_id === $user['id'] ||
                $this->Users->is_sibling_of($user['id'], $permanent_user->user_id) === true) {

                // Disable by setting active to false instead of deleting.
                #$this->PermanentUsers->delete($permanent_user);
                $permanent_user->active = false;
                if ($this->PermanentUsers->save($permanent_user)) {
                    $success = true;
                }
            }

            $this->set([
                'success'   => $success,
                '_serialize' => ['success']
            ]);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function getVouchers() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            // Obtain API key from queries and data.
            $api_key = $this->_getRequestApiKey();
            if (is_null($api_key)) {
                throw new BadRequestException();
            }

            $realm    = $api_key->realm;
            $profile  = $api_key->profile;

            $username = $this->_getRequestParam('username');
            $email    = $this->_getRequestParam('email');
            if (is_null($username) && is_null($email)) {
                throw new BadRequestException();
            }

            $items = $this->_getVouchers($user, $username, $email, $realm, $profile);
            $total = count($items);

            //___ FINAL PART ___
            $this->set([
                'items' => $items,
                'success' => true,
                'totalCount' => $total,
                '_serialize' => ['items', 'success', 'totalCount']
            ]);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function createVoucher() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        // Since _createVouchers() can create multiple Vouchers at the same time,
        // transactions should be used.
        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            // Obtain API key from queries and data.
            $api_key = $this->_getRequestApiKey();
            if (is_null($api_key)) {
                return;
            }

            $realm    = $api_key->realm;
            $profile  = $api_key->profile;

            // When creating a Voucher, username is not specified.
            $username = $this->_getRequestParam('username');
            $email    = $this->_getRequestParam('email');
            $usernames = null;
            if (!is_null($username) && !empty($username)) {
                $usernames = [$username];
            }

            $unique_id = $this->_getRequestParam('unique_id');
            $unique_id_type = $this->_getRequestParam('unique_id_type');
            if (is_null($unique_id) || empty($unique_id)) {
                $unique_id_type = null;
            } else if (is_null($unique_id_type) || empty($unique_id_type)) {
                throw new BadRequestException();
            }

            $username = null;
            $created = $this->_createVouchers($user, $usernames, [$email], $realm,
                                              $profile, $unique_id_type, $unique_id);
            if (count($created) > 0) {
                $username = $created[0]["name"];
            } else {
                throw new Exception();
            }

            $connection->commit();

            $this->set(array(
                'success'    => true,
                'username'   => $username,
                '_serialize' => array('success', 'username')
            ));
        } catch (Exception $e) {
            $connection->rollback();
            $this->LifeSeed->handleException($e);
        }
    }

    public function deleteVoucher() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            // Obtain API key from queries and data.
            $api_key = $this->_getRequestApiKey();
            if (is_null($api_key)) {
                throw new BadRequestException();
            }

            $username = $this->_getRequestParam('username');
            if (is_null($username)) {
                throw new BadRequestException();
            }

            $success = false;
            $voucher = $this->_findVoucherByUsername($username);
            if (is_null($voucher)) {
                $error = [
                    'errors' => [
                        'username' => __('The username you provided is not found.'),
                    ],
                    'message' => __('Could not delete item'),
                ];
                throw new ForbiddenException($error);
            } else if ($voucher->user_id === $user['id'] ||
                $this->Users->is_sibling_of($user['id'], $voucher->user_id) === true) {

                // Expire rather than delete.
                #$this->Vouchers->delete($voucher);
                $voucher->state = 'expired';
                $voucher->expire = Time::now();
                if ($this->Vouchers->save($voucher)) {
                    $success = true;
                }
            }

            $this->set([
                'success'   => $success,
                '_serialize' => ['success']
            ]);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function getDeviceToken() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            $device_token = $this->_issueDeviceToken($user);
            if (!is_null($device_token)) {
                $success = true;
            } else {
                $success = false;
            }

            $this->set([
                'success'      => $success,
                'device_token' => $device_token,
                '_serialize'   => ['success', 'device_token'],
            ]);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function getConnectionProfile() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            $device_token = $this->_getRequestParam('device_token');
            if (!isset($device_token)) {
                throw new BadRequestException();
            }

            if (!$this->_checkDeviceTokenPermission($user, $device_token)) {
                throw new ForbiddenException();
            }

            $this->_getConnectionProfile($device_token);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function updateConnectionProfile() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            $device_token = $this->_getRequestParam('device_token');
            if (!isset($device_token)) {
                throw new BadRequestException();
            }

            if (!$this->_checkDeviceTokenPermission($user, $device_token)) {
                throw new ForbiddenException();
            }

            $success = $this->_updateConnectionProfile($device_token);

            if ($success) {
                $this->set([
                    'success'    => true,
                    '_serialize' => ['success']
                ]);
            } else {
                $this->set([
                    'errors'  => [],
                    'success' => false,
                    'message' => __('Update not found'),
                    '_serialize' => ['errors','success','message'],
                ]);
            }
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function getDevices() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            $device_token = $this->_getRequestParam('device_token');
            if (!isset($device_token)) {
                throw new BadRequestException();
            }

            if (!$this->_checkDeviceTokenPermission($user, $device_token)) {
                throw new ForbiddenException();
            }

            $success = false;
            $count = 0;

            $devices = $this->_getDevices($user, $device_token);
            if (!is_null($devices)) {
                $success = true;
                $count = count($devices);
            }

            $this->set([
                'success'    => $success,
                'items'      => $devices,
                'totalCount' => $count,
                '_serialize' => ['success', 'items', 'totalCount']
            ]);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    public function getUsage() {
        $user = $this->_extApiRightCheck();
        if (!$user) {
            return;
        }

        try {
            if (!$this->request->is('post')) {
                throw new MethodNotAllowedException();
            }

            $device_token = $this->_getRequestParam('device_token');
            if (!isset($device_token)) {
                throw new BadRequestException();
            }

            if (!$this->_checkDeviceTokenPermission($user, $device_token)) {
                throw new ForbiddenException();
            }

            $devices = $this->_getDevices($user, $device_token);
            if (!is_null($devices)) {
                // TODO: implement
            }

            $success = false;
            $items = [];
            $count = count($items);

            $this->set([
                'success'    => $success,
                'items'      => $items,
                'totalCount' => $count,
                '_serialize' => ['success', 'items', 'totalCount']
            ]);
        } catch (Exception $e) {
            $this->LifeSeed->handleException($e);
        }
    }

    private function _extApiRightCheck() {
        // This is a common function which will check the right for an access provider on the called action.
        // We have this as a common function but beware that each controlleer which uses it;
        // have to set the value of 'base' in order for it to work correct.

        $action = $this->request->action;

        //___AA Check Starts ___
        $user = $this->_userForApiKey($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $user_id = null;
        if ($user['group_name'] == Configure::read('group.admin')) {  //Admin
            $user_id = $user['id'];
        } elseif($user['group_name'] == Configure::read('group.ap') || //Or AP
                 $user['group_name'] == Configure::read('group.sm')){  //Or SM
            $user_id = $user['id'];

            $temp_debug = Configure::read('debug');
            // Configure::write('debug', 0); // turn off debugging

            if (!$this->Acl->check(array('model' => 'Users', 'foreign_key' => $user_id),
                                   $this->base.$action)) {  //Does AP have right?
                Configure::write('debug', $temp_debug); // return previous setting
                $this->Aa->fail_no_rights($this);
                return;
            }

            Configure::write('debug', $temp_debug); // return previous setting

        } else {
           $this->Aa->fail_no_rights($this);
           return;
        }

        return $user;
        //__ AA Check Ends ___
    }

    private function _userForApiKey($controller) {
        return $this->_checkIfValid($controller);
    }

    private function _getRequestParam($key) {
        $value = $this->request->data($key);
        if (is_null($value)) {
            $value = $this->request->query($key);
        }
        return $value;
    }

    private function _getRequestApiKey() {
        $api_key_str = $this->_getRequestParam('api_key');
        if (is_null($api_key_str)) {
            return null;
        }

        $query = $this->ExternalApiKeys->find();
        $query->contain('Users');
        $query->contain('Realms');
        $query->contain('Profiles');
        $query->where(['ExternalApiKeys.api_key' => $api_key_str]);
        $api_key = $query->first();
        return $api_key;
    }

    private function _checkIfValid($controller) {
        //First we will ensure there is a api_key in the request
        $api_key_obj = $this->_getRequestApiKey();
        if (is_null($api_key_obj)) {
            $result = array(
                'errors'  => [],
                'success' => false,
                'message' => __('ApiKey invalid'),
            );
        } else {
            $api_key = $api_key_obj->api_key;
            if ($api_key != false) {
                if (strlen($api_key) != 36) {
                    $result = array(
                        'errors'  => [],
                        'success' => false,
                        'message' => __('ApiKey in wrong format'),
                    );
                } else {
                    //Find the owner of the api_key
                    $result = $this->_findApiKeyOwner($api_key);
                }
            } else {
                $result = array(
                    'errors'  => [],
                    'success' => false,
                    'message' => __('ApiKey missing'),
                );
            }
        }

        //If it failed - set the controller up
        if ($result['success'] == false) {
            $this->set(array(
                'success'   => $result['success'],
                'errors'    => $result['errors'],
                'message'   => $result['message'],
                '_serialize' => array('success', 'message','errors')
            ));
            return false;
        } else {
            return $result['user']; //Return the user detail
        }
    }

    private function _findApiKeyOwner($api_key) {
        $query = $this->ExternalApiKeys->find();
        $query->contain(['Users', 'Users.Groups']);
        $query->where(['ExternalApiKeys.api_key' => $api_key]);
        $external_api_key = $query->first();
        $user = $external_api_key->user;
       
        if (!$user) {
            return array(
                'errors'  => [],
                'success' => false,
                'message' => __('No user for api_key'),
            );
        } else {
            //Check if account is active or not:
            if ($user->active==0) {
                return array(
                    'success' => false,
                    'message' => __('Account disabled'),
                );
            }else{
                $user = array(
                    'id'            => $user->id,
                    'group_name'    => $user->group->name,
                    'group_id'      => $user->group->id,
                    'monitor'       => $user->monitor,
                );  
                return array('success' => true, 'user' => $user);
            }
        }
    }

    private function _findPermanentUserByUsername($username) {
        $query = $this->PermanentUsers->find();
        $query->where(['PermanentUsers.username' => $username]);
        return $query->first();
    }

    private function _findVoucherByUsername($username) {
        $query = $this->Vouchers->find();
        $query->where(['Vouchers.name' => $username]);
        return $query->first();
    }

    private function _findExistingUserByUsername($username) {
        $permanent_user = $this->_findPermanentUserByUsername($username);
        $permanent_user_id = is_null($permanent_user) ? null : $permanent_user->id;

        $voucher = $this->_findVoucherByUsername($username);
        $voucher_id = is_null($voucher) ? null : $voucher->id;

        return [
            'permanent_user'    => $permanent_user,
            'voucher'           => $voucher,
            'permanent_user_id' => $permanent_user_id,
            'voucher_id'        => $voucher_id,
        ];
    }

    private function _getPermanentUsers($user, $username = null, $email = null,
                                        $realm = null, $profile = null) {
        $query = $this->PermanentUsers->find();
        if (!empty($username)) {
            $query->where(['PermanentUsers.username' => $username]);
        }
        if (!empty($email)) {
            $query->where(['PermanentUsers.email' => $email]);
        }
        if (!empty($realm)) {
            $query->where(['PermanentUsers.realm_id' => $realm->id]);
        }
        if (!empty($profile)) {
            $query->where(['PermanentUsers.profile_id' => $profile->id]);
        }
		$q_r = $query->all();

        // Select elements on your own to avoid unnecessary information.
        $items = [];
        foreach ($q_r as $entity) {
            $item = [
                'username'       => $entity->username,
                'profile'        => $entity->profile,
                'email'          => $entity->email,
                'unique_id_type' => $entity->unique_id_type,
                'unique_id'      => $entity->unique_id,
                'active'         => $entity->active,
            ];
            $items[] = $item;
        }

        return $items;
    }

    private function _createPermanentUser($user, $username, $email, $realm, $profile,
                                          $unique_id_type = null, $unique_id = null) {
        $name     = '';
        $surname  = '';

        $suffix                 = $realm->suffix;
        $suffix_permanent_users = $realm->suffix_permanent_users;
        if (($suffix != '') && ($suffix_permanent_users)) {
            $username = preg_replace('/@.*$/', '', $username);
            $username = $username.'@'.$suffix;
        }

        // Check if the specified username is used for Voucher.
        $voucher = $this->_findVoucherByUsername($username);
        if (!is_null($voucher)) {
            // throw exception
            $this->_causedDuplicateUserError();
        }

        // Check if the specified username is used for PermanentUser.
        $permanent_user = $this->_findPermanentUserByUsername($username);
        if (!is_null($permanent_user)) {
            if ($permanent_user->{'active'}) {
                // throw exception
                $this->_causedDuplicateUserError();
            }

            if ($permanent_user->{'email'}          === $email &&
                $permanent_user->{'realm'}          === $realm->name &&
                $permanent_user->{'realm_id'}       === $realm->id &&
                $permanent_user->{'user_id'}        === $user['id'] &&
                $permanent_user->{'unique_id_type'} === $unique_id_type &&
                $permanent_user->{'unique_id'}      === $unique_id) {
                
                $entity = $permanent_user;
                $entity->{'active'}     = true;
                $entity->{'profile'}    = $profile->name;
                $entity->{'profile_id'} = $profile->id;

                if ($this->PermanentUsers->save($entity)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                // throw exception
                $this->_causedDuplicateUserError();
            }
        } else {
            $data = [
                'username'       => $username,
                'email'          => $email,
                'auth_type'      => 'sql',
                'active'         => true,
                'name'           => $name,
                'surname'        => $surname,
                'realm'          => $realm->name,
                'realm_id'       => $realm->id,
                'token'          => '',
                'user_id'        => $user['id'],
                #'sub_group_id'   => $sub_group_id,
                'profile'        => $profile->name,
                'profile_id'     => $profile->id,
                'unique_id_type' => $unique_id_type,
                'unique_id'      => $unique_id,
            ];

            $entity = $this->PermanentUsers->newEntity($data);
            if ($this->PermanentUsers->save($entity)) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    private function _getVouchers($user, $username = null, $email = null,
                                        $realm = null, $profile = null) {
        $query = $this->Vouchers->find();
        if (!empty($username)) {
            $query->where(['Vouchers.name' => $username]);
        }
        if (!empty($email)) {
            $query->where(['Vouchers.email' => $email]);
        }
        if (!empty($realm)) {
            $query->where(['Vouchers.realm_id' => $realm['id']]);
        }
        if (!empty($profile)) {
            $query->where(['Vouchers.profile_id' => $profile['id']]);
        }
		$q_r = $query->all();

        // Select elements on your own to avoid unnecessary information.
        $items = [];
        foreach ($q_r as $entity) {
            $item = [
                'username'       => $entity->name,
                'profile'        => $entity->profile,
                'email'          => $entity->email,
                'unique_id_type' => $entity->unique_id_type,
                'unique_id'      => $entity->unique_id,
                'status'         => $entity->status,
                'expire'         => $entity->expire,
            ];
            $items[] = $item;
        }

        return $items;
	}

    private function _createVouchers($user, $usernames, $emails, $realm_entity,
                                     $profile_entity, $unique_id_type, $unique_id) {
        // Set user_id
        $this->request->data['user_id'] = $user['id'];

        // Most of the processing below is defined within Vouchers::add().

        // If single_field is true, the account name is a sequence of words;
        // if false, it is a sequence of numbers.
        //
        // If single_field is true, the username is generated by
        // VoucherGeneratorComponent::_adjective_noun().
        // There are 395 x 290 = 114,550 possible combinations.

        $this->request->data['single_field'] = 'true';

        $check_items = array(
			'activate_on_login',
            #'never_expire'
		);

        foreach($check_items as $i){
            if(isset($this->request->data[$i])){
                $this->request->data[$i] = 1;
            } else {
                $this->request->data[$i] = 0;
            }
        }

        // If it is expiring; set it in the correct format
        $expire = $this->request->data('expire');
        if (!is_null($expire)) {
            $newDate = date_create_from_format(self::VOUCHER_EXPIRE_FORMAT, $expire);
            if ($newDate === false) {
                $error = [
                    'errors' => [
                        'expire' => __('failed to parse value'),
                    ],
                    'message' => __('Could not create item'),
                ];
                throw new BadRequestException($error);
            }

            $this->request->data['expire'] = $newDate;
            $this->request->data['never_expire'] = 0;
        } else {
            $this->request->data['never_expire'] = 1;
        }

        //---Set Realm related things---
        if ($realm_entity) {
            //---Set Realm related things---
            $this->request->data['realm']    = $realm_entity->name;
            $this->request->data['realm_id'] = $realm_entity->id;

            //Test to see if we need to auto-add a suffix
            $suffix          = $realm_entity->suffix;
            $suffix_vouchers = $realm_entity->suffix_vouchers;
        } else {
            $error = [
                'errors' => [
                ],
                'message' => __('realm not found in DB or not supplied'),
            ];
            throw new ForbiddenException($error);
        }

        //---Set profile related things---
        if ($profile_entity) {
            $this->request->data['profile']   = $profile_entity->name;
            $this->request->data['profile_id']= $profile_entity->id;
        } else {
            $error = [
                'errors' => [
                ],
                'message' => __('profile not found in DB or not supplied'),
            ];
            throw new ForbiddenException($error);
        }

        //--Here we start with the work!
        $qty        = count($emails);
        if (!is_null($usernames)) {
            $qty    = min($qty, count($usernames));
        }
        $counter    = 0;
        $repl_fields= [
            'id', 'name', 'email', 'batch', 'created', 'extra_name', 'extra_value',
            'realm', 'realm_id', 'profile', 'profile_id', 'expire', 'time_valid'
        ];

        $created    = [];

        while ($counter < $qty) {
            if ($this->request->data['single_field'] == 'false') {
                if (is_null($usernames)) {
                    // Since single_field is fixed to true,
                    // the processing of this block is not used.
                    $p = '';
                    if (array_key_exists('precede', $this->request->data)) {
                        if($this->request->data['precede'] !== ''){
                            $p = $this->request->data['precede'];
                        }
                    }

                    $s = '';
                    if (($suffix != '') && ($suffix_vouchers)) {
                        $s = $suffix;
                    }
                    $un     = $this->VoucherGenerator->generateUsernameForVoucher($p,$s);
                } else {
                    $un = $usernames[$counter];
                    if (($suffix != '') && ($suffix_vouchers)) {
                        $un = preg_replace('/@.*$/', '', $un);
                        $un = $un.'@'.$suffix;
                    }
                }
                $pwd    = $this->VoucherGenerator->generatePassword();
                $this->request->data['name']      = $un;
                $this->request->data['password']  = $pwd;
            } else {
                if (is_null($usernames)) {
                    $pwd = $this->VoucherGenerator->generateVoucher();
                    if (($suffix != '') && ($suffix_vouchers)) {
                        $pwd = $pwd.'@'.$suffix;
                    }
                } else {
                    $pwd = $usernames[$counter];
                    if (($suffix != '') && ($suffix_vouchers)) {
                        $pwd = preg_replace('/@.*$/', '', $pwd);
                        $pwd = $pwd.'@'.$suffix;
                    }
                }
                $this->request->data['name']      = $pwd;
                $this->request->data['password']  = $pwd;
            }

            // Check if the specified username is used for PermanentUser.
            $permanent_user = $this->_findPermanentUserByUsername(
                                        $this->request->data['name']);
            if (!is_null($permanent_user)) {
                if (is_null($usernames)) {
                    continue;
                } else {
                    // throw exception
                    $this->_causedDuplicateUserError();
                }
            }

            $entity = $this->Vouchers->newEntity($this->request->data());
            $entity->email = $emails[$counter];
            if (!$this->Vouchers->save($entity)) {
                if (!is_null($usernames)) {
                    // throw exception
                    $this->_causedDuplicateUserError();
                }
            }

            if (!$entity->errors()) {
                // Hopefully taking care of duplicates is as simple as this :-)
                $counter = $counter + 1;
                $row     = array();
                foreach ($repl_fields as $field) {
                    $row["$field"] = $entity->{"$field"};
                }
                array_push($created, $row);
            }
        }

        return $created;
    }

    private function _issueDeviceToken($user) {
        // While identifying the user using the username,
        // retrieve (+ create) sta_info using the specified device information
        // and then obtain the device token.

        $sta_info = $this->_findOrCreateStaInfo();
        if (!is_null($sta_info)) {
            return $sta_info->device_token;
        }

        return null;
    }

    private function _findOrCreateStaInfo() {
        // Obtain API key from queries and data.
        $api_key = $this->_getRequestApiKey();
        if (is_null($api_key)) {
            return;
        }

        $realm_id = $api_key->realm_id;

        // Obtain the information necessary to create StaInfo from the request.
        $values = [];
        $keys = ['device_type', 'device_unique_id', 'username'];
        foreach ($keys as $key) {
            $values[$key] = $this->_getRequestParam($key);
        }

        $existing_user             = $this->_findExistingUserByUsername($values['username']);
        $permanent_user            = $existing_user['permanent_user'];
        $permanent_user_id         = $existing_user['permanent_user_id'];
        $voucher                   = $existing_user['voucher'];
        $voucher_id                = $existing_user['voucher_id'];

        if (!$permanent_user && !$voucher) {
            // If there is no user information, null is returned.
            return null;
        }

        // Check the realm and user status.
        $sub_group_id = null;
        if (!is_null($permanent_user)) {
            if ($realm_id != $permanent_user->realm_id ||
                !$permanent_user->active) {
                throw new ForbiddenException();
            }
            // Obtain subgroups.
            $sub_group_id = $permanent_user->sub_group_id;
        }
        if (!is_null($voucher)) {
            if ($realm_id != $voucher->realm_id ||
                (!is_null($voucher->expire) && Time::now() > $voucher->expire)) {
                throw new ForbiddenException();
            }
        }

        // Search for registered terminal information.
        $query = $this->StaInfos->find();
        $query->contain('StaConfigs');
        $query->where(['StaInfos.device_type' => $values['device_type']]);
        $query->where(['StaInfos.device_unique_id' => $values['device_unique_id']]);
        if (!is_null($permanent_user_id)) {
            $query->contain('PermanentUsers');
            $query->where(['StaInfos.permanent_user_id' => $permanent_user_id]);
        }
        if (!is_null($voucher_id)) {
            $query->contain('Vouchers');
            $query->where(['StaInfos.voucher_id' => $voucher_id]);
        }
        $sta_infos = $query->all();

        $target_sta_info = null;
        foreach ($sta_infos as $sta_info) {
            $sta_configs = $sta_info->sta_configs;
            if (count($sta_configs) === 0) {
                continue;
            }

            // Sort in descending order according to the value of expire in sta_config.
            // Note: It may not be necessary to sort because expire is not used here.
            $sta_configs_expires = array_column($sta_configs, 'expire');
            array_multisort($sta_configs_expires, SORT_DESC, $sta_configs);

            foreach ($sta_configs as $sta_config) {
                // Note: The expire check is not performed here to prevent
                // multiple sta_info records from being generated for a single STA.

                if (!is_null($sub_group_id)) {
                    // If the configuration information is tied to each subgroup
                    // Note: processing for sub_group may not be necessary
                    $query = $this->StaConfigsSubGroups->find();
                    $query->contain('SubGroups');
                    $query->where(['StaConfigsSubGroups.sta_config_id' =>
                                   $sta_config->id]);
                    $query->where(['SubGroups.id' => $sub_group_id]);
                    $query->where(['SubGroups.realm_id' => $realm_id]);
                    if ($query->count() > 0) {
                        $target_sta_info   = $sta_info;
                        break;
                    }
                }

                // Check the match status of the configuration information
                // associated with the terminal information with the realm.
                $query = $this->StaConfigsRealms->find();
                $query->where(['StaConfigsRealms.sta_config_id' =>
                               $sta_config->id]);
                $query->where(['StaConfigsRealms.realm_id' => $realm_id]);
                if (!is_null($query->first())) {
                    $target_sta_info   = $sta_info;
                    break;
                }
            }
        }

        if (is_null($target_sta_info)) {
            // If it is an unregistered sta, attempt to register it.
            $device_token = $this->_generateUuid();
            $short_unique_id = substr(hash('sha256', $device_token), 0, 8);

            $profile_id = $api_key->profile_id;

            // Create a sta_info record.
            $data = [
                'device_type'       => $values['device_type'],
                'device_unique_id'  => $values['device_unique_id'],
                'device_token'      => $device_token,
                'short_unique_id'   => $short_unique_id,
                'permanent_user_id' => $permanent_user_id,
                'voucher_id'        => $voucher_id,
            ];

            $entity = $this->StaInfos->newEntity($data);
            $this->StaInfos->save($entity);
            $target_sta_info = $this->StaInfos->get($entity->id);

            $sta_config = null;
            if (!is_null($sub_group_id)) {
                // Find connection settings from the organizational
                // department to which the user belongs.
                $query = $this->StaConfigsSubGroups->find();
                $query->contain('StaConfigs');
                $query->contain('SubGroups');
                $query->where(['StaConfigsSubGroups.sub_group_id' => $sub_group_id]);
                $query->where(['SubGroups.realm_id' => $realm_id]);
                $query->where(['StaConfigs.expire >' => Time::now()]);
                $query->order(['StaConfigs.expire' => 'DESC']);
                $sta_config = $query->first();
            }

            if (is_null($sta_config)) {
                // Find connection settings from the realm to which the user belongs.
                $query = $this->StaConfigsRealms->find();
                $query->contain('StaConfigs');
                $query->where(['StaConfigsRealms.realm_id' => $realm_id]);
                $query->where(['StaConfigs.expire >' => Time::now()]);
                $query->order(['StaConfigs.expire' => 'DESC']);
                $sta_config = $query->first();
            }

            if (is_null($sta_config)) {
                $target_sta_info = null;
            } else {
                // Create a record for sta_configs_sta_infos.
                $data = [
                    'sta_config_id' => $sta_config->id,
                    'sta_info_id'   => $target_sta_info->id,
                ];

                $entity = $this->StaConfigsStaInfos->newEntity($data);
                if (!$this->StaConfigsStaInfos->save($entity)) {
                    $target_sta_info = null;
                }
            }
        }

        if (!is_null($target_sta_info)) {
            return $target_sta_info;
        } else {
            return null;
        }
    }

    private function _generateUuid() {
        return $this->LifeSeed->generateUuid();
    }

    private function _getStaInfoByDeviceToken($device_token) {
        $query = $this->StaInfos->find();
        $query->where(['StaInfos.device_token' => $device_token]);
        return $query->first();
    }

    private function _checkDeviceTokenPermission($user, $device_token) {
        $sta_info = $this->_getStaInfoByDeviceToken($device_token);
        if (is_null($sta_info)) {
            return false;
        }

        $success = false;
        if (!is_null($sta_info->permanent_user_id)) {
            $query = $this->PermanentUsers->find();
            $query->contain('Realms');
            $query->where(['PermanentUsers.id' => $sta_info->permanent_user_id]);
            $permanent_user = $query->first();
            if (is_null($permanent_user)) {
                return false;
            }

            if (!$this->LifeSeed->checkEntityReadPermByUserAndGroup(
                    $permanent_user->real_realm, $user)) {
                return false;
            }

            $success = true;
        }

        if (!is_null($sta_info->voucher_id)) {
            $query = $this->Vouchers->find();
            $query->contain('Realms');
            $query->where(['Vouchers.id' => $sta_info->voucher_id]);
            $voucher = $query->first();
            if (is_null($voucher)) {
                return false;
            }

            if (!$this->LifeSeed->checkEntityReadPermByUserAndGroup(
                    $voucher->real_realm, $user)) {
                return false;
            }

            $success = true;
        }

        return $success;
    }

    private function _getConnectionProfile($device_token) {
        $sta_info = $this->_getStaInfoByDeviceToken($device_token);
        if (is_null($sta_info)) {
            $this->set([
                'success'   => false,
                '_serialize' => ['success']
            ]);
            return;
        }

        $profile_type = null;

        switch ($sta_info->device_type) {
        case self::DEVICE_TYPE_ANDROID:
        case self::DEVICE_TYPE_IOS:
            $profile_type = self::PROFILE_TYPE_EAP_CONFIG;
            break;
        case self::DEVICE_TYPE_WINDOWS:
            $profile_type = self::PROFILE_TYPE_WINDOWS;
            break;
        case self::DEVICE_TYPE_MACOS:
            $profile_type = self::PROFILE_TYPE_MOBILECONFIG;
            break;
        default:
            break;
        }

        if (is_null($profile_type)) {
            $this->set([
                'success'   => false,
                '_serialize' => ['success']
            ]);
            return;
        }

        // When a device token is obtained, a connection profile is generated
        // and returned.
        $data = $this->_generateConnectionProfile($device_token, $profile_type);

        if ($this->request->is('json') || $this->request->is('xml')) {
            $data = base64_encode($data);

            $this->set([
                'success'   => true,
                'type'      => $profile_type,
                'data'      => $data,
                '_serialize' => ['success', 'type', 'data']
            ]);
        } else {
            $this->autoRender = false;

            switch ($profile_type) {
            case self::PROFILE_TYPE_EAP_CONFIG:
                // Sample:
                //   Content-Type: application/eap-config
                //   Content-Disposition: attachment; filename="profile.eap-config"

                $this->response->type([self::PROFILE_TYPE_EAP_CONFIG =>
                                       self::CONTENT_TYPE_EAP_CONFIG]);
                $this->response = $this->response->withType(self::PROFILE_TYPE_EAP_CONFIG);
                $this->response->download(self::DOWNLOAD_FILE_NAME_EAP_CONFIG);
                $this->response->body($data);
                break;
            case self::PROFILE_TYPE_MOBILECONFIG:
                // Sample:
                //   Content-Type: application/x-apple-aspen-config
                //   Content-Disposition: attachment; filename="profile.mobileconfig"

                $this->response->type([self::PROFILE_TYPE_MOBILECONFIG =>
                                       self::CONTENT_TYPE_MOBILECONFIG]);
                $this->response = $this->response->withType(
                                       self::PROFILE_TYPE_MOBILECONFIG);
                $this->response->download(self::DOWNLOAD_FILE_NAME_MOBILECONFIG);
                $this->response->body($data);
                break;
            case self::PROFILE_TYPE_WINDOWS:
                // In the case of profiles for Windows, they do not come here
                // because they are made to step on the link of the xml extension
                // via "ms-settings:wifi-provisioning".
                // <a href="ms-settings:wifi-provisioning?uri=http://example.com/ProvisioningDoc.xml">Install</a>

                $this->response->type([self::PROFILE_TYPE_WINDOWS =>
                                       self::CONTENT_TYPE_WINDOWS]);
                $this->response = $this->response->withType(
                                       self::PROFILE_TYPE_WINDOWS);
                $this->response->download(self::DOWNLOAD_FILE_NAME_WINDOWS);
                $this->response->body($data);
                break;
            default:
                break;
            }
        }
    }

    private function _generateConnectionProfile($device_token, $type = 'eap-config') {
        $query = $this->StaInfos->find();
        $query->contain('PermanentUsers');
        $query->contain('Vouchers');
        $query->contain('StaConfigs');
        $query->where(['StaInfos.device_token' => $device_token]);
        $sta_info  = $query->first();
        if (is_null($sta_info)) {
            throw new NotFoundException();
        }

        $query = $this->StaConfigsStaInfos->find();
        $query->contain('StaConfigs');
        $query->where(['StaConfigsStaInfos.sta_info_id' => $sta_info->id]);
        $query->order(['StaConfigs.expire' => 'DESC']);
        $sta_config_sta_info = $query->first();
        if (is_null($sta_config_sta_info)) {
            throw new NotFoundException();
        }

        $sta_config = $sta_config_sta_info->sta_config;
        if (is_null($sta_config)) {
            throw new NotFoundException();
        }

        $encoding_scheme = $this->EncodingSchemes->get($sta_config->encoding_scheme_id);
        if (is_null($encoding_scheme)) {
            throw new NotFoundException();
        }

        // Domain name embedded in the server cert.
        $aaa_fqdn = Configure::read(self::CONFIG_KEY_RADIUS_SERVER_CN);

        // The shorter expiration time of the hmac key and sta_config
        // shall be the expiration time of the profile.
        $hmac_expire      = $encoding_scheme->expire;
        $config_expire    = $sta_config->expire;
        if ($hmac_expire >= $config_expire) {
            $expire = $config_expire;
        } else {
            $expire = $hmac_expire;
        }

        if (Time::now() > $expire) {
            // If all connection settings have expired
            throw new NotFoundException();
        }

        // Generate UserName for profiles.
        if (isset($sta_info->permanent_user)) {
            $base_username    = $sta_info->permanent_user->username;
        } else if (isset($sta_info->voucher)) {
            $base_username    = $sta_info->voucher->name;
        } else {
            throw new NotFoundException();
        }

        $device_unique_id = $sta_info->short_unique_id;
        $hmac_key_suffix  = $encoding_scheme->suffix;
        $salt             = $sta_config_sta_info->salt;
        $user_id          = $this->_generateProfileUsername($base_username,
                                                            $device_unique_id,
                                                            $hmac_key_suffix,
                                                            $expire, $salt);

        $anon_id          = preg_replace('/^.*@/', 'anonymous@', $user_id);

        // Generate password from UserName and hmac key.
        $passwd           = $this->_calcHmacPassword($user_id, $hmac_key_suffix);

        // Read the ca certificate from the file.
        $ca_cert          = $this->_readCaCert();

        $config_params = [
            'home_domain'     => $sta_config->home_domain,
            'expire'          => $expire,
            'aaa_fqdn'        => $aaa_fqdn,
            'eap_method'      => $sta_config->eap_method,
            'ca_cert'         => $ca_cert,
            'user_id'         => $user_id,
            'passwd'          => $passwd,
            'anon_id'         => $anon_id,
            'ssid'            => $sta_config->ssid,
            'rcoi'            => $sta_config->rcoi,
            'friendly_name'   => $sta_config->friendly_name,
        ];

        if ($type === self::PROFILE_TYPE_EAP_CONFIG) {
            return $this->_generateEapConfigProfile($config_params);
        } else if ($type === self::PROFILE_TYPE_WINDOWS) {
            return $this->_generateWindowsProfile($config_params);
        } else if ($type === self::PROFILE_TYPE_MOBILECONFIG) {
            return $this->_generateMobileconfigProfile($config_params);
        }

        return null;
    }

    private function _generateProfileUsername($base_username, $device_unique_id,
                                              $hmac_key_suffix, $expire, $salt) {
        $version          = self::PROFILE_USERNAME_VERSION;
        $expire_str       = sprintf("%x%x%x", $expire->year, $expire->month, $expire->day);

        $user_id          = sprintf("%d%s%s%s%s:%s", $version, $device_unique_id,
                                    $hmac_key_suffix, $expire_str, $salt,
                                    $base_username);
        return $user_id;
    }

    private function _calcHmacPassword($user_id, $hmac_key_suffix) {
        $hmac_key_dir    = Configure::read(self::CONFIG_KEY_HMAC_KEY_DIR);
        $hmac_key_prefix = Configure::read(self::CONFIG_KEY_HMAC_KEY_PREFIX);
        $hmac_key_ext    = Configure::read(self::CONFIG_KEY_HMAC_KEY_EXT);

        $hmac_key_dir = preg_replace("/\/$/", '', $hmac_key_dir);
        if (strlen($hmac_key_ext) > 0) {
            $hmac_key_ext = preg_replace("/^\.*/", '.', $hmac_key_ext);
        }

        $hmac_key_path   = sprintf("%s/%s%s%s",
                                   $hmac_key_dir, $hmac_key_prefix,
                                   $hmac_key_suffix, $hmac_key_ext);
        if (!file_exists($hmac_key_path)) {
            throw new NotFoundException();
        }

        $hmac_key = file_get_contents($hmac_key_path);
        if (is_null($hmac_key) || strlen($hmac_key) === 0) {
            throw new ForbiddenException();
        }

        $passwd = hash_hmac('sha256', $user_id, $hmac_key);

        return $passwd;
    }

    private function _readCaCert() {
        $ca_cert_path     = Configure::read(self::CONFIG_KEY_RADIUS_CA_CERT_PATH);
        if (!file_exists($ca_cert_path)) {
            // TODO: error handling
            return null;
        }

        $ca_cert          = file_get_contents($ca_cert_path);
        $ca_cert          = str_replace(array("\r\n", "\r", "\n"), "\n", $ca_cert);
        $ca_cert          = explode("\n", $ca_cert);
        $ca_cert          = preg_replace(['/.*BEGIN CERTIFICATE.*/',
                                          '/.*END CERTIFICATE.*/'], ['', ''], $ca_cert);
        $ca_cert          = join('', $ca_cert);

        return $ca_cert;
    }

    private function _convert_eap_config_profile_expire($expire) {
        $expire->setTimezone(new \DateTimeZone('UTC'));
        $expiration_time  = $expire->i18nFormat("yyyy-MM-dd'T'HH:mm:ssX");
        return $expiration_time;
    }

    private function _convert_eap_config_params($config_params) {
        $eap_config_params = [
            'home_domain'     => '',
            'expiration_date' => '',
            'eap_method_type' => false,
            'inner_auth'      => false,
            'ca_cert'         => '',
            'aaa_fqdn'        => '',
            'anon_id'         => '',
            'user_id'         => '',
            'passwd'          => '',
            'ssid'            => '',
            'ois'             => [],
            'friendly_name'   => '',
        ];

        // Expiration date sample: 2023-01-05T00:00:00Z
        $expiration_time = $this->_convert_eap_config_profile_expire(
                               $config_params['expire']);
        $eap_config_params['expiration_date'] = $expiration_time;

        // Convert eap_config values to parameters in data.
        $eap_method = $config_params['eap_method'];
        if ($eap_method === 'peap') {
            $eap_config_params['eap_method_type'] = self::EAP_METHOD_TYPE_PEAP;
            $eap_config_params['inner_auth'] = [
                'eap_method_type' => self::EAP_METHOD_TYPE_EAP_MSCHAPV2,
            ];
        } else if ($eap_method === 'eap-tls') {
            $eap_config_params['eap_method_type'] = self::EAP_METHOD_TYPE_TLS;
        } else if (preg_match('/^eap-ttls/', $eap_method)) {
            $eap_config_params['eap_method_type'] = self::EAP_METHOD_TYPE_EAP_TTLS;

            if (preg_match('/\/pap$/', $eap_method)) {
                $eap_config_params['inner_auth'] = [
                    'non_eap_auth_method_type' => self::NON_EAP_AUTH_METHOD_TYPE_PAP,
                ];
            } else if (preg_match('/\/mschap$/', $eap_method)) {
                $eap_config_params['inner_auth'] = [
                    'non_eap_auth_method_type' => self::NON_EAP_AUTH_METHOD_TYPE_MSCHAP,
                ];
            } else if (preg_match('/\/mschapv2$/', $eap_method)) {
                $eap_config_params['inner_auth'] = [
                    'non_eap_auth_method_type' => self::NON_EAP_AUTH_METHOD_TYPE_MSCHAPV2,
                ];
            }
        }

        // Roaming Consortium Organization Identifiers
        // (comma-separated, no space)
        $eap_config_params['ois'] = explode(',', $config_params['rcoi']);

        $keys = [
            // Home Domain Name helps user devices distinguish home/foreign ANP.
            'home_domain',
            // Domain name embedded in the server cert.
            'aaa_fqdn',
            'ca_cert',
            'user_id',
            'passwd',
            'anon_id',
            // SSID
            'ssid',
            // Friendly Name is displayed on user devices.
            'friendly_name',
        ];

        foreach ($keys as $key) {
            $eap_config_params[$key] = $config_params[$key];
        }

        return $eap_config_params;
    }

    private function _generateEapConfigProfile($config_params) {
        $loader = new \Twig\Loader\FilesystemLoader(self::PROFILE_TEMPLATE_DIR);
        $twig = new \Twig\Environment($loader);

        $eap_config_params = $this->_convert_eap_config_params($config_params);
        $profile_data = $twig->render(self::PROFILE_TEMPLATE_FILE_EAP_CONFIG,
                                      $eap_config_params);
        return $profile_data;
    }

    private function _convert_windows_params($config_params) {
        $windows_params = [
            'home_domain'     => '',
            'expiration_date' => '',
            'eap_method_type' => false,
            'inner_auth'      => false,
            'ca_cert'         => '',
            'aaa_fqdn'        => '',
            'anon_id'         => '',
            'user_id'         => '',
            'passwd'          => '',
            'ssid'            => '',
            'ois'             => [],
            'friendly_name'   => '',
        ];

        // Expiration date sample: 2023-01-05T00:00:00Z
        #$expiration_time = $this->_convert_windows_profile_expire(
        #                       $config_params['expire']);
        #$windows_params['expiration_date'] = $expiration_time;

        // Convert windows values to parameters in data.

        // Hotfix: MSCHAPv2 problem with Windows 11 22h2 Enterprise edition.
        //         https://hgot07.hatenablog.com/entry/2022/10/13/160604
        $windows_params['eap_method_type'] = self::EAP_METHOD_TYPE_EAP_TTLS;
        $windows_params['ttls_inner_authentication'] = self::TTLS_INNER_AUTHENTICATION_PAP;

/*
        $eap_method = $config_params['eap_method'];
        if ($eap_method === 'peap') {
            $windows_params['eap_method_type'] = self::EAP_METHOD_TYPE_PEAP;
            $windows_params['ttls_inner_authentication'] =
                    self::TTLS_INNER_AUTHENTICATION_MSCHAPV2;
        } else if ($eap_method === 'eap-tls') {
            $windows_params['eap_method_type'] = self::EAP_METHOD_TYPE_TLS;
        } else if (preg_match('/^eap-ttls/', $eap_method)) {
            $windows_params['eap_method_type'] = self::EAP_METHOD_TYPE_EAP_TTLS;

            if (preg_match('/\/pap$/', $eap_method)) {
                $windows_params['ttls_inner_authentication'] =
                    self::TTLS_INNER_AUTHENTICATION_PAP;
            } else if (preg_match('/\/mschap$/', $eap_method)) {
                $windows_params['ttls_inner_authentication'] =
                    self::TTLS_INNER_AUTHENTICATION_MSCHAP;
            } else if (preg_match('/\/mschapv2$/', $eap_method)) {
                $windows_params['ttls_inner_authentication'] =
                    self::TTLS_INNER_AUTHENTICATION_MSCHAPV2;
            }
        }
*/

        // Roaming Consortium Organization Identifiers
        // (comma-separated, no space)
        $windows_params['ois'] = explode(',', $config_params['rcoi']);

        $keys = [
            // Home Domain Name helps user devices distinguish home/foreign ANP.
            'home_domain',
            // Domain name embedded in the server cert.
            'aaa_fqdn',
            'ca_cert',
            'user_id',
            'passwd',
            'anon_id',
            // SSID
            'ssid',
            // Friendly Name is displayed on user devices.
            'friendly_name',
        ];

        foreach ($keys as $key) {
            $windows_params[$key] = $config_params[$key];
        }

        // Carrier ID (Set a globally-unique UUID for the operator.)
        $carrier_id    = Configure::read(self::CONFIG_KEY_WINDOWS_CARRIER_ID);
        $subscriber_id = Configure::read(self::CONFIG_KEY_WINDOWS_SUBSCRIBER_ID);
        $author_id     = Configure::read(self::CONFIG_KEY_WINDOWS_AUTHOR_ID);

        $trusted_root_ca_hash =
            Configure::read(self::CONFIG_KEY_WINDOWS_TRUSTED_ROOT_CA_HASH);
        $trusted_root_ca_hash = preg_replace('/:/', ' ', $trusted_root_ca_hash);
        $trusted_root_ca_hash = strtolower($trusted_root_ca_hash);

        $windows_params['carrier_id']           = $carrier_id;
        $windows_params['subscriber_id']        = $subscriber_id;
        $windows_params['author_id']            = $author_id;
        $windows_params['trusted_root_ca_hash'] = $trusted_root_ca_hash;

        return $windows_params;
    }

    private function _signatureWindowsProfile($profile_data) {
        // TODO: implement

        $profile_data = preg_replace('/<\/CarrierProvisioning>/', '', $profile_data);
        $profile_data .= <<<EOS
  <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
    <SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
      <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" />
      <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" />
      <Reference URI="">
        <Transforms>
          <Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />
        </Transforms>
        <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
        <DigestValue>
        </DigestValue>
      </Reference>
    </SignedInfo>
    <SignatureValue>
    </SignatureValue>
    <KeyInfo>
      <X509Data>
        <X509Certificate>
        </X509Certificate>
      </X509Data>
    </KeyInfo>
  </Signature>
</CarrierProvisioning>
EOS;
        $profile_data = preg_replace('/\n$/', '', $profile_data);

        #var_dump($profile_data);
        #return $profile_data;

        $sudo_command_path = Configure::read(self::CONFIG_KEY_SUDO_COMMAND_PATH);
        $xmlsec1_command_path = Configure::read(self::CONFIG_KEY_XMLSEC1_COMMAND_PATH);
        $signer_cert_pfx_path = Configure::read(self::CONFIG_KEY_WINDOWS_SIGNER_CERT_PFX_PATH);
        $pfx_passwd = Configure::read(self::CONFIG_KEY_WINDOWS_PFX_PASSWD);
        $ca_file = Configure::read(self::CONFIG_KEY_WINDOWS_CA_FILE);

        $cmdopt = '';
        if (!is_null($ca_file)) {
            $cmdopt = sprintf("--trusted-pem '%s'", $ca_file);
        }

        # xmlsec1 --sign --pkcs12 $signercertpfx --pwd \"$pfxpasswd\" $cmdopt -
        $command = sprintf("%s %s --sign --pkcs12 '%s' --pwd '%s' %s -",
                           $sudo_command_path, $xmlsec1_command_path,
                           $signer_cert_pfx_path, $pfx_passwd, $cmdopt);

        $descriptorspec = [
            ['pipe', 'rb'],
            ['pipe', 'wb'],
        ];
        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $profile_data);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            #print $output."\n";
            fclose($pipes[1]);

            $return_value = proc_close($process);
            if ($return_value === 0) {
                $signed_profile_data = $output;
            }
        }

        return $signed_profile_data;
    }
    private function _generateWindowsProfile($config_params) {
        $loader = new \Twig\Loader\FilesystemLoader(self::PROFILE_TEMPLATE_DIR);
        $twig = new \Twig\Environment($loader);

        $windows_params = $this->_convert_windows_params($config_params);
        $profile_data = $twig->render(self::PROFILE_TEMPLATE_FILE_WINDOWS,
                                      $windows_params);
        #return $profile_data;

        $signed_profile_data = $this->_signatureWindowsProfile($profile_data);
        return $signed_profile_data;
    }

    private function _convert_mobileconfig_profile_expire($expire) {
        return $this->_convert_eap_config_profile_expire($expire);
    }

    private function _convert_mobileconfig_params($config_params) {
        $mobileconfig_params = [
            'home_domain'     => '',
            'expiration_date' => '',
            'eap_method_type' => false,
            'inner_auth'      => false,
            'ca_cert'         => '',
            'aaa_fqdn'        => '',
            'anon_id'         => '',
            'user_id'         => '',
            'passwd'          => '',
            'ssid'            => '',
            'ois'             => [],
            'friendly_name'   => '',
        ];

        // Expiration date sample: 2023-01-05T00:00:00Z
        $expiration_time = $this->_convert_mobileconfig_profile_expire(
                               $config_params['expire']);
        $mobileconfig_params['expiration_date'] = $expiration_time;

        // Convert mobileconfig values to parameters in data.
        $eap_method = $config_params['eap_method'];
        if ($eap_method === 'peap') {
            $mobileconfig_params['eap_method_types'] = [self::EAP_METHOD_TYPE_PEAP];
            $mobileconfig_params['inner_auth'] = [
                'eap_method_type' => self::EAP_METHOD_TYPE_EAP_MSCHAPV2,
            ];
        } else if ($eap_method === 'eap-tls') {
            $mobileconfig_params['eap_method_types'] = [self::EAP_METHOD_TYPE_TLS];
        } else if (preg_match('/^eap-ttls/', $eap_method)) {
            $mobileconfig_params['eap_method_types'] = [self::EAP_METHOD_TYPE_EAP_TTLS];

            if (preg_match('/\/pap$/', $eap_method)) {
                $mobileconfig_params['ttls_inner_authentication'] =
                    self::TTLS_INNER_AUTHENTICATION_PAP;
            } else if (preg_match('/\/mschap$/', $eap_method)) {
                $mobileconfig_params['ttls_inner_authentication'] =
                    self::TTLS_INNER_AUTHENTICATION_MSCHAP;
            } else if (preg_match('/\/mschapv2$/', $eap_method)) {
                $mobileconfig_params['ttls_inner_authentication'] =
                    self::TTLS_INNER_AUTHENTICATION_MSCHAPV2;
            }
        }

        // Roaming Consortium Organization Identifiers
        // (comma-separated, no space)
        $mobileconfig_params['ois'] = explode(',', $config_params['rcoi']);

        $keys = [
            // Home Domain Name helps user devices distinguish home/foreign ANP.
            'home_domain',
            // Domain name embedded in the server cert.
            'aaa_fqdn',
            'ca_cert',
            'user_id',
            'passwd',
            'anon_id',
            // SSID
            'ssid',
            // Friendly Name is displayed on user devices.
            'friendly_name',
        ];

        foreach ($keys as $key) {
            $mobileconfig_params[$key] = $config_params[$key];
        }

        $mobileconfig_params['payload_display_name'] =
            Configure::read(self::CONFIG_KEY_MOBILECONFIG_PAYLOAD_DISPLAY_NAME);

        // Set a globally-unique, fixed UUID for the same kind of profile.
        $pl_uuid = Configure::read(self::CONFIG_KEY_MOBILECONFIG_PL_UUID);
        $pl_uuid = strtoupper($pl_uuid);
        $plid_prefix = Configure::read(self::CONFIG_KEY_MOBILECONFIG_PLID_PREFIX);
        $plid    = sprintf("%s%s", $plid_prefix, $pl_uuid);
        $mobileconfig_params['pl_uuid'] = $pl_uuid;
        $mobileconfig_params['plid'] = $plid;

        $uuid1 = strtoupper($this->_generateUuid());
        $uuid2 = strtoupper($this->_generateUuid());
        $mobileconfig_params['uuid1'] = $uuid1;
        $mobileconfig_params['uuid2'] = $uuid2;

        $mobileconfig_params['ca_cert_name'] =
            Configure::read(self::CONFIG_KEY_MOBILECONFIG_CA_CERT_NAME);
        $mobileconfig_params['ica_cert'] = $mobileconfig_params['ca_cert'];
        $mobileconfig_params['description'] =
            Configure::read(self::CONFIG_KEY_MOBILECONFIG_DESCRIPTION);

        #$mobileconfig_params['nai_realms_names'] = [];

        return $mobileconfig_params;
    }

    private function _signatureMobileconfigProfile($profile_data) {
        $sudo_command_path = Configure::read(self::CONFIG_KEY_SUDO_COMMAND_PATH);
        $openssl_command_path = Configure::read(self::CONFIG_KEY_OPENSSL_COMMAND_PATH);
        $chain_path = Configure::read(self::CONFIG_KEY_MOBILECONFIG_CHAIN_PATH);
        $cert_path = Configure::read(self::CONFIG_KEY_MOBILECONFIG_CERT_PATH);
        $key_path = Configure::read(self::CONFIG_KEY_MOBILECONFIG_KEY_PATH);

        if (is_null($cert_path) || is_null($key_path)) {
            return $profile_data;
        }

        if (is_null($chain_path)) {
            $command = sprintf("%s %s smime -sign -nodetach ".
                               "-signer '%s' -inkey '%s' -outform der",
                               $sudo_command_path, $openssl_command_path,
                               $server_cert_path, $server_key_path);
        } else {
            $command = sprintf("%s %s smime -sign -nodetach -certfile '%s' ".
                               "-signer '%s' -inkey '%s' -outform der",
                               $sudo_command_path, $openssl_command_path,
                               $ca_cert_path, $server_cert_path, $server_key_path);
        }

        $descriptorspec = [
            ['pipe', 'rb'],
            ['pipe', 'wb'],
        ];
        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $profile_data);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            #print $output."\n";
            fclose($pipes[1]);

            $return_value = proc_close($process);
            if ($return_value === 0) {
                $signed_profile_data = $output;
            }
        }

        return $signed_profile_data;
    }

    private function _generateMobileconfigProfile($config_params) {
        $loader = new \Twig\Loader\FilesystemLoader(self::PROFILE_TEMPLATE_DIR);
        $twig = new \Twig\Environment($loader);

        $mobileconfig_params = $this->_convert_mobileconfig_params($config_params);
        $profile_data = $twig->render(self::PROFILE_TEMPLATE_FILE_MOBILECONFIG,
                                      $mobileconfig_params);
        $signed_profile_data = $this->_signatureMobileconfigProfile($profile_data);

        return $signed_profile_data;
    }

    private function _updateConnectionProfile($device_token) {
        $query = $this->StaInfos->find();
        $query->contain('PermanentUsers');
        $query->contain('Vouchers');
        $query->contain('StaConfigs');
        $query->where(['StaInfos.device_token' => $device_token]);
        $sta_info = $query->first();
        if (is_null($sta_info)) {
            return false;
        }

        $query = $this->StaConfigsStaInfos->find();
        $query->contain('StaConfigs');
        $query->where(['StaConfigsStaInfos.sta_info_id' => $sta_info->id]);
        $query->order(['StaConfigs.expire' => 'DESC']);
        $sta_config_sta_info = $query->first();
        $sta_config = $sta_config_sta_info->sta_config;
        if (is_null($sta_config)) {
            return false;
        }

        $realm_id = null;
        $sub_group_id = null;
        $new_sta_config = null;

        $permanent_user = $sta_info->permanent_user;
        if (!is_null($permanent_user)) {
            $realm_id     = $permanent_user->realm_id;
            $sub_group_id = $permanent_user->sub_group_id;
        }

        $voucher = $sta_info->voucher;
        if (!is_null($voucher)) {
            $realm_id     = $voucher->realm_id;
        }

        if (!is_null($realm_id)) {
            if (!is_null($sub_group_id)) {
                // Find new connection settings from the organizational
                // department to which the user belongs.
                $query = $this->StaConfigsSubGroups->find();
                $query->contain('StaConfigs');
                $query->contain('SubGroups');
                $query->where(['StaConfigsSubGroups.sub_group_id' => $sub_group_id]);
                $query->where(['SubGroups.realm_id' => $realm_id]);
                $query->where(['StaConfigs.expire >' => $sta_config->expire]);
                $query->where(['StaConfigs.expire >' => Time::now()]);
                $query->order(['StaConfigs.expire' => 'DESC']);
                $q_r = $query->first();
                if ($q_r) {
                    $new_sta_config = $q_r->sta_config;
                }
            }

            if (is_null($new_sta_config)) {
                // Find new connection settings from the realm to which the user belongs.
                $query = $this->StaConfigsRealms->find();
                $query->contain('StaConfigs');
                $query->where(['StaConfigsRealms.realm_id' => $realm_id]);
                $query->where(['StaConfigs.expire >' => $sta_config->expire]);
                $query->where(['StaConfigs.expire >' => Time::now()]);
                $query->order(['StaConfigs.expire' => 'DESC']);
                $q_r = $query->first();
                if ($q_r) {
                    $new_sta_config = $q_r->sta_config;
                }
            }
        }

        if (!is_null($new_sta_config)) {
            $data = [
                'sta_info_id'   => $sta_info->id,
                'sta_config_id' => $new_sta_config->id,
            ];
            $entity = $this->StaConfigsStaInfos->newEntity($data);
            if ($this->StaConfigsStaInfos->save($entity)) {
                return true;
            }
            return false;
        }

        return false;
    }

    private function _getDevices($user, $device_token) {
        $query = $this->StaInfos->find();
        $query->where(['StaInfos.device_token' => $device_token]);
        $sta_info  = $query->first();
        if (is_null($sta_info)) {
            return null;
        }

        $permanent_user_id = $sta_info->permanent_user_id;
        $voucher_id = $sta_info->voucher_id;
        if (is_null($permanent_user_id) && is_null($voucher_id)) {
            return null;
        }

        // Check PermanentUser permission
        if (!is_null($permanent_user_id)) {
            $permanent_user = $this->PermanentUsers->get($permanent_user_id);
            if (is_null($permanent_user)) {
                return null;
            }

            if ($permanent_user->user_id !== $user['id'] &&
                $this->Users->is_sibling_of($user_id, $permanent_user->user_id) === false) {
                return null;
            }
        }

        // Check Voucher permission
        if (!is_null($voucher_id)) {
            $voucher = $this->Vouchers->get($voucher_id);
            if (is_null($voucher)) {
                return null;
            }

            if ($voucher->user_id !== $user['id'] &&
                $this->Users->is_sibling_of($user_id, $voucher->user_id) === false) {
                return null;
            }
        }

        $query = $this->StaInfos->find();
        if (!is_null($permanent_user_id)) {
            $query->where(['StaInfos.permanent_user_id' => $permanent_user_id]);
        }
        if (!is_null($voucher_id)) {
            $query->where(['StaInfos.voucher_id' => $voucher_id]);
        }
        $q_r = $query->all();

        $devices = [];
        foreach ($q_r as $entity) {
            $devices[] = [
                'device_type'      => $entity->device_type,
                'device_unique_id' => $entity->device_unique_id,
                'short_unique_id'  => $entity->short_unique_id,
            ];
        }

        return $devices;
    }

    private function _causedDuplicateUserError() {
         $error = [
             'errors' => [
                 'username' => __('The username you provided is already taken. Please provide another one'),
             ],
             'message' => __('Could not create item'),
         ];
         throw new ForbiddenException($error);
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
            'message' => $message,
            '_serialize' => ['errors','success','message']
        ]);
    }
}
