<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ProxiesTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');  
        $this->belongsTo('Users');
        $this->belongsTo('HomeServerPools');
        $this->belongsToMany('Realms');
    }
    
    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $filedName = __('name');
        $validator
            ->requirePresence('name', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('name', __('A {0} is required', $filedName))
            ->regex('name', '/^[0-9a-zA-Z$&*+\-\.\^_\~]+$/')
            ->add('name', [ 
                'nameUnique' => [
                    'message' => __('The {0} you provided is already taken. Please provide another one.',$filedName),
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ]);

        return $validator;
    }
    
}
