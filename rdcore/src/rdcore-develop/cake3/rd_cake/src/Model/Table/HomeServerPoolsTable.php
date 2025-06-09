<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class HomeServerPoolsTable extends Table {

    public function initialize(array $config){
        $this->addBehavior('FreeRadiusHomeServers');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users'); 
        $this->hasMany('HomeServers',['dependent' => true]);  
    }
 
    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $filedName = __('name');
        $validator
            ->requirePresence('name', 'create')
            ->requirePresence('type', 'create')
            ->notEmpty('name', __('A {0} is required', $filedName))
            ->regex('name', '/^[0-9a-zA-Z$&*+\-\.\^_\~]+$/')
            ->integer('user_id')
            ->add('name', [ 
                'nameUnique' => [
                    'message' => __('The {0} you provided is already taken. Please provide another one.',$filedName),
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ])
            ->inList('type', ['fail-over', 'load-balance', 'client-balance',
                              'client-port-balance', 'keyed-balance']);
        return $validator;
    }
       
}
