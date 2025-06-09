<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

use Cake\I18n\FrozenDate;

class EncodingSchemesTable extends Table
{
    const SUFFIX_LENGTH = 3;

    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');  
        $this->hasMany('StaConfigs');
    }

    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $validator
            ->requirePresence('name', 'create')
            ->requirePresence('suffix', 'create')
            ->requirePresence('expire', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('name', 'name is required')
            ->notEmpty('suffix', 'suffix is required')
            ->notEmpty('expire', 'expire is required')
            ->regex('suffix', '/^[0-9a-zA-Z]+$/')
            ->add('name', [ 
                'nameUnique' => [
                    'message' => 'The name you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ])
            ->add('suffix', [
                'suffixUnique' => [
                    'message' => 'The suffix you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique',
                    'provider' => 'table'
                ],
                'suffixMinLength' => [
                    'message' => 'The number of characters in the suffix is incorrect.',
                    'rule' => ['minLength', self::SUFFIX_LENGTH],
                ],
                'suffixMaxLength' => [
                    'message' => 'The number of characters in the suffix is incorrect.',
                    'rule' => ['maxLength', self::SUFFIX_LENGTH],
                ],
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
