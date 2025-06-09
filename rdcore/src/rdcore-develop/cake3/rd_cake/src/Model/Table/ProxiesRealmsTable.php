<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ProxiesRealmsTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->belongsTo('Proxies');
        $this->belongsTo('Realms');
    }
    
    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $validator
            ->requirePresence('proxy_id', 'create')
            ->requirePresence('realm_id', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('proxy_id', 'proxy_id is required')
            ->notEmpty('realm_id', 'proxy_id is required')
            ->nonNegativeInteger('proxy_id')
            ->nonNegativeInteger('realm_id');

        return $validator;
    }
}
