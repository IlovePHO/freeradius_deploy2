<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class SubGroupsTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');  
        $this->belongsTo('Users');
        $this->belongsTo('Idps');
        $this->belongsTo('Profiles', ['propertyName' => 'real_profile']);
        $this->belongsTo('Realms', ['propertyName' => 'real_realm']);

        $this->hasMany('PermanentUsers');
    }

    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $validator
            ->requirePresence('name', 'create')
            ->requirePresence('user_id', 'create')
            ->requirePresence('realm_id', 'create')
            ->requirePresence('idp_id', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('user_id', 'user_id is required')
            ->notEmpty('realm_id', 'realm_id is required')
            ->notEmpty('idp_id', 'idp_id is required')
            ->allowEmptyString('profile')
            ->nonNegativeInteger('user_id')
            ->nonNegativeInteger('realm_id')
            ->nonNegativeInteger('idp_id');

        return $validator;
    }
}
