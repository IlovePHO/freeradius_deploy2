<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AttributeConvertsTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');  
    }
    
    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $validator
            ->requirePresence('src', 'create')
            ->notEmpty('src', 'A src is required')
            ->regex('src', '/^[0-9a-zA-Z\-_]+$/')
            ->allowEmptyString('dst')
            ->regex('dst', '/^[0-9a-zA-Z\-_]+$/');

        return $validator;
    }
    
}
