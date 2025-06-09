<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\I18n\FrozenDate;

class StaConfigsTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->belongsTo('EncodingSchemes');
        $this->belongsToMany('Realms', ['through' => 'StaConfigsRealms']);
        $this->belongsToMany('SubGroups');
        $this->belongsToMany('StaInfos');
        #$this->hasMany('StaConfigsRealms');
        #$this->hasMany('StaConfigsSubGroups');
    }

    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $validator
            ->requirePresence('name', 'create')
            ->requirePresence('user_id', 'create')
            ->requirePresence('eap_method', 'create')
            ->requirePresence('home_domain', 'create')
            ->requirePresence('friendly_name', 'create')
            ->requirePresence('encoding_scheme_id', 'create')
            ->requirePresence('expire', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('name')
            ->notEmpty('user_id')
            ->notEmpty('eap_method')
            ->notEmpty('home_domain')
            ->notEmpty('friendly_name')
            ->notEmpty('encoding_scheme_id')
            ->notEmpty('expire')
            ->nonNegativeInteger('id')
            ->nonNegativeInteger('user_id')
            ->nonNegativeInteger('encoding_scheme_id')
            ->inList('eap_method', ['peap',
                                    #'eap-tls', // Currently eap-tls is not supported.
                                    'eap-ttls/pap',
                                    'eap-ttls/mschap',
                                    'eap-ttls/mschapv2'])
            ->date('expire')
            ->add('name', [ 
                'nameUnique' => [
                    'message' => 'The name you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ])
            ->add('expire', [
                'expireIsFuture' => [
                    'message' => 'The expire you provided is a past date.',
                    'rule' => function($value) {
                        return (new FrozenDate($value))->isFuture();
                    },
                ],
            ]);

        return $validator;
    }
}
