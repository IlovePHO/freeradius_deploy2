<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdpsTable extends Table
{
    public function initialize(array $config) {
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->belongsTo('Realms');

        $this->hasMany('IdpOauthClientCredentials', ['dependent' => true]);
        $this->hasMany('SubGroups', ['dependent' => true]);
    }

    public function validationDefault(Validator $validator) {
        $validator = new Validator();
        $validator->setProvider('Lifeseed', 'App\Model\Validation\LifeseedValidation');
        $validator
            ->requirePresence('name', 'create')
            ->requirePresence('type', 'create')
            ->requirePresence('auth_type', 'create')
            ->requirePresence('user_id', 'create')
            ->requirePresence('realm_id', 'create')
            ->requirePresence('domain', 'create')
            ->add('name', [
                  'nameUnique' => [
                      'message' => 'The name you provided is already taken. '.
                                   'Please provide another one.',
                      'rule' => 'validateUnique',
                      'provider' => 'table']
                  ])
            ->inList('type', ['google_workspace', 'azure_ad', 'direct'])
            ->inList('auth_type', ['oauth', 'none'])
            ->add('domain', '', [
                      'rule' => ['fqdnFormat'],
                      'provider' => 'Lifeseed',
                  ]);

        return $validator;
    }
}
