<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class StaConfigsRealmsTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');  
        $this->belongsTo('StaConfigs');
        $this->belongsTo('Realms');
    }

    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $validator
            ->requirePresence('sta_config_id', 'create')
            ->requirePresence('realm_id', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('sta_config_id')
            ->notEmpty('realm_id')
            ->nonNegativeInteger('sta_config_id')
            ->nonNegativeInteger('realm_id');

        return $validator;
    }
}
