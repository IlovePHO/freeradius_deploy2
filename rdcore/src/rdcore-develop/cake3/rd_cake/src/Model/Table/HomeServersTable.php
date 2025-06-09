<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class HomeServersTable extends Table {

    public function initialize(array $config){
        $this->addBehavior('Timestamp');
        $this->belongsTo('HomeServerPools');    
    }
 
    public function validationDefault(Validator $validator) {
        $validator = new Validator();
        $validator->setProvider('Lifeseed', 'App\Model\Validation\LifeseedValidation');
        $filedName = __('name');
        $validator
            ->requirePresence('home_server_pool_id', 'create')
            ->requirePresence('name', 'create')
            ->requirePresence('secret', 'create')
            ->requirePresence('type', 'create')
            ->requirePresence('proto', 'create')
            ->requirePresence('ipaddr', 'create')
            ->requirePresence('port', 'create')
            ->requirePresence('status_check', 'create')
            ->requirePresence('priority', false)
            ->add('name', '', [
                      'rule' => ['fqdnFormat'],
                      'provider' => 'Lifeseed',
                  ])
            ->add('name', [
                  'nameUnique' => [
                      'message' => __('The {0} you provided is already taken. Please provide another one.',$filedName),
                      'rule' => 'validateUnique',
                      'provider' => 'table']
                  ])
            ->inList('type', ['auth+acct', 'auth', 'acct'])
            ->inList('proto', ['tcp', 'udp'])
            ->add('ipaddr', '', [
                      'rule' => ['ipv4Format'],
                      'provider' => 'Lifeseed',
                  ])
            ->nonNegativeInteger('port')
            ->range('port', [1, 65535])
            ->inList('status_check', ['none', 'status-server', 'request'])
            ->nonNegativeInteger('priority');

        return $validator;
    }
}
