<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SoftEtherUsersTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
        $this->belongsTo('SoftEtherVirtualHubs', [
            'foreignKey'    => 'hub_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('hub_id', 'create')
            ->notBlank('hub_id','Value is required');

        $validator
            ->requirePresence('user_name', 'create')
            ->notBlank('user_name','Value is required')
            ->add('user_name', [ 
                'unique' => [
                    'message' => 'The user name you provided is already taken. Please provide another one.',
                    'rule' => ['validateUnique', ['scope' => 'hub_id']], 
                    'provider' => 'table'
                ]
            ]);

        $validator
            ->requirePresence('auth_password', 'create')
            ->notBlank('auth_password','Value is required');

        return $validator;
    }

}
