<?php

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

use Exception;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Exception\MethodNotAllowedException;

use Google\Client;
use Google\Service\Directory;
use Google\Service\Oauth2;

use Cake\Utility\Security;

class GoogleWsLinksController extends AppController {

    public $base       = "Access Providers/Controllers/GoogleWsLinks/";

    private $google_api_scopes = array(
        'https://www.googleapis.com/auth/admin.directory.user.readonly',
        'https://www.googleapis.com/auth/admin.directory.orgunit.readonly',
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
    );
    private $google_access_type = 'offline';
    private $google_prompt = 'select_account consent';
    private $google_application_name = 'LsRadiusDesk';

//------------------------------------------------------------------------

    public function initialize() {
        parent::initialize();
        $this->loadModel('Idps');
        $this->loadModel('IdpOauthClientCredentials');
        $this->loadModel('IdpOauthClientTokens');

        $this->loadModel('SubGroups');
        $this->loadModel('Profiles');
        $this->loadModel('Realms');
        $this->loadModel('Languages');
        $this->loadModel('Countries');
        $this->loadModel('PermanentUsers');
        $this->loadModel('SubGroupsPermanentUsers');
        $this->loadModel('Users');

        $this->loadComponent('Aa');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed', ['models' => ['Users']]);

        //$this->loadComponent('CommonQuery', [ //Very important to specify the Model
        //    'model' => 'Proxies'
        //]);

        $this->client = null;
    }

    //____ BASIC CRUD Actions Manager ________

    public function prepare() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $this->_checkParams(['idp_id', 'realm_id']);

            $params = [];
            $keys = ['idp_id', 'realm_id', 'state'];
            foreach ($keys as $key) {
                $params[$key] = $this->request->query($key);
                if (is_null($params[$key])) {
                    $params[$key] = $this->request->data($key);
                }
            }

            $state = "";
            if (!is_null($params['state']) && !empty($params['state'])) {
                $state = $params['state'];
            }

            $auth_uri = "";
            $complete = $this->_prepareGoogleClient($params['idp_id'], $user_id,
                                                    $auth_uri, $state);
            if ($complete) {
                $state = "";
            }

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'complete'      => $complete,
                'auth_uri'      => $auth_uri,
                'state'         => urlencode($state),
                '_serialize'    => ['success', 'complete', 'auth_uri', 'state']
            ]);
        } catch (Exception $e) {
            $this->_handleException($e);
        }
    }

    public function synchronize() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            $this->_checkParams(['idp_id', 'realm_id']);

            $params = [];
            $keys = ['idp_id', 'realm_id', 'dry_run', 'org_unit_id'];
            foreach ($keys as $key) {
                $params[$key] = $this->request->query($key);
                if (is_null($params[$key])) {
                    $params[$key] = $this->request->data($key);
                }
            }

            if (is_null($params['dry_run'])) {
                $dry_run = false;
            } else {
                $dry_run = ($params['dry_run'] == true ? true : false);
            }

            if (!$this->_prepareGoogleClient($params['idp_id'], $user_id)) {
                throw new UnauthorizedException();
            }

            $items = $this->_syncGoogleWorkspace($params['idp_id'], $user_id,
                                                 $params['realm_id'],
                                                 $params['org_unit_id'],
                                                 $dry_run);
            if ($dry_run) {
                $connection->rollback();
            } else {
                $connection->commit();
            }

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $items,
                'dry_run'       => $dry_run,
                '_serialize'    => ['success', 'dry_run', 'items']
            ]);
        } catch (Exception $e) {
            $connection->rollback();

            $this->_handleException($e);
        }
    }

