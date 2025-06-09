<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdpOauthClientCredentialsTable extends Table
{
    public function initialize(array $config) {
        $this->addBehavior('Timestamp');
        $this->addBehavior('Crypto');

        $this->belongsTo('Idps');

        $this->hasMany('IdpOauthClientTokens', ['dependent' => true]);
    }

    public function validationDefault(Validator $validator) {
        $validator = new Validator();
        $validator->setProvider('Lifeseed', 'App\Model\Validation\LifeseedValidation');
        $validator
            #->add('name', [
            #          'nameUnique' => [
            #              'message' => 'The name you provided is already taken. '.
            #                           'Please provide another one.',
            #              'rule' => 'validateUnique',
            #              'provider' => 'table',
            #          ],
            #      ])
            // TODO: It would be more helpful to indicate the cause of variation errors.
            ->add('credential', '', [
                      'rule' => ['oauthCredentialFormat'],
                      'provider' => 'Lifeseed',
                      'on' => 'create',
                  ]);

        return $validator;
    }
}
