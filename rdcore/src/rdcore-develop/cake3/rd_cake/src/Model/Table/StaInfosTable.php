<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class StaInfosTable extends Table
{
    public function initialize(array $config) {
        $this->addBehavior('Timestamp');
        $this->belongsToMany('StaConfigs');
        $this->belongsTo('PermanentUsers');
        $this->belongsTo('Vouchers');
        #$this->belongsToMany('PermanentUsers');
        #$this->belongsToMany('Vouchers');
    }

    public function validationDefault(Validator $validator) {
        $validator = new Validator();
        $validator
            ->requirePresence('device_type', 'create')
            ->requirePresence('device_unique_id', 'create')
            ->requirePresence('short_unique_id', 'create')
            ->requirePresence('device_token', 'create')
            ->inList('device_type', ['android', 'ios', 'windows', 'macos'])
            ->regex('device_token', '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');

        return $validator;
    }
}
