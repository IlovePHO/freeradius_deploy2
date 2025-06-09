<?php

namespace App\Controller;

use Cake\Core\Configure;

/**
 * Groups Controller
 *
 * @property Group $Group
 */
class GroupsController extends AppController {

    public $main_model       = 'Groups';
    public $base    = "Access Providers/Controllers/Groups/";

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Groups');

        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');

        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        $this->loadComponent('LifeSeed', ['models' => ['Groups']]);
    }
/**
 * index method
 *
 * @return void
 */
	public function index() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

//		$this->Groups->recursive = 0;
		$this->set('groups', $this->paginate());
	}

	public function indexAdmin() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query = $this->Groups->find();
        $query->where(['Groups.name !=' => Configure::read('group.admin')]);
        $query->where(['Groups.name !=' => Configure::read('group.user')]);

        // If the accessing user belongs to Site Managers,
        // only Site Managers will be returned.
        if ($user["group_name"] == Configure::read('group.sm')) {
            $query->where(['Groups.name' => Configure::read('group.sm')]);
        }

        $callback = function($user, $i, &$row) {
            $row['name'] = __($row['name']);
        };

        $this->LifeSeed->index($user, $query, [], [], $callback);
	}


/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

		if (! $this->Groups->find()->where(['Groups.id' => $id])->exists()) {
			throw new NotFoundException(__('Invalid group'));
		}
		$this->set('group', $this->Groups->find()->where(['Groups.id' => $id])->first());
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

		if ($this->request->is('post')) {
			$groupEntity = $this->Groups->newEntity($this->request->getData());

			if ($this->Groups->save($groupEntity)) {
				$this->Flash->set(__('The group has been saved'));
				$this->redirect(['action' => 'index']);
			} else {
				$this->Flash->set(__('The group could not be saved. Please, try again.'));
			}
		}
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

		if (! $this->Groups->find()->where(['Groups.id' => $id])->exists()) {
			throw new NotFoundException(__('Invalid group'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
            $groupEntity = $this->Groups->newEntity($this->request->getData());

            if ($this->Groups->save($groupEntity)) {
				$this->Flash->set(__('The group has been saved'));
				$this->redirect(['action' => 'index']);
			} else {
				$this->Flash->set(__('The group could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->Groups->find()->where(['Groups.id' => $id])->first();
		}
	}

/**
 * delete method
 *
 * @throws MethodNotAllowedException
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
        //__ Authentication + Authorization __
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

		if (! $this->request->is('post')) {
			throw new MethodNotAllowedException();
		}

		if (! $this->Groups->find()->where(['Groups.id' => $id])->exists()) {
			throw new NotFoundException(__('Invalid group'));
		}
        if ($this->Groups->query()->delete()->where(['Groups.id' => $id])->execute()) {
			$this->Flash->set(__('Group deleted'));
			$this->redirect(['action' => 'index']);
		}
		$this->Flash->set(__('Group was not deleted'));
		$this->redirect(['action' => 'index']);
	}
}
