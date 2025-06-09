<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use Cake\Event\Event;
use ArrayObject;

class SoftEtherLocalBridgesTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('hub_name', 'create')
            ->notBlank('hub_name','Value is required');

        $validator
            ->requirePresence('device_name', 'create')
            ->notBlank('device_name','Value is required')
            ->maxLength('device_name',10, null, function($context) {
                return $context['data']['tap_mode'];
            });

        return $validator;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
        if ($data['tap_mode']) {
            $data['device_name'] = isset($data['tap_device_name']) ? $data['tap_device_name'] : null;
        }
    }

    public function afterSave($event, $entity, $options){
        if ($entity->tap_mode){
            $tapDeviceName = 'tap_' . strtolower($entity->device_name);
            $interfacesTable = TableRegistry::getTableLocator()->get('SoftEtherInterfaces');
            if (!($interfacesTable->exists(['if_name' => $tapDeviceName]))) {
                $interfacesEntity = $interfacesTable->newEntity();
                $interfacesEntity->if_name = $tapDeviceName;
                $interfacesEntity->tap_mode = true;
                $interfacesTable->save($interfacesEntity);
            }
        }  
    }

    public function afterDelete($event, $entity, $options){
        if ($entity->tap_mode){
            $tapDeviceName = 'tap_' . strtolower($entity->device_name);
            $interfacesTable = TableRegistry::getTableLocator()->get('SoftEtherInterfaces');
            if ($interfacesTable->exists(['if_name' => $tapDeviceName])) {
                $interfacesEntity = $interfacesTable->find()->where(['if_name' => $tapDeviceName])->first();
                $interfacesTable->delete($interfacesEntity);
            }
        }  
    }

}
