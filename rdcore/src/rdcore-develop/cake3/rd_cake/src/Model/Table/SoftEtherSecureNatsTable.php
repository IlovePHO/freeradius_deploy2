<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SoftEtherSecureNatsTable extends Table {

    public function initialize(array $config){  
        $this->addBehavior('Timestamp');
        $this->belongsTo('SoftEtherVirtualHubs', [
            'foreignKey'    => 'hub_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('hub_id', 'create')
            ->notBlank('hub_id','Value is required')
            ->add('hub_id', [ 
                'unique' => [
                    'message' => 'The hub id you provided is already taken. Please provide another one.',
                    'rule' => 'validateUnique', 
                    'provider' => 'table'
                ]
            ]);

        $validator
            ->ip('ip_address')
            ->notEmpty('ip_address','If enabled is true, Value is required', function($context) {
                return $context['data']['enabled'];
            });

        $validator
            ->ip('subnet_mask')
            ->notEmpty('subnet_mask','If enabled is true, Value is required', function($context) {
                return $context['data']['enabled'];
            });

        $validator
            ->regex('mac_address', '/^([0-9a-fA-F]{2}-){5}[0-9a-fA-F]{2}$/', 'If mac_address is MAC address');

        $validator
            ->ip('dhcp_lease_ip_start')
            ->notEmpty('dhcp_lease_ip_start','If enabled is true and dhcp_enabled is true, Value is required', function($context) {
                return $context['data']['enabled'] && $context['data']['dhcp_enabled'];
            });

        $validator
            ->ip('dhcp_lease_ip_end')
            ->notEmpty('dhcp_lease_ip_end','If enabled is true and dhcp_enabled is true, Value is required', function($context) {
                return $context['data']['enabled'] && $context['data']['dhcp_enabled'];
            });

        $validator
            ->ip('dhcp_subnet_mask')
            ->notEmpty('dhcp_subnet_mask','If enabled is true and dhcp_enabled is true, Value is required', function($context) {
                return $context['data']['enabled'] && $context['data']['dhcp_enabled'];
            });

        $validator
            ->ip('dhcp_gateway_address')
            ->notEmpty('dhcp_gateway_address','If enabled is true and dhcp_enabled is true, Value is required', function($context) {
                return $context['data']['enabled'] && $context['data']['dhcp_enabled'];
            });

        return $validator;
    }

}
