<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SoftEtherL2tpIpsecTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            // ->requirePresence('ipsec_secret', 'create')
            ->notBlank('ipsec_secret','Value is required');

        $validator
            // ->requirePresence('l2tp_defaulthub', 'create')
            ->notBlank('l2tp_defaulthub','Value is required');

        return $validator;
    }

}
