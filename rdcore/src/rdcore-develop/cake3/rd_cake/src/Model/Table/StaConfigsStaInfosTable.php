<?php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class StaConfigsStaInfosTable extends Table
{
    const SALT_LENGTH = 3;

    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->belongsTo('StaConfigs');
        $this->belongsTo('StaInfos');
    }
    
    public function validationDefault(Validator $validator){
        $validator = new Validator();
        $validator
            ->requirePresence('sta_config_id', 'create')
            ->requirePresence('sta_info_id', 'create')
            ->requirePresence('id', 'update')
            ->notEmpty('sta_config_id')
            ->notEmpty('sta_info_id')
            ->nonNegativeInteger('sta_config_id')
            ->nonNegativeInteger('sta_info_id');

        return $validator;
    }

    public function beforeSave($event, $entity) {
        $this->_fillSalt($entity);
    }

    private function _generateSalt($length) {
        $chars = [];
        for ($i = 0; $i < $length; $i++) {
            $chars[$i] = dechex(random_int(0, 15));
        }
        return join('', $chars);
    }

    private function _fillSalt($entity) {
        if (!$entity->has('salt') || strlen($entity->salt) === 0) {
            $entity->salt = $this->_generateSalt(self::SALT_LENGTH);
        }
    }
}
