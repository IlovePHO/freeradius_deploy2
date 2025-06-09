<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SoftEtherWireguardConfigsTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('preshared_key', 'create')
            ->notBlank('preshared_key','Value is required');

        $validator
            ->requirePresence('private_key', 'create')
            ->notBlank('private_key','Value is required');

        return $validator;
    }

}
