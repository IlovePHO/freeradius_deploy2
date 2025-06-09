<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SoftEtherInterfacesTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
        $this->belongsTo('SoftEtherNetworkBridges', [
            'foreignKey'    => 'bridge_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('if_name', 'create')
            ->notBlank('if_name','Value is required')
            ->maxLength('if_name',15)
            ->add('if_name', [ 
                'unique' => [
                    'message' => 'The if name you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ]);

        return $validator;
    }

}
