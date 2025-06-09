<?php

namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

use Cake\Core\Configure;
use Cake\Utility\Security;

class CryptoBehavior extends Behavior {
    public function initialize(array $config) {
    }

    public function encrypt($value) {
        $key = Configure::read('AES.key');
        $encrypted_bin = Security::encrypt($value, $key);
        return base64_encode($encrypted_bin);
    }

    public function decrypt($value) {
        $key = Configure::read('AES.key');
        $encrypted_bin = base64_decode($value);
        return Security::decrypt($encrypted_bin, $key);
    }

    public function beforeSave($event, $entity) {
        $this->copyClientSecret($entity);
        $this->encryptParams($entity);
    }

    private function copyClientSecret($entity) {
        if ($entity->has('credential')) {
            $credential = json_decode($entity->{'credential'});
            if (is_object($credential)) {
                if (isset($credential->web)) {
                    // Processing for JSON of Google's OAuth credential.
                    // Because "client_secret" is only included in the initial download.
                    if (isset($credential->web->client_secret)) {
                        $client_secret = json_encode(['value' =>
                                                     $credential->web->client_secret]);
                        $entity->{'client_secret'} = $client_secret;
                    }
                }
            }
        }
    }

    private function encryptParams($entity) {
        $check_items = array(
            'credential', 'token', 'client_secret',
        );

        foreach ($check_items as $i) {
            if ($entity->has($i)) {
                if (is_object(json_decode($entity->{$i}))) {
                    $entity->{$i} = $this->encrypt($entity->{$i});
                }
            }
        }
    }
}
?>
