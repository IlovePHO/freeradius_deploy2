<?php 

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SoftEtherNetworkBridge extends Entity{
   
    protected function _getIfName(){
        return array_map(function($ifEntity) {
            return $ifEntity->if_name;
        }, $this->_properties['soft_ether_interfaces']);
    }
      
}
