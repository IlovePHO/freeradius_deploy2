<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class SoftEtherNetworkBridgesTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
        $this->hasMany('SoftEtherInterfaces', [
            // 'dependent' => true,
            'foreignKey' => 'bridge_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('bridge_name', 'create')
            ->notBlank('bridge_name','Value is required')
            ->maxLength('bridge_name',15)
            ->add('bridge_name', [ 
                'unique' => [
                    'message' => 'The bridge name you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ]);

        $validator
            ->requirePresence('ip_address', 'create')
            ->notBlank('ip_address','Value is required')
            ->ip('ip_address');

        $validator
            ->requirePresence('subnet_mask', 'create')
            ->notBlank('subnet_mask','Value is required')
            ->ip('subnet_mask');

        return $validator;
    }

    public function afterDelete($event, $entity, $options){
        $interfacesTable = TableRegistry::getTableLocator()->get('SoftEtherInterfaces');
        $q_r = $interfacesTable->find()->where(['bridge_id' => $entity->id])->all();
        foreach($q_r as $if) {
            $if->bridge_id = 0;
            $interfacesTable->save($if);
        }  
    }

}
