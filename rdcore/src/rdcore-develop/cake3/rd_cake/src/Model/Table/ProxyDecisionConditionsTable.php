<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ProxyDecisionConditionsTable extends Table {
    public function initialize(array $config) {
        $this->addBehavior('Timestamp');
        $this->belongsTo('Proxies');
    }
    
    public function validationDefault(Validator $validator) {
        $validator = new Validator();
        $validator->setProvider('Lifeseed', 'App\Model\Validation\LifeseedValidation');
        $validator
            ->requirePresence('proxy_id', 'create')
            ->requirePresence('ssid', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('proxy_id', 'proxy_id is required')
            ->notEmptyString('ssid', 'ssid is required')
            ->allowEmptyString('user_name_regex')
            ->nonNegativeInteger('id')
            ->nonNegativeInteger('proxy_id')
            ->nonNegativeInteger('priority')
            ->add('ssid', '', [
                      'rule' => ['regexFormat'],
                      'provider' => 'Lifeseed',
                  ])
            ->add('user_name_regex', '', [
                      'rule' => ['regexFormat'],
                      'provider' => 'Lifeseed',
                  ]);

        return $validator;
    }
}
