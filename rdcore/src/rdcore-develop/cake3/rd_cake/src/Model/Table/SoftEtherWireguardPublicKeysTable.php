<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SoftEtherWireguardPublicKeysTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('public_key', 'create')
            ->notBlank('public_key','Value is required')
            ->add('public_key', [ 
                'unique' => [
                    'message' => 'The public key you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ]);

        $validator
            ->requirePresence('hub_name', 'create')
            ->notBlank('hub_name','Value is required');

        $validator
            ->requirePresence('user_name', 'create')
            ->notBlank('user_name','Value is required');

        return $validator;
    }

}
