<?php

namespace App\Controller\Component;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

use Exception;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Exception\InternalErrorException;

class LifeSeedComponent extends Component {
    const UUID_V4_FORMAT = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';

    public function initialize(array $config) {
        $this->controller = $this->_registry->getController();
        $this->JsonErrors = $this->controller->JsonErrors;
        $this->TimeCalculations = $this->controller->TimeCalculations;

        if (isset($this->controller->RealmAcl)) {
            $this->RealmAcl = $this->controller->RealmAcl;
        }

        if (isset($this->controller->main_model)) {
            $this->main_model = $this->controller->main_model;
            $this->{$this->main_model} = $this->controller->{$this->main_model};
        }

        if (isset($config['models'])) {
            $models = $config['models'];
            foreach ($models as $model) {
                $this->{$model} = $this->controller->{$model};
            }
        }
    }

    //____ BASIC CRUD Actions Manager ________

    public function index($user, $query,
                          $append_fields = [], $exclude_fields = [],
                          $callback = null, $post_callback = null) {
        if (!$user || !isset($this->main_model)) {
            return false;
        }

        // Limit the records displayed by the user executing the query.
        // If there is no user_id column or no user model passed, do not add a query.
        $this->filterQueryByUserAndGroup($query, $user);

        // Sort
        if (isset($this->request->query['sort'])) {
            $sort_key = $this->request->query['sort'];
            if (isset($this->request->query['dir'])) {
                $sort_dir = $this->request->query['dir'];
            } else {
                $sort_dir = 'ASC';
            }

            // Modify sort_key based on the "contain" status of the query.
            $sort_key = $this->modify_sort_key($query, $sort_key);

            $query->order([$sort_key => $sort_dir]);
        }

        //===== PAGING (MUST BE LAST) ======
        $limit = 50;   //Defaults
        $page = 1;
        $offset = 0;
        if (isset($this->request->query['limit'])) {
            $limit = $this->request->query['limit'];
            $page = $this->request->query['page'];
            $offset = $this->request->query['start'];
        }

        $query->page($page);
        $query->limit($limit);
        $query->offset($offset);

        $q_r = $query->all();
        $items = array();

        foreach ($q_r as $i) {
            $row        = array();
            $fields     = $this->{$this->main_model}->schema()->columns();
            foreach ($fields as $field) {
                $row["$field"]= $i->{"$field"};
                
                if($field == 'created'){
                    $row['created_in_words'] =
                        $this->TimeCalculations->time_elapsed_string($i->{"$field"});
                }
                if($field == 'modified'){
                    $row['modified_in_words'] =
                        $this->TimeCalculations->time_elapsed_string($i->{"$field"});
                } 
            }
            foreach ($append_fields as $table_name => $table_fields) {
                foreach ($table_fields as $field) {
                    $row_name = sprintf("%s_%s", $table_name, $field);
                    if (isset($i->{"$table_name"}->{"$field"})) {
                        $row["$row_name"] = $i->{"$table_name"}->{"$field"};
                    } else {
                        $row["$row_name"] = null;
                    }
                }
            }
            foreach ($exclude_fields as $table_name => $table_fields) {
                foreach ($table_fields as $field) {
                    if (strlen($table_name) > 0) {
                        $row_name = sprintf("%s_%s", $table_name, $field);
                    } else {
                        $row_name = $field;
                    }
                    unset($row["$row_name"]);
                }
            }
            if (!is_null($callback)) {
                $callback($user, $i, $row);
            }
            if (is_null($row)) {
                continue;
            }

            array_push($items, $row);
        }

        if (!is_null($post_callback)) {
            $post_callback($user, $items);
        }

        $total = count($items);

        //___ FINAL PART ___
        $this->controller->set([
            'items' => $items,
            'success' => true,
            'totalCount' => $total,
            '_serialize' => ['items', 'success', 'totalCount']
        ]);

        return true;
    }

    private function modify_sort_key($query, $sort_key) {
        $modified_sort_key = $sort_key;

        $fields = $this->{$this->main_model}->schema()->columns();
        if (in_array($sort_key, $fields, true)) {
            $modified_sort_key = sprintf("%s.%s", $this->main_model, $sort_key);
        } else {
            $soft_key_updated = false;
            foreach ($query->contain() as $table_name => $value) {
                if ($soft_key_updated) {
                    break;
                }

                $modified_table_name =
                    Inflector::underscore(Inflector::singularize($table_name));
                $fields = $this->{$table_name}->schema()->columns();
                foreach ($fields as $field) {
                    $candidate_field = sprintf("%s_%s", $modified_table_name, $field);
                    if ($candidate_field === $sort_key) {
                        $modified_sort_key = sprintf("%s.%s", $table_name, $field);
                        $soft_key_updated = true;
                        break;
                    }
                }
            }
        }

        return $modified_sort_key;
    }