/*
    public function listAuthConfigs() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $idp_id = $this->request->query('idp_id');
            if (is_null($idp_id)) {
                throw new BadRequestException();
            }

            $items = $this->_listGoogleAuthConfigs($idp_id, $user_id);
            $total = count($items);

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $items,
                'totalCount'    => $total,
                '_serialize'    => ['success', 'items', 'totalCount']
            ]);
        } catch (Exception $e) {
            // TODO: Need to implement exception handling.
            $this->set([
                'success'       => false,
                '_serialize'    => ['success']
            ]);
        }
    }

    public function updateAuthConfig() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $idp_id = $this->request->data('idp_id');
            $credential = $this->request->data('credential');
            if (is_null($idp_id) || is_null($credential)) {
                throw new BadRequestException();
            }

            $name = $this->request->data('name');
            $this->_updateGoogleAuthConfig($idp_id, $user_id, $credential, $name);

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                '_serialize'    => ['success']
            ]);
        } catch (Exception $e) {
            // TODO: Need to implement exception handling.
            $this->set([
                'success'       => false,
                '_serialize'    => ['success']
            ]);
        }
    }

    public function deleteAuthConfig() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            if (isset($this->request->data['id'])) { //Single item delete
                $entity = $this->IdpOauthClientCredentials->get($this->request->data['id']);
                $result = $this->IdpOauthClientCredentials->delete($entity);
            } else {                                 //Assume multiple item delete
                foreach ($this->request->data as $d) {
                    $entity = $this->IdpOauthClientCredentials->get($d['id']);
                    $result = $this->IdpOauthClientCredentials->delete($entity);
                }
            }

            $connection->commit();

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                '_serialize'    => ['success']
            ]);
        } catch (Exception $e) {
            $connection->rollback();

            // TODO: Need to implement exception handling.
            $this->set([
                'success'       => false,
                '_serialize'    => ['success']
            ]);
        }
    }

    public function listGoogleAccessTokens() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $idp_id = $this->request->query('idp_id');
            if (is_null($idp_id)) {
                throw new BadRequestException();
            }

            $items = $this->_listGoogleAccessTokens($idp_id);

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $items,
                '_serialize'    => ['success', 'items']
            ]);
        } catch (Exception $e) {
            // TODO: Need to implement exception handling.
            $this->set([
                'success'       => false,
                '_serialize'    => ['success']
            ]);
        }
    }

    public function deleteGoogleAccessToken() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        $connection = ConnectionManager::get('default');
        $connection->begin();

        try {
            if (isset($this->request->data['id'])) { //Single item delete
                $entity = $this->IdpOauthClientTokens->get($this->request->data['id']);
                $result = $this->IdpOauthClientTokens->delete($entity);
            } else {                                 //Assume multiple item delete
                foreach ($this->request->data as $d) {
                    $entity = $this->IdpOauthClientTokens->get($d['id']);
                    $result = $this->IdpOauthClientTokens->delete($entity);
                }
            }

            $connection->commit();

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                '_serialize'    => ['success']
            ]);
        } catch (Exception $e) {
            $connection->rollback();

            // TODO: Need to implement exception handling.
            $this->set([
                'success'       => false,
                '_serialize'    => ['success']
            ]);
        }

    }
*/

    public function callback() {
        try {
            // get state
            $encoded_state = $this->request->query['state'];
            $state = $this->_decodeState($encoded_state);

            $this->request->query['token'] = $state->token;
            $redirect_uri = $state->redirect_uri;
            $idp_id = $state->idp_id;
            $close = $state->close;

            // Check the URI of the redirect destination.
            if (preg_match('/^http/', $redirect_uri) ||
                !preg_match('/^\/cake3\/rd_cake/', $redirect_uri)) {
                $error_hash = [];
                $error_hash['errors']['state'] = __('invalid value is specified');
                $error_hash['message'] = __('Bad Request');
                throw new BadRequestException($error_hash);
            }

            //__ Authentication + Authorization __
            $user = $this->_ap_right_check();
            if (!$user) {
                return;
            }
            $user_id    = $user['id'];

            // Get authorization code.
            $code = $this->request->query['code'];

            // Exchange authorization code for an access token.
            $this->_updateGoogleClient($code, $idp_id, $user_id);

            if ($close) {
                $this->set([
                    'success' => true,
                    '_serialize' => ['success']
                ]);
            } else {
                // Delete "/cake3/rd_cake" as it is automatically added when redirecting.
                $redirect_uri = preg_replace('/^\/cake3\/rd_cake/', '', $redirect_uri);
                $this->redirect($redirect_uri);
                //var_dump($redirect_uri);
            }
        } catch (Exception $e) {
            $this->_handleException($e);
        }
    }

    public function listOrgunits() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $this->_checkParams(['idp_id']);

            $idp_id = $this->request->query('idp_id');
            $org_unit_id = $this->request->query('org_unit_id');
            $recursive = $this->request->query('recursive');
            if (is_null($recursive)) {
                $recursive = 0;
            }

            if (!$this->_prepareGoogleClient($idp_id, $user_id)) {
                throw new UnauthorizedException();
            }

            if ($recursive) {
                $items = $this->_listGoogleOrgunitsRecursive($org_unit_id);
            } else {
                $results = $this->_listGoogleOrgunits($org_unit_id);
                $items = $results->getOrganizationUnits();
            }
            $total = count($items);

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $items,
                'totalCount'    => $total,
                '_serialize'    => ['success', 'items', 'totalCount']
            ]);
        } catch (Exception $e) {
            $this->_handleException($e);
        }
    }

    public function getOrgunit() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $this->_checkParams(['idp_id', 'org_unit_id']);

            $idp_id = $this->request->query('idp_id');
            $org_unit_id = $this->request->query('org_unit_id');

            if (!$this->_prepareGoogleClient($idp_id, $user_id)) {
                throw new UnauthorizedException();
            }

            $items = $this->_getGoogleOrgunit($org_unit_id);

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $items,
                '_serialize'    => ['success', 'items']
            ]);
        } catch (Exception $e) {
            $this->_handleException($e);
        }
    }

    public function listOrgunitMembers() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $this->_checkParams(['idp_id', 'org_unit_id']);

            $idp_id = $this->request->query('idp_id');
            $org_unit_id = $this->request->query('org_unit_id');

            if (!$this->_prepareGoogleClient($idp_id, $user_id)) {
                throw new UnauthorizedException();
            }

            $page_token = $this->request->query('page_token');
            $results = $this->_listGoogleOrgunitMembers($org_unit_id, $page_token);
            $items = $results->getUsers();
            $total = count($items);
            $next_page_token = $results->getNextPageToken();

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $items,
                'totalCount'    => $total,
                'nextPageToken' => $next_page_token,
                '_serialize'    => ['success', 'items', 'totalCount', 'nextPageToken']
            ]);
        } catch (Exception $e) {
            $this->_handleException($e);
        }
    }

    public function listUsers() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $this->_checkParams(['idp_id']);

            $idp_id = $this->request->query('idp_id');

            if (!$this->_prepareGoogleClient($idp_id, $user_id)) {
                throw new UnauthorizedException();
            }

            $page_token = $this->request->query('page_token');
            $results = $this->_listGoogleUsers(null, $page_token);
            $items = $results->getUsers();
            $total = count($items);
            $next_page_token = $results->getNextPageToken();

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $items,
                'totalCount'    => $total,
                'nextPageToken' => $next_page_token,
                '_serialize'    => ['success', 'items', 'totalCount', 'nextPageToken']
            ]);
        } catch (Exception $e) {
            $this->_handleException($e);
        }
    }

    public function getUser() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $this->_checkParams(['idp_id', 'user_key']);

            $idp_id = $this->request->query('idp_id');
            $user_key = $this->request->query('user_key');

            if (!$this->_prepareGoogleClient($idp_id, $user_id)) {
                throw new UnauthorizedException();
            }

            $items = $this->_getGoogleUser($user_key);

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $items,
                '_serialize'    => ['success', 'items']
            ]);
        } catch (Exception $e) {
            $this->_handleException($e);
        }
    }

    public function getUserinfoMe() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if (!$user) {
            return;
        }
        $user_id    = $user['id'];

        try {
            $this->_checkParams(['idp_id']);

            $idp_id = $this->request->query('idp_id');

            if (!$this->_prepareGoogleClient($idp_id, $user_id)) {
                throw new UnauthorizedException();
            }

            $results = $this->_getGoogleUserinfoMe();

            //___ FINAL PART ___
            $this->set([
                'success'       => true,
                'items'         => $results,
                '_serialize'    => ['success', 'items']
            ]);
        } catch (Exception $e) {
            $this->_handleException($e);
        }
    }

    private function _setGoogleClientParams($client) {
        $client->setApplicationName($this->google_application_name);
        $client->setScopes($this->google_api_scopes);
        $client->setAccessType($this->google_access_type);
        $client->setPrompt($this->google_prompt);
    }

    private function _getGoogleAuthConfig($idp_id, $user_id,
                                          $idp_oauth_client_credential_id = null) {
        $user = $this->Users->get($user_id);
        if (isset($idp_oauth_client_credential_id)) {
            $entity = $this->IdpOauthClientCredentials->get(
                          $idp_oauth_client_credential_id);
            if ($this->LifeSeed->checkEntityPermByUser($entity, $user, 'ro',
                                                       ['child', 'parent'])) {
                return $entity;
            }
        } else {
            // NOTE: There is a challenge in prioritizing when there are
            //       multiple candidates for available credentials.
            $base_query = $this->IdpOauthClientCredentials->find();

            // Find the record created by the user.
            $query = $base_query;
            $entity = $query->where(['idp_id' => $idp_id, 'user_id' => $user_id])->first();
            if (!is_null($entity)) {
                return $entity;
            }

            // Find entities based on groups.
            $query = $base_query;
            $this->LifeSeed->filterQueryByUserAndGroup($query, $user, ['parent']);
            $entity = $query->first();
            if (!is_null($entity)) {
                return $entity;
            }
        }

        return null;
    }

    private function _loadGoogleAuthConfig($client, $idp_id, $user_id) {
        $entity = $this->_getGoogleAuthConfig($idp_id, $user_id);
        if (!is_null($entity) && isset($entity) && isset($entity->credential)) {
            $credential = json_decode($entity->credential, true);
            if (!isset($credential)) {
                // Decrypt when it is encrypted and cannot be read as JSON.
                $credential_json = $this->IdpOauthClientCredentials->decrypt(
                                       $entity->credential);
                $credential = json_decode($credential_json, true);
            }

            if (isset($credential['web']) &&
                !isset($credential['web']['client_secret']) &&
                isset($entity->client_secret)) {
                // If the credential does not contain client_secret,
                // embed the value obtained from the client_secret column.
                $client_secret_json = $this->IdpOauthClientCredentials->decrypt(
                                          $entity->client_secret);
                $client_secret = json_decode($client_secret_json, true);
                if (isset($client_secret['value'])) {
                    $credential['web']['client_secret'] = $client_secret['value'];
                }
            }

            $client->setAuthConfig($credential);
        } else {
            throw new UnauthorizedException(__('no credential is available'));
        }
    }

    private function _removeDataColumns($q_r, $model, $remove_columns) {
        $items = array();
        foreach ($q_r as $i) {
            $row    = array();
            $fields = $model->schema()->columns();
            foreach ($fields as $field) {
                if (in_array($field, $remove_columns)) {
                    continue;
                }
                $row["$field"]= $i->{"$field"};
            }
            array_push($items, $row);
        }
        return $items;
    }

    private function _findSubGroups($idp_id, $user_id, $realm_id,
                                    $path_or_id, $recursive = false) {
        if (is_null($path_or_id)) {
            return null;
        }

        $query = $this->SubGroups->find();
        $query->where(['user_id' => $user_id]);
        $query->where(['realm_id' => $realm_id]);
        $query->where(['idp_id' => $idp_id]);
        if (!$recursive) {
            if (substr($path_or_id, 0, 1) == '/') {
                $query->where(['path' => $path_or_id]);
            } else {
                $query->where(['unique_id' => $path_or_id]);
            }
            return $query->all();
        } else {
            if (substr($path_or_id, 0, 1) == '/') {
                $query->where(['OR' => [
                                  ['path' => $path_or_id],
                                  ['path LIKE' => $path_or_id.'/%'],
                              ]]);
                return $query->all();
            } else {
                $query->where(['unique_id' => $path_or_id]);
                $sub_group = $query->first();
                if (is_null($sub_group)) {
                    return null;
                }
                return $this->_findSubGroups($idp_id, $user_id, $realm_id,
                                             $sub_group->path, $recursive);
            }
        }
    }

    private function _findSubGroupByPath($idp_id, $user_id, $realm_id, $path) {
        return $this->_findSubGroups($idp_id, $user_id, $realm_id, $path)->first();
    }

    private function _createOrUpdateSubGroup($idp_id, $user_id, $realm_id,
                                             $unique_id, $name, $path,
                                             &$is_created = null,
                                             &$is_updated = null) {

        $data = [
            'name'      => $name,
            'user_id'   => $user_id,
            'realm_id'  => $realm_id,
            'idp_id'    => $idp_id,
            'unique_id' => $unique_id,
            'path'      => $path,
        ];

        if (!is_null($unique_id)) {
            $entity = $this->_findSubGroups(
                             $idp_id, $user_id, $realm_id, $unique_id)->first();
            if (!is_null($entity)) {
                $this->SubGroups->patchEntity($entity, $data);
                if ($entity->isDirty()) {
                    $is_updated = true;
                }
                return $this->SubGroups->save($entity);
            }
        }

        $is_created = true;
        $entity = $this->SubGroups->newEntity($data);
        return $this->SubGroups->save($entity);
    }

    private function _findOrCreateSubGroupByPath($idp_id, $user_id, $realm_id,
                                                 $path, &$is_created = null,
                                                 &$is_updated = null) {
        $sub_group = $this->_findSubGroupByPath(
                         $idp_id, $user_id, $realm_id, $path);
        if (is_null($sub_group)) {
            $orgunit = $this->_getGoogleOrgunit($path);
            $unique_id = $orgunit->orgUnitId;
            if (is_null($orgunit->name)) {
                $name = '(root)';
            } else {
                // The "path" based value is now treated as a "name" for search purposes.
                #$name = $orgunit->name;
                $name = str_replace('/', ' ', $path);
                $name = trim($name);
            }
            $sub_group = $this->_createOrUpdateSubGroup($idp_id, $user_id, $realm_id,
                                                        $unique_id, $name, $path,
                                                        $is_created, $is_updated);
        }
        return $sub_group;
    }

    private function _importGoogleOrgunitUsers($idp_id, $user_id, $realm_id,
                                               $google_users,
                                               &$exist_permanent_user_ids,
                                               &$exist_sub_group_ids,
                                               &$created_permanent_users = null,
                                               &$updated_permanent_users = null,
                                               &$error_permanent_users = null,
                                               &$created_sub_groups = null,
                                               &$updated_sub_groups = null) {

        $realm = $this->Realms->get($realm_id);

        foreach ($google_users as $google_user) {
            $org_unit_path = $google_user->orgUnitPath;

            $sub_group_is_created = false;
            $sub_group_is_updated = false;
            $sub_group = $this->_findOrCreateSubGroupByPath(
                             $idp_id, $user_id, $realm_id, $org_unit_path,
                             $sub_group_is_created, $sub_group_is_updated);
            if ($sub_group_is_created) {
                $created_sub_groups[] = $sub_group->toArray();
            }
            if ($sub_group_is_updated) {
                $updated_sub_groups[] = $sub_group->toArray();
            }

            $permanent_user_is_created = false;
            $permanent_user_is_updated = false;
            $permanent_user = $this->_importGoogleUser(
                                         $user_id, $realm,
                                         $google_user, $sub_group,
                                         $permanent_user_is_created,
                                         $permanent_user_is_updated,
                                         $error_permanent_users);
            if ($permanent_user !== false) {
                if (!in_array($sub_group->id, $exist_sub_group_ids, true)) {
                    $exist_sub_group_ids[] = $sub_group->id;
                }
                $exist_permanent_user_ids[] = $permanent_user->id;

                if ($permanent_user_is_created) {
                    $created_permanent_users[] = $permanent_user->toArray();
                }
                if ($permanent_user_is_updated) {
                    $updated_permanent_users[] = $permanent_user->toArray();
                }
            }
        }
    }

    private function _importGoogleUser($user_id, $realm, $google_user,
                                       $sub_group = null,
                                       &$is_created = null,
                                       &$is_updated = null,
                                       &$errors = null) {
        $username = $google_user->primaryEmail;
        $username = preg_replace('/@.*$/', '', $username);
        if (($realm->suffix != '') && ($realm->suffix_permanent_users)) {
             $username = $username.'@'.$realm->suffix;
        }

        $country_id = null;
        $language_id = null;
        $address = '';
        $phone = '';
        
        $name = null;
        $surname = null;
        if (isset($google_user->name)) {
            if (isset($google_user->name['familyName'])) {
                $surname = $google_user->name['familyName'];
            }
            if (isset($google_user->name['givenName'])) {
                $name = $google_user->name['givenName'];
            }
        }

        $sub_group_id = null;
        $profile = '';
        $profile_id = null;
        if (isset($sub_group)) {
            $sub_group_id = $sub_group->id;
            $profile = $sub_group->profile;
            $profile_id = $sub_group->profile_id;

            if (is_null($profile)) {
                $profile = '';
            }
        }

        $query = $this->PermanentUsers->find();
        $query->where(['unique_id' => $google_user->id]);
        $permanent_user = $query->first();

        if (isset($permanent_user)) {
            $data = [
                'username'     => $username,
                'email'        => $google_user->primaryEmail,
                'name'         => $name,
                'surname'      => $surname,
                'realm'        => $realm->name,
                'realm_id'     => $realm->id,
                'user_id'      => $user_id,
                'sub_group_id' => $sub_group_id,
                'profile'      => $profile,
                'profile_id'   => $profile_id,
            ];

            $entity = $permanent_user;
            $this->PermanentUsers->patchEntity($entity, $data);
            if ($entity->isDirty()) {
                $permanent_user = $this->PermanentUsers->save($entity);
                if ($permanent_user === false) {
                    // Duplicate user names cause errors.
                    if (!is_null($errors)) {
                        $errors[$username] = $entity->errors();
                    }
                } else {
                    if (!is_null($is_updated)) {
                        $is_updated = true;
                    }
                }
            }
        } else {
            $data = [
                'username'     => $username,
                'email'        => $google_user->primaryEmail,
                'auth_type'    => 'sql',
                'active'       => true,
                'name'         => $name,
                'surname'      => $surname,
                'realm'        => $realm->name,
                'realm_id'     => $realm->id,
                'token'        => '',
                'user_id'      => $user_id,
                'unique_id'    => $google_user->id,
                'sub_group_id' => $sub_group_id,
                'profile'      => $profile,
                'profile_id'   => $profile_id,
                'unique_id_type' => 'google',
            ];

            $entity = $this->PermanentUsers->newEntity($data);
            $permanent_user = $this->PermanentUsers->save($entity);
            if ($permanent_user === false) {
                // Duplicate user names cause errors.
                if (!is_null($errors)) {
                    $errors[$username] = $entity->errors();
                }
            } else {
                if (!is_null($is_created)) {
                    $is_created = true;
                }
            }
        }

        return $permanent_user;
    }

    private function _checkPermanentUserInOtherGroup($sub_group_id, $permanent_user_id) {
        $query = $this->SubGroupsPermanentUsers->find();
        $query->where(['sub_group_id !=' => $sub_group_id]);
        $query->where(['permanent_user_id' => $permanent_user_id]);
        $q_r = $query->first();

        if (isset($q_r)) {
            return true;
        } else {
            return false;
        }
    }

    private function _syncGoogleWorkspace($idp_id, $user_id, $realm_id,
                                          $org_unit_id = null) {
        $created_permanent_users = [];
        $updated_permanent_users = [];
        $deleted_permanent_users = [];
        $error_permanent_users = [];
        $created_sub_groups = [];
        $updated_sub_groups = [];
        $deleted_sub_groups = [];

        if (isset($org_unit_id)) {
            try {
                $google_orgunit = $this->_getGoogleOrgunit($org_unit_id);
            } catch (Exception $e) {
                if ($e->getCode() != 404) {
                    // Errors due to the absence of a group are ignored.
                    throw $e;
                }
            }
            #var_dump($google_orgunit);

            $exist_sub_group_ids = [];
            if (isset($google_orgunit)) {
                $base_org_unit_id = $google_orgunit->orgUnitId;


                // Retrieve users under the Orgunits corresponding to the
                // specified path from Google Workspace, and Create and store
                // subgroups corresponding to Orgunits.
                $exist_permanent_user_ids = [];
                $google_users = $this->_listGoogleOrgunitMembersAll($base_org_unit_id);
                $this->_importGoogleOrgunitUsers($idp_id, $user_id, $realm_id,
                                                 $google_users,
                                                 $exist_permanent_user_ids,
                                                 $exist_sub_group_ids,
                                                 $created_permanent_users,
                                                 $updated_permanent_users,
                                                 $error_permanent_users,
                                                 $created_sub_groups,
                                                 $updated_sub_groups);

                // Move users who no longer exist in the orgunits
                // being processed to "/".
                $this->_removeUnnecessaryPermanentUser($idp_id, $user_id, $realm_id,
                                                       $exist_permanent_user_ids,
                                                       $exist_sub_group_ids, false,
                                                       $updated_permanent_users,
                                                       $deleted_permanent_users);
            }

            // Delete orgunits that no longer exist.
            // The user who belonged to the orgunits will be moved to "/".
            $this->_removeUnnecessarySubGroups($idp_id, $user_id, $realm_id,
                                               $org_unit_id,
                                               $exist_sub_group_ids, false,
                                               $updated_permanent_users,
                                               $deleted_permanent_users,
                                               $deleted_sub_groups);
        } else {
            $exist_permanent_user_ids = [];
            $exist_sub_group_ids = [];

            // For users retrieved from Google workspace, create and store
            // subgroups corresponding to orgunits.
            $google_users = $this->_listGoogleUsersAll();
            $this->_importGoogleOrgunitUsers($idp_id, $user_id, $realm_id,
                                             $google_users,
                                             $exist_permanent_user_ids,
                                             $exist_sub_group_ids,
                                             $created_permanent_users,
                                             $updated_permanent_users,
                                             $error_permanent_users,
                                             $created_sub_groups,
                                             $updated_sub_groups);

            $classified_sub_group_ids = $this->_getClassifiedSubGroupIds(
                                            $idp_id, $user_id, $realm_id,
                                            $exist_sub_group_ids);

            // Delete non-existent users belonging to orgunits on the DB.
            $this->_removeUnnecessaryPermanentUser($idp_id, $user_id, $realm_id,
                                                   $exist_permanent_user_ids,
                                                   $classified_sub_group_ids['all'],
                                                   true,
                                                   $updated_permanent_users,
                                                   $deleted_permanent_users);

            // Delete non-existent orgunits.
            $this->_deleteSubGroups($classified_sub_group_ids['unnecessary'],
                                    $deleted_sub_groups);
        }

        $result = [
            'sub_groups' => [
                'create' => $created_sub_groups,
                'update' => $updated_sub_groups,
                'delete' => $deleted_sub_groups,
            ],
            'permanent_users' => [
                'create' => $created_permanent_users,
                'update' => $updated_permanent_users,
                'delete' => $deleted_permanent_users,
                'error'  => $error_permanent_users,
            ],
        ];

        return $result;
    }

    private function _removeUnnecessaryPermanentUser($idp_id, $user_id, $realm_id,
                                                     $exist_permanent_user_ids,
                                                     $target_sub_group_ids,
                                                     $delete_permanent_user = false,
                                                     &$updated_permanent_users = null,
                                                     &$deleted_permanent_users = null) {

        if (count($target_sub_group_ids) > 0) {
            $root_sub_group = $this->_findOrCreateSubGroupByPath(
                                  $idp_id, $user_id, $realm_id, '/');

            $query = $this->PermanentUsers->find();
            $query->where(['user_id' => $user_id]);
            $query->where(['realm_id' => $realm_id]);
            $query->where(['sub_group_id IN' => $target_sub_group_ids]);
            if (count($exist_permanent_user_ids) > 0) {
                $query->where(['id NOT IN' => $exist_permanent_user_ids]);
            }
            $permanent_users = $query->all();

            foreach ($permanent_users as $permanent_user) {
                if ($delete_permanent_user) {
                    if (!is_null($deleted_permanent_users)) {
                        $deleted_permanent_users[] = $permanent_user->toArray();
                    }
                    $this->PermanentUsers->delete($permanent_user);
                } else {
                    $data = [
                        'sub_group_id'  => $root_sub_group->id,
                    ];
                    $entity = $permanent_user;
                    $this->PermanentUsers->patchEntity($entity, $data);
                    $permanent_user = $this->PermanentUsers->save($entity);
                    if (!is_null($updated_permanent_users)) {
                        $updated_permanent_users[] = $permanent_user->toArray();
                    }
                }
            }
        }
    }

    private function _removeUnnecessarySubGroups($idp_id, $user_id, $realm_id,
                                                 $org_unit_id,
                                                 $exist_sub_group_ids = [],
                                                 $delete_permanent_user = false,
                                                 &$updated_permanent_users = null,
                                                 &$deleted_permanent_users = null,
                                                 &$deleted_sub_groups = null) {
        $unnecessary_sub_group_ids = [];
        $sub_groups = $this->_findSubGroups($idp_id, $user_id, $realm_id,
                                            $org_unit_id, true);
        foreach ($sub_groups as $sub_group) {
            if (!in_array($sub_group->id, $exist_sub_group_ids, true)) {
                $unnecessary_sub_group_ids[] = $sub_group->id;
            }
        }

        if (count($unnecessary_sub_group_ids) > 0) {
            $root_sub_group = $this->_findOrCreateSubGroupByPath(
                                  $idp_id, $user_id, $realm_id, '/');

            $query = $this->PermanentUsers->find();
            $query->where(['user_id' => $user_id]);
            $query->where(['realm_id' => $realm_id]);
            $query->where(['sub_group_id IN' => $unnecessary_sub_group_ids]);
            $permanent_users = $query->all();

            foreach ($permanent_users as $permanent_user) {
                if ($delete_permanent_user) {
                    if (!is_null($deleted_permanent_users)) {
                        $deleted_permanent_users[] = $permanent_user->toArray();
                    }
                    $this->PermanentUsers->delete($permanent_user);
                } else {
                    $data = [
                        'sub_group_id'  => $root_sub_group->id,
                    ];
                    $entity = $permanent_user;
                    $this->PermanentUsers->patchEntity($entity, $data);
                    $permanent_user = $this->PermanentUsers->save($entity);
                    if (!is_null($updated_permanent_users)) {
                        $updated_permanent_users[] = $permanent_user->toArray();
                    }
                }
            }
        }

        $this->_deleteSubGroups($unnecessary_sub_group_ids, $deleted_sub_groups);
    }

    private function _getClassifiedSubGroupIds($idp_id, $user_id, $realm_id,
                                               $exist_sub_group_ids) {
        $sub_group_ids = [];
        $unnecessary_sub_group_ids = [];
        $query = $this->SubGroups->find();
        $query->where(['user_id' => $user_id]);
        $query->where(['realm_id' => $realm_id]);
        $query->where(['idp_id' => $idp_id]);
        $sub_groups = $query->all();
        foreach ($sub_groups as $sub_group) {
            $sub_group_ids[] = $sub_group->id;
            if (!in_array($sub_group->id, $exist_sub_group_ids, true)) {
                $unnecessary_sub_group_ids[] = $sub_group->id;
            }
        }

        return [
            'all'         => $sub_group_ids,
            'exist'       => $exist_sub_group_ids,
            'unnecessary' => $unnecessary_sub_group_ids,
        ];
    }

    private function _deleteSubGroups($sub_group_ids, &$deleted_sub_groups = null) {
        foreach ($sub_group_ids as $sub_group_id) {
            $sub_group = $this->SubGroups->get($sub_group_id);
            if ($sub_group->path == '/') {
                continue;
            }
            if (!is_null($deleted_sub_groups)) {
                $deleted_sub_groups[] = $sub_group->toArray();
            }
            $this->SubGroups->delete($sub_group);
        }
    }

    private function _listGoogleAuthConfigs($idp_id, $user_id) {
        $model = $this->IdpOauthClientCredentials;
        $query = $model->find()->where(['idp_id' => $idp_id])
                               ->where(['user_id' => $user_id]);
        $q_r = $query->all();
        return $this->_removeDataColumns($q_r, $model,
                   ['credential', 'client_secret', 'user_id']);
    }

    private function _updateGoogleAuthConfig($idp_id, $user_id, $credential, $name) {
        if (is_null($idp_id)) {
            $entity = null;
        } else {
            $query = $this->IdpOauthClientCredentials->find();
            $query->where(['idp_id' => $idp_id]);
            $query->where(['user_id' => $user_id]);
            $entity = $query->first();
        }

        if (isset($entity)) {
            $new_entity = ['credential' => $credential];
            if (!is_null($name)) {
                $new_entity['name'] = $name;
            }

            $this->IdpOauthClientCredentials->patchEntity($entity, $new_entity);
            $this->IdpOauthClientCredentials->save($entity);
        } else {
            if (is_null($name)) {
                $name = '';
            }
            $data = [
                'idp_id'        => $idp_id,
                'user_id'       => $user_id,
                'credential'    => $credential,
                'name'          => $name,
            ];

            $entity = $this->IdpOauthClientCredentials->newEntity($data);
            $this->IdpOauthClientCredentials->save($entity);
        }
    }

    private function _loadGoogleAccessToken($client, $idp_id, $user_id) {
        $query = $this->IdpOauthClientTokens->find();
        $query->contain('IdpOauthClientCredentials');
        $query->where(['IdpOauthClientTokens.user_id' => $user_id]);
        $query->where(['idp_id' => $idp_id]);

        $q_r = $query->first();
        if (isset($q_r) && isset($q_r->token)) {
            $access_token = json_decode($q_r->token, true);
            if (!isset($access_token)) {
                // Decrypt when it is encrypted and cannot be read as JSON.
                $token_json = $this->IdpOauthClientTokens->decrypt($q_r->token);
                $access_token = json_decode($token_json, true);
            }

            $client->setAccessToken($access_token);
        }
    }

    private function _listGoogleAccessTokens($idp_id) {
        $model = $this->IdpOauthClientTokens;
        $query = $model->find();
        $query->contain('IdpOauthClientCredentials');
        #$query->where(['user_id' => $user_id]);
        $query->where(['idp_id' => $idp_id]);
        $q_r = $query->all();
        return $this->_removeDataColumns($q_r, $model,
                   ['token', 'idp_oauth_client_credential']);
    }

    private function _updateGoogleAccessToken($client, $idp_id, $user_id) {
        $new_token = json_encode($client->getAccessToken());

        $query = $this->IdpOauthClientTokens->find();
        $query->contain('IdpOauthClientCredentials');
        $query->where(['IdpOauthClientTokens.user_id' => $user_id]);
        $query->where(['idp_id' => $idp_id]);

        $entity = $query->first();
        if (isset($entity)) {
            $this->IdpOauthClientTokens->patchEntity($entity, ['token' => $new_token]);
            $this->IdpOauthClientTokens->save($entity);
        } else {
            $q_r = $this->IdpOauthClientCredentials->find()
                        ->where(['idp_id' => $idp_id])->first();
            if (isset($q_r)) {
                $data = [
                    'user_id'                        => $user_id,
                    'idp_oauth_client_credential_id' => $q_r->id,
                    'token'                          => $new_token,
                ];
                $entity = $this->IdpOauthClientTokens->newEntity($data);
                $this->IdpOauthClientTokens->save($entity);
            } else {
                // Credential is not found
            }
        }
    }

    private function _generateIv() {
        return substr(md5(uniqid()), 0, 16);
    }

    private function _encodeState($state) {
        $key = Configure::read('AES.key');
        $iv = $this->_generateIv();
        $state_json = json_encode($state);
        $state_encrypted = openssl_encrypt($state_json, 'AES-256-CBC',
                                           $key, 0, $iv);
        $state_base64 = base64_encode($state_encrypted);
        return sprintf("%s:%s", $iv, $state_base64);
    }

    private function _decodeState($encoded_state) {
        $key = Configure::read('AES.key');
        $iv = substr($encoded_state, 0, 16);
        $state_base64 = substr($encoded_state, 17);
        $state_encrypted = base64_decode($state_base64);
        $state_json = openssl_decrypt($state_encrypted, 'AES-256-CBC',
                                           $key, 0, $iv);
        $state = json_decode($state_json);
        return $state;
    }

    private function _prepareGoogleClientToken($client, $idp_id, $user_id,
                                               &$auth_uri = null, &$state = null) {
        try {
            $this->_loadGoogleAccessToken($client, $idp_id, $user_id);

            // If there is no previous token or it's expired.
            if ($client->isAccessTokenExpired()) {
                // Refresh the token if possible, else fetch a new one.
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    $this->_updateGoogleAccessToken($client, $idp_id, $user_id);
                } else {
                    // Encrypt the token and current URL for RADIUSdesk authentication
                    // and embed them in the OAuth state.
                    $token = $this->request->query('token');
                    if (is_null($token)) {
                        $token = $this->request->data('token');
                    }

                    if ($this->request->is('get')) {
                        $redirect_uri = $this->request->here();
                    } else {
                        $redirect_uri = $this->request->here();
                        $data = $this->request->data();
                        $http_query = http_build_query($data);
                        $redirect_uri .= '?' . $http_query;
                    }

                    if (is_null($state) || empty($state)) {
                        $tmp_state = array(
                            'token'        => $token,
                            'redirect_uri' => $redirect_uri,
                            'idp_id'       => $idp_id,
                            'close'        => !is_null($auth_uri),
                        );
                        $encoded_state = $this->_encodeState($tmp_state);
                        if (!is_null($state)) {
                            $state = $encoded_state;
                        }
                    } else {
                        $encoded_state = $state;
                    }
                    $client->setState($encoded_state);

                    // Request authorization from the user.
                    $google_auth_url = $client->createAuthUrl();
                    //echo $google_auth_url."\n";

                    if (is_null($auth_uri)) {
                        $this->redirect($google_auth_url);
                    } else {
                        $auth_uri = $google_auth_url;
                    }

                    return false;
                }
            }
        } catch (Exception $e) {
            //$this->_deleteGoogleAccessToken($client);
            throw $e;
        }

        return true;
    }

    private function _updateGoogleClientToken($client, $code, $idp_id, $user_id) {
        $access_token = $client->fetchAccessTokenWithAuthCode($code);
        // Check to see if there was an error.
        if (array_key_exists('error', $access_token)) {
            throw new Exception(join(', ', $access_token));
        }

        $client->setAccessToken($access_token);
        $this->_updateGoogleAccessToken($client, $idp_id, $user_id);
    }

    private function _prepareGoogleClient($idp_id, $user_id,
                                          &$auth_uri = null, &$state = null) {
        $client = new Client();
        $this->_setGoogleClientParams($client);
        $this->_loadGoogleAuthConfig($client, $idp_id, $user_id);

        if ($this->_prepareGoogleClientToken($client, $idp_id, $user_id,
                                             $auth_uri, $state)) {
            $this->client = $client;
            return true;
        } else {
            $this->client = null;
            return false;
        }
    }

    private function _updateGoogleClient($code, $idp_id, $user_id) {
        $client = new Client();
        $this->_setGoogleClientParams($client);
        $this->_loadGoogleAuthConfig($client, $idp_id, $user_id);

        if ($this->_updateGoogleClientToken($client, $code, $idp_id, $user_id)) {
            $this->client = $client;
            return true;
        } else {
            $this->client = null;
            return false;
        }
    }

    private function _listGoogleOrgunits($org_unit_path = null) {
        $client = $this->client;
        $service = new Directory($client);
        $customer_id = 'my_customer';

        if (isset($org_unit_path)) {
            $opt_params = array(
                'orgUnitPath' => $org_unit_path,
            );
        } else {
            $opt_params = array(
            );
        }

        return $service->orgunits->listOrgunits($customer_id, $opt_params);
    }

    private function _listGoogleOrgunitsRecursive($base_org_unit_path = null) {
        $base_results = $this->_listGoogleOrgunits($base_org_unit_path);
        $base_orgunits = $base_results->getOrganizationUnits();
        $items = array();

        $keys = [
            'name', 'orgUnitId', 'orgUnitPath', 'parentOrgUnitId', 'parentOrgUnitPath',
        ];

        for ($i = 0; $i < count($base_orgunits); $i++) {
            $base_orgunit = $base_orgunits[$i];
            $base_orgunit_id = $base_orgunit->getOrgUnitId();

            $item = [];
            foreach ($keys as $key) {
                $item[$key] = $base_orgunit[$key];
            }
            $item['children'] =  $this->_listGoogleOrgunitsRecursive($base_orgunit_id);
            $items[] = $item;
        }

        return $items;
    }

    private function _getGoogleOrgunit($org_unit_id) {
        $client = $this->client;
        $service = new Directory($client);
        $customer_id = 'my_customer';
        if (strlen($org_unit_id) == 0) {
            // do nothing
        } else if (substr($org_unit_id, 0, 1) == '/') {
            $org_unit_id = substr($org_unit_id, 1);
        } else {
            // do nothing
        }

        return $service->orgunits->get($customer_id, $org_unit_id);
    }

    private function _getGoogleOrgunitPath($org_unit_id) {
        $org_unit_path = null;
        if (strlen($org_unit_id) == 0) {
            return null;
        } else if (substr($org_unit_id, 0, 1) == '/') {
            $org_unit_path = $org_unit_id;
        } else {
            $orgunit = $this->_getGoogleOrgunit($org_unit_id);
            $org_unit_path = $orgunit->orgUnitPath;
        }
        return $org_unit_path;
    }

    private function _listGoogleOrgunitMembers($org_unit_id, $page_token) {
        $org_unit_path = $this->_getGoogleOrgunitPath($org_unit_id);
        $query = sprintf("orgUnitPath='%s'", $org_unit_path);
        return $this->_listGoogleUsers($query, $page_token);
    }

    private function _listGoogleOrgunitMembersAll($org_unit_id) {
        $org_unit_path = $this->_getGoogleOrgunitPath($org_unit_id);
        $query = sprintf("orgUnitPath='%s'", $org_unit_path);
        return $this->_listGoogleUsersAll($query);
    }

    private function _listGoogleUsers($query = null, $page_token = null) {
        $client = $this->client;
        $service = new Directory($client);

        $opt_params = array(
            'customer' => 'my_customer',
        );

        if (isset($page_token)) {
            $opt_params['pageToken'] = $page_token;
        } else {
            $opt_params['orderBy']   = 'email';

            if (isset($query)) {
                $opt_params['query'] = $query;
            }
        }

        return $service->users->listUsers($opt_params);
    }

    private function _listGoogleUsersAll($query = null) {
        $items = [];
        $page_token = null;

        do {
            $results = $this->_listGoogleUsers($query, $page_token);
            $google_users = $results->getUsers();
            if (isset($google_users)) {
                $items = array_merge($items, $google_users);
            }
            $page_token = $results->getNextPageToken();
        } while (isset($page_token));

        return $items;
    }

    private function _getGoogleUser($user_key) {
        $client = $this->client;
        $service = new Directory($client);

        return $service->users->get($user_key);
    }

    private function _getGoogleUserinfoMe() {
        $client = $this->client;
        $service = new Oauth2($client);

        #return $service->userinfo->get();
        return $service->userinfo_v2_me->get();
    }

    private function _checkParams($keys) {
        $raise_error = false;
        $error_hash = ['message' => __('Bad Request')];
        foreach ($keys as $key) {
            if (is_null($this->request->query($key)) &&
                is_null($this->request->data($key))) {
                $error_hash['errors'][$key] = __('This field is required');
                $raise_error = true;
            }
        }

        if ($raise_error) {
            throw new BadRequestException($error_hash);
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
}
