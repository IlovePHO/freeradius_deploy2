<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdpOauthClientTokensTable extends Table
{
    public function initialize(array $config) {
        $this->addBehavior('Timestamp');
        $this->addBehavior('Crypto');

        $this->belongsTo('IdpOauthClientCredentials');
        $this->belongsTo('Users');
    }
}