    public function add($user) {
        return $this->_addOrEdit($user, 'add');
    }

    public function edit($user) {
        return $this->_addOrEdit($user, 'edit');
    }

    private function isset_data_id() {
        if (!isset($this->request->data['id'])) {
            $message = __('Could not update item');
            $this->controller->set([
                'errors' => ['id' => "This field is required"],
                'success' => false,
                'message' => ['message' => $message],
                '_serialize' => ['errors','success','message']
            ]);
            return false;
        }
        return true;
    }

    public function generateErrorHashFromEntity($entity) {
        # Generate error hash from $entity->errors().
        $error_hash = [];
        $errors = $entity->errors();
        foreach (array_keys($errors) as $field) {
            $detail_string = '';
            $error_detail =  $errors[$field];
            foreach (array_keys($error_detail) as $error) {
                $detail_string = $detail_string." ".$error_detail[$error];
            }
            $error_hash[$field] = $detail_string;
        }
        return $error_hash;
    }

    private function _addOrEdit($user, $type= 'add') {
        try {
            if (!$user || !isset($this->main_model)) {
                return false;
            }

            if ($type == 'add') {
                $entity = $this->{$this->main_model}->newEntity($this->request->data());
            } else if ($type == 'edit' && $this->isset_data_id()) {
                $entity = $this->{$this->main_model}->get($this->request->data['id']);

                // Check whether the entity to be edited can be accessed
                // with the authority of the user who is operating it.
                if (!$this->checkEntityWritePermByUserAndGroup($entity, $user)) {
                    // Access to an unauthorized entity returns Forbidden.
                    // If there is no user_id column or user model is not passed,
                    // only the group is determined.
                    throw new ForbiddenException();
                }

                $this->{$this->main_model}->patchEntity($entity, $this->request->data());
            } else {
                return false;
            }

            // Check if the user_id of the entity after creation/editing can be handled with
            // the authority of the user who is operating the entity.
            if (!$this->checkEntityWritePermByUserAndGroup($entity, $user)) {
                // Access to an unauthorized entity returns Forbidden.
                // If there is no user_id column or user model is not passed,
                // only the group is determined.
                throw new ForbiddenException();
            }

            if ($this->{$this->main_model}->save($entity)) {
                $this->controller->set([
                    'success' => true,
                    '_serialize' => ['success']
                ]);
                return $entity;
            } else {
                # Validation check failed.
                $error_hash = $this->generateErrorHashFromEntity($entity);
                $type = $type =='add' ? __('create') : __('update');
                $message = __("Could not {0} item", $type);

                $this->controller->set([
                    'errors' => $error_hash,
                    'success' => false,
                    'message' => ['message' => $message],
                    '_serialize' => ['errors','success','message']
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->controller->set(array(
                'success' => false,
                'message' => ['message' => $e->getMessage()],
                '_serialize' => array('success','message')
            ));
            return false;
        }
    }

    public function delete($user, $id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $fail_flag = false;
        if (isset($this->request->data['id'])) { //Single item delete
            $message = "Single item ".$this->request->data['id'];

            try {
                $this->_checkAndDelete($this->request->data['id'], $user);
            } catch (Exception $e) {
                $fail_flag = true;
            }

            // Note: The following deletion method will not work for relationships.
            #$this->{$this->main_model}->query()->delete()->where(
            #    ['id' => $this->request->data['id']])->execute();
        } else {                                //Assume multiple item delete
            foreach ($this->request->data as $d) {
                try {
                    $this->_checkAndDelete($d['id'], $user);
                } catch (Exception $e) {
                    $fail_flag = true;
                }

                // Note: The following deletion method will not work for relationships.
                #$this->{$this->main_model}->query()->delete()->where(
                #['id' => $d['id']])->execute();
            }
        }
        
        if ($fail_flag == true) {
            $this->controller->set([
                'success'   => false,
                'message'   => ['message' => __('Could not delete some items')],
                '_serialize' => ['success','message']
            ]);
            return false;
        } else {
            $this->controller->set([
                'success' => true,
                '_serialize' => ['success']
            ]);
            return true;
        }
    }

    public function getIds() {
        $ids = [];
        if ($this->request->is('get')) {
            $id = $this->request->query('id');
            if (!is_null($id)) {
                $ids[] = $id;
            } 
        } else {
            $id = $this->request->data('id');
            if (!is_null($id) && isset($id)) {
                $ids[] = $id;
            } else {
                foreach ($this->request->data as $d) {
                    try {
                        $ids[] = $d['id'];
                    } catch (Exception $e) {
                    }
                }
            }
        }
        return $ids;
    }

    private function _checkAndDelete($id, $user) {
        $entity = $this->{$this->main_model}->get($id);
        if (is_null($entity)) {
            throw new NotFoundException();
        } else if (!$this->checkEntityWritePermByUserAndGroup($entity, $user)) {
            // Access to an unauthorized entity returns Forbidden.
            // If there is no user_id column or user model is not passed,
            // only the group is determined.
            throw new ForbiddenException();
        }
        $this->{$this->main_model}->delete($entity);
    }

    public function setUserIdIfEmpty($user) {
        $user_id = $user['id'];

        if (is_null($this->request->data('user_id'))) {
            $this->request->data['user_id'] = $user_id;
        }
    }

    public function modifyUserId($user) {
        $user_id = $user['id'];

        // Get the creator's id
        if (isset($this->request->data['user_id'])) {
            // This is the holder of the token - override '0'
            if ($this->request->data['user_id'] == '0') {
                $this->request->data['user_id'] = $user_id;
            }
        }
    }

    public function modifyCheckBox($check_items) {
        foreach ($check_items as $i) {
            if (isset($this->request->data[$i])) {
                $this->request->data[$i] = 1;
            } else {
                $this->request->data[$i] = 0;
            }
        }
    }

    public function getChildUsers($user) {
        if (!isset($this->Users)) {
            return null;
        }
        return $this->Users->find('children', ['for' => $user['id']])->all();
    }

    public function getChildUserIds($user) {
        $children = $this->getChildUsers($user);
        if (is_null($children)) {
            return [];
        }
        $user_ids = [];
        foreach ($children as $child) {
            $user_ids[] = $child['id'];
        }
        return $user_ids;
    }

    public function getParentUsers($user) {
        if (!isset($this->Users)) {
            return null;
        }
        return $this->Users->find('path', ['for' => $user['id']])
                           ->where(['id !=' => $user['id']])
                           ->all();
    }

    public function getParentUserIds($user) {
        $parents = $this->getParentUsers($user);
        if (is_null($parents)) {
            return [];
        }
        $user_ids = [];
        foreach ($parents as $parent) {
            $user_ids[] = $parent['id'];
        }
        return $user_ids;
    }

    public function filterQueryByUser($query, $user, $append = ['child', 'parent']) {
        // If there is no "user_id" in the schema column, nothing is done.
        $dup_query = $query;
        $columns = $dup_query->repository()->schema()->columns();
        $alias = $dup_query->repository()->alias();
        if (!in_array('user_id', $columns, true)) {
            return;
        }

        // Data created by the user calling the API.
        $filter_array = [
            [$alias.'.user_id' => $user['id']],
        ];

        if (in_array('child', $append, true)) {
            // Data created by child users.
            $child_user_ids = $this->getChildUserIds($user);
            if (count($child_user_ids) > 0) {
                $filter_array[] = [$alias.'.user_id IN' => $child_user_ids];
            }
        }

        if (in_array('parent', $append, true)) {
            // Data created by the parent user and for which "available_to_siblings" is true.
            if (in_array('available_to_siblings', $columns, true)) {
                $parent_user_ids = $this->getParentUserIds($user);
                if (count($parent_user_ids) > 0) {
                    $filter_array[] = [
                        $alias.'.user_id IN' => $parent_user_ids,
                        $alias.'.available_to_siblings' => true,
                    ];
                }
            }
        }

        $query->where(['OR' => $filter_array]);
    }

    public function filterQueryByUserAndGroup($query, $user, $append = ['child', 'parent']) {
        if ($user['group_name'] == Configure::read('group.admin')) {
            // No restrictions are imposed in the case of the administrator group.
        } else if ($user['group_name'] == Configure::read('group.ap') ||
                   $user['group_name'] == Configure::read('group.sm')) {
            // In the case of the "Access Providers" group, the decision is based on
            // the permissions of each user.
            $this->filterQueryByUser($query, $user, $append);
        } else {
            // For other groups, only records created by the individual are permitted.
            $this->filterQueryByUser($query, $user, []);
        }
    }

    public function checkEntityPermByUser($entity, $user, $perm = 'rw',
                                          $append = ['child', 'parent']) {
        if (!isset($entity->user_id) || is_null($user)) {
            return false;
        }

        // Entity created by the user calling the API.
        if ($entity->user_id === $user['id']) {
            return true;
        }

        if (in_array('child', $append, true)) {
            // Entity created by child users.
            $child_user_ids = $this->getChildUserIds($user);
            if (in_array($entity->user_id, $child_user_ids, true)) {
                return true;
            }
        }

        if (in_array('parent', $append, true)) {
            if ($perm === 'ro') {
                // Data created by the parent user and for which
                // "available_to_siblings" is true.
                if (isset($entity->available_to_siblings) &&
                    $entity->available_to_siblings) {
                    $parent_user_ids = $this->getParentUserIds($user);
                    if (in_array($entity->user_id, $parent_user_ids, true)) {
                        return true;
                    }
                }
            } else {
                // Writing to entities created by the parent is not permitted.
            }
        }

        // The child does not have write access to the entity created by the parent.
        return false;
    }

    public function checkEntityReadPermByUserAndGroup($entity, $user) {
        if ($user['group_name'] == Configure::read('group.admin')) {
            // No restrictions are imposed in the case of the administrator group.
            return true;
        } else if ($user['group_name'] == Configure::read('group.ap') ||
                   $user['group_name'] == Configure::read('group.sm')) {
            // In the case of the "Access Providers" group, the decision is based on
            // the permissions of each user.
            return $this->checkEntityPermByUser($entity, $user, 'ro', ['child', 'parent']);
        } else {
            // For other groups, only records created by the individual are permitted.
            return $this->checkEntityPermByUser($entity, $user, 'ro', []);
        }
    }

    public function checkEntityWritePermByUserAndGroup($entity, $user) {
        if ($user['group_name'] == Configure::read('group.admin')) {
            // No restrictions are imposed in the case of the administrator group.
            return true;
        } else if ($user['group_name'] == Configure::read('group.ap') ||
                   $user['group_name'] == Configure::read('group.sm')) {
            // In the case of the "Access Providers" group, the decision is based on
            // the permissions of each user.
            return $this->checkEntityPermByUser($entity, $user, 'rw', ['child']);
        } else {
            // For other groups, only records created by the individual are permitted.
            return $this->checkEntityPermByUser($entity, $user, 'rw', []);
        }
    }

    public function checkRealmPermByUserAndGroup($realm, $user, $right = 'read') {
        // Candidates for $right are "create", "read", "update", and "delete".
        if (!isset($this->RealmAcl)) {
            throw new InternalErrorException();
        }

        if ($user['group_name'] == Configure::read('group.admin')) {
            // No restrictions are imposed in the case of the administrator group.
            return true;
        } else if ($user['group_name'] == Configure::read('group.ap') ||
                   $user['group_name'] == Configure::read('group.sm')) {
            // Check for user_id and available_to_siblings of the realm.
            if (!$this->checkEntityReadPermByUserAndGroup($realm, $user)) {
                return false;
            }

            $user_id  = $user['id'];
            $realm_id = $realm->id;
            $realm_owner_id = $realm->{'user_id'};
            if ($realm_owner_id !== $user_id) {
                // We only list realms which the Access Provider has add right
                if (!$this->RealmAcl->can_manage_realm($user_id, $realm_owner_id,
                                                       $realm_id, $right)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function generateUuid() {
        $chars = str_split(self::UUID_V4_FORMAT);
        foreach ($chars as $i => $char) {
            if ($char === 'x') {
                $chars[$i] = dechex(random_int(0, 15));
            } elseif ($char === 'y') {
                $chars[$i] = dechex(random_int(8, 11));
            }
        }
        return join('', $chars);
    }

    public function handleException($e) {
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
        } else if (method_exists($e, 'getMessage')) {
            $error_hash = [];
            $message = $e->getMessage();
        } else {
            $error_hash = [];
            $message = '';

            if (isset($e['errors'])) {
                $error_hash = $e['errors'];
            }
            if (isset($e['message'])) {
                $message = $e['message'];
            }
        }

        $this->controller->set([
            'errors' => $error_hash,
            'success' => false,
            'message' => ['message' => $message],
            '_serialize' => ['errors','success','message']
        ]);
    }
}

