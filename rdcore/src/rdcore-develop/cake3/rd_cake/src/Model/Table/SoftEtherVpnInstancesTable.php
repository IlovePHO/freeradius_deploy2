<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SoftEtherVpnInstancesTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('ip_address', 'create')
            ->notBlank('ip_address','Value is required')
            ->ip('ip_address')
            ->add('ip_address', [ 
                'unique' => [
                    'message' => 'The ip address you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ]);

        #$validator
        #    ->requirePresence('admin_name', 'create')
        #    ->notBlank('admin_name','Value is required');

        $validator
            ->requirePresence('password', 'create')
            ->notBlank('password','Value is required');

        return $validator;
    }

}
