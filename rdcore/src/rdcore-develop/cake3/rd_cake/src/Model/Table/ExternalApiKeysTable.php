<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ExternalApiKeysTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');  
        $this->belongsTo('Users');
        $this->belongsTo('Realms');
        $this->belongsTo('Profiles');
    }

    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $validator
            ->requirePresence('name', 'create')
            ->requirePresence('user_id', 'create')
            ->requirePresence('realm_id', 'create')
            ->requirePresence('profile_id', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('name')
            ->notEmpty('user_id')
            ->notEmpty('realm_id')
            ->notEmpty('profile_id')
            ->nonNegativeInteger('user_id')
            ->nonNegativeInteger('realm_id')
            ->nonNegativeInteger('profile_id')
            ->add('name', [ 
                'nameUnique' => [
                    'message' => 'The name you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ]);

        return $validator;
    }
}
