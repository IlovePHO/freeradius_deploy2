<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class SoftEtherVirtualHubsTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
        $this->hasMany('SoftEtherUsers', [
            'dependent' => true,
            'foreignKey' => 'hub_id'
        ]);
        $this->hasOne('SoftEtherSecureNats', [
            'dependent' => true,
            'foreignKey' => 'hub_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('hub_name', 'create')
            ->notBlank('hub_name','Value is required')
            ->add('hub_name', [ 
                'unique' => [
                    'message' => 'The hub name you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ]);

        $validator
            ->requirePresence('password', 'create')
            ->notBlank('password','Value is required');

        $validator
            //->requirePresence('default_gateway', 'create')
            ->notBlank('default_gateway','Value is required')
            ->ip('default_gateway');

        $validator
            //->requirePresence('default_subnet', 'create')
            ->notBlank('default_subnet','Value is required')
            ->ip('default_subnet');

        return $validator;
    }

    public function afterSave($event, $entity, $options){
        if ($entity->isNew()){
            $secureNatsTable = TableRegistry::getTableLocator()->get('SoftEtherSecureNats');
            $secureNatsEntity = $secureNatsTable->newEntity();
            $secureNatsEntity->hub_id = $entity->id;
            $secureNatsEntity->enabled = false;
            $secureNatsTable->save($secureNatsEntity);
        }  
    }

}
