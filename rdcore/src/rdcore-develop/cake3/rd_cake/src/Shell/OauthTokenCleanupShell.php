<?php

namespace App\Shell;

use Cake\Console\Shell;
use Cake\I18n\Time;

class OauthTokenCleanupShell extends Shell{

    public function initialize() {
        parent::initialize();
        $this->loadModel('IdpOauthClientTokens');
    }

    public function main() {
        $this->_cleanupIdpOauthAccessToken();
    }
    
    private function _cleanupIdpOauthAccessToken() {
        $threshold = new Time("2 weeks ago");

        $query = $this->IdpOauthClientTokens->find();
        $query->where(['IdpOauthClientTokens.created <=' => $threshold]);
        $q_r = $query->all();

        foreach ($q_r as $q) {
            $this->IdpOauthClientTokens->delete($q);
        }
    }
}

?>
