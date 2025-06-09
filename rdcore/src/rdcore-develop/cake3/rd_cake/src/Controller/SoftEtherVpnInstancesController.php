<?php

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use MethodNotAllowedException;

class SoftEtherVpnInstancesController extends AppController {

    public $main_model       = 'SoftEtherVpnInstances';
    public $base    = "Access Providers/Controllers/SoftEtherVpnInstances/";

//------------------------------------------------------------------------
// public method

    public function initialize() {
        parent::initialize();
        $this->loadModel($this->main_model);
        // $this->loadModel('Proxies');
        $this->loadComponent('Aa');
        $this->loadComponent('GridButtons');
        $this->loadComponent('CommonQuery', [ //Very important to specify the Model
            'model' => $this->main_model,
            'sort_by' => $this->main_model . '.id'
        ]);
        $this->loadComponent('JsonErrors');
        $this->loadComponent('TimeCalculations');

        // $this->loadComponent('LifeSeed');
    }
    
    public function index(){
        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $query      = $this->{$this->main_model}->find();
        $this->CommonQuery->build_common_query($query, $user, array()); //AP QUERY is sort of different in a way

        $limit = 50;   //Defaults
        $page = 1;
        $offset = 0;
        if (isset($this->request->query['limit'])) {
            $limit = $this->request->query['limit'];
            $page = $this->request->query['page'];
            $offset = $this->request->query['start'];
        }

        $query->page($page);
        $query->limit($limit);
        $query->offset($offset);

        $total = $query->count();
        $q_r = $query->all();

        $items = array();

        foreach ($q_r as $i) {
            array_push($items,array(
                'id'		=> $i['id'], 
                'ip_address'	=> $i['ip_address'],
                'admin_name'	=> $i['admin_name'], 
                'password'	=> $i['password'], 
                'config_hash_value'	=> $i['config_hash_value'], 
            ));
        }
       
        $this->set(array(
            'items' => $items,
            'success' => true,
            'totalCount' => $total,
            '_serialize' => array('items','success','totalCount')
        ));
    }
 
    public function add(){
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $entity = $this->{$this->main_model}->newEntity($this->request->data());

        if ($this->{$this->main_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not add item');
            $this->JsonErrors->entityErros($entity,$message);
        }
    }
 
    public function edit(){
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $entity = $this->{$this->main_model}->get($this->request->data('id'));
        $this->{$this->main_model}->patchEntity($entity, $this->request->data());

        if ($this->{$this->main_model}->save($entity)) {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        } else {
            $message = __('Could not update item');
            $this->JsonErrors->entityErros($entity,$message);
        }
    }

    public function delete() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $fail_flag = false;

	if(isset($this->request->data['id'])) {   //Single item delete
            $message = "Single item ".$this->request->data('id');

            $entity     = $this->{$this->main_model}->get($this->request->data('id'));   
            $this->{$this->main_model}->delete($entity);
        } else {                          //Assume multiple item delete
            foreach($this->request->data as $d) {
                $entity     = $this->{$this->main_model}->get($d['id']);  
                $this->{$this->main_model}->delete($entity);
            }
        }

        if($fail_flag == true) {
            $this->set(array(
                'success'   => false,
                'message'   => array('message' => __('Could not delete some items')),
                '_serialize' => array('success','message')
            ));
        } else {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        }
    }

    public function synchronize() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $user = $this->_ap_right_check();
        if(!$user){
            return;
        }

        $success_flag = true;

	if(isset($this->request->data['id'])) {   //Single item delete
            $message = "Single item ".$this->request->data('id');

            $entity     = $this->{$this->main_model}->get($this->request->data('id'));   
            $success_flag = $this->_synchronizeVpnConfig($entity->ip_address);
            $config_hash_value = $this->_createConfigHashValue($entity->ip_address);
            if (empty($config_hash_value) && $success_flag == true) {
                $success_flag = false;
            } elseif ($success_flag) {
                $entity->config_hash_value = $config_hash_value;
                $this->{$this->main_model}->save($entity);
            }
        } else {                          //Assume multiple item delete
            foreach($this->request->data as $d) {
                $entity     = $this->{$this->main_model}->get($d['id']);  
                $success_flag = $this->_synchronizeVpnConfig($entity->ip_address);
                $config_hash_value = $this->_createConfigHashValue($entity->ip_address);
                if (empty($config_hash_value) && $success_flag == true) {
                    $success_flag = false;
                } elseif ($success_flag) {
                    $entity->config_hash_value = $config_hash_value;
                    $this->{$this->main_model}->save($entity);
                }
            }
        }

        if($success_flag == false) {
            $this->set(array(
                'success'   => false,
                'message'   => array('message' => __('Could not synchronize some items')),
                '_serialize' => array('success','message')
            ));
        } else {
            $this->set(array(
                'success' => true,
                '_serialize' => array('success')
            ));
        }
    }

    // GUI panel menu
    public function menuForGrid(){
        $user = $this->Aa->user_for_token($this);
        if (!$user) {   //If not a valid user
            return;
        }

        $menu = $this->GridButtons->returnButtons($user, false, 'SoftEtherVpnInstances');

        $this->set(array(
            'items' => $menu,
            'success' => true,
            '_serialize' => array('items', 'success')
        ));
    }

//------------------------------------------------------------------------
// private method
    
    private function _synchronizeVpnConfig($vpn_instance_ip_address) {
        // load models
        $this->loadModel('SoftEtherVirtualHubs');
        $this->loadModel('SoftEtherUsers');
        $this->loadModel('SoftEtherSecureNats');
        $this->loadModel('SoftEtherLocalBridges');
        $this->loadModel('SoftEtherL2tpIpsec');
        $this->loadModel('SoftEtherWireguardConfigs');
        $this->loadModel('SoftEtherWireguardPublicKeys');
        $this->loadModel('SoftEtherNetworkBridges');
        $this->loadModel('SoftEtherInterfaces');

        // curl settings
        Configure::load('SoftEther');
        $se_url_start = Configure::read('se_url_start');
        $se_url_end = Configure::read('se_url_end');
        $se_admin_password = Configure::read('se_admin_password');

        $url = $se_url_start . $vpn_instance_ip_address . $se_url_end;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
                array("Content-type: application/json", "X-VPNADMIN-PASSWORD:" . $se_admin_password));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        // VPN setting
        $result = true;
        try {
            $this->_setVirtualHubConfigs($curl);
            $this->_setLocalBridges($curl);
            $this->_setL2tpIpsec($curl);
            $this->_setWireguard($curl);
            $this->_setNetworkBridgeConfigs($curl);
        } catch (JsonRpcException $e) {
            $result = false;
        }

        curl_close($curl);

        return $result;
    }
    
    private function _setVirtualHubConfigs($curl) {
        $data = $this->_createJsonRpcRequest('1', 'EnumHub', (object)[]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));

        foreach ($json_response->result->HubList as $virtual_hub) {
            $delete_hub_params = array(
                'HubName_str'		=> $virtual_hub->HubName_str
            );
    
            $data = $this->_createJsonRpcRequest('1', 'DeleteHub', $delete_hub_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        }

        $seVirtualHubs = $this->SoftEtherVirtualHubs->find()->contain(['SoftEtherUsers', 'SoftEtherSecureNats'])->all();

        foreach ($seVirtualHubs as $se_virtual_hub) {
            $create_hub_params = array(
                'HubName_str'		=> $se_virtual_hub->hub_name,
            );
    
            $data = $this->_createJsonRpcRequest('1', 'CreateHub', $create_hub_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));

            $set_hub_params = array(
                'HubName_str'		=> $se_virtual_hub->hub_name,
                'AdminPasswordPlainText_str'    => $se_virtual_hub->password,
                'DefaultGateway_u32'	=> $this->_ip4tou32($se_virtual_hub->default_gateway),
                'DefaultSubnet_u32'	=> $this->_ip4tou32($se_virtual_hub->default_subnet),
                'Online_bool'		=> $se_virtual_hub->online
            );
    
            $data = $this->_createJsonRpcRequest('1', 'SetHub', $set_hub_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));

            $this->_setUsers($curl, $se_virtual_hub->hub_name, $se_virtual_hub->soft_ether_users);
            $this->_setSecureNat($curl, $se_virtual_hub->hub_name, $se_virtual_hub->soft_ether_secure_nat);
        }
    }
    
    private function _setUsers($curl, $hub_name, $users) {
        foreach ($users as $user) {
            $create_user_params = array(
                'HubName_str'		=> $hub_name,
                'Name_str'		=> $user->user_name,
                'Realname_utf'		=> $user->real_name,
                'Note_utf'		=> $user->note,
                'AuthType_u32'		=> 1,
                'Auth_Password_str'	=> $user->auth_password,
            );
    
            $data = $this->_createJsonRpcRequest('1', 'CreateUser', $create_user_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        }
    }
    
    private function _setSecureNat($curl, $hub_name, $secure_nat) {
        if ($secure_nat->enabled) {
            $data = $this->_createJsonRpcRequest('1', 'EnableSecureNAT', array('HubName_str' => $hub_name));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        } else {
            $data = $this->_createJsonRpcRequest('1', 'DisableSecureNAT', array('HubName_str' => $hub_name));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
            return ;
        }

        $mac_address = "";
        if (empty($secure_nat->mac_address)) {
            for ($i = 0; $i < 10; $i++) {
                    $mac_address .= dechex(rand(0, 15));
            }
            $mac_address = dechex(rand(0, 15)) . '2' . $mac_address;
        } else {
            $mac_address = str_replace('-', '', $secure_nat->mac_address);
        }

        $set_secure_nat_option_params = array(
            'RpcHubName_str'        => $hub_name,
            'Ip_ip'                 => $secure_nat->ip_address,
            'Mask_ip'               => $secure_nat->subnet_mask,
            'MacAddress_bin'     => base64_encode(hex2bin($mac_address)),
            'UseDhcp_bool'  => $secure_nat->dhcp_enabled,
            'DhcpLeaseIPStart_ip'   => $secure_nat->dhcp_lease_ip_start,
            'DhcpLeaseIPEnd_ip'     => $secure_nat->dhcp_lease_ip_end,
            'DhcpSubnetMask_ip'     => $secure_nat->dhcp_subnet_mask,
            'DhcpExpireTimeSpan_u32'        => $secure_nat->dhcp_expire,
            'DhcpGatewayAddress_ip' => $secure_nat->dhcp_gateway_address,
            'DhcpDnsServerAddress_ip'       => $secure_nat->dhcp_dns_server_address1,
            'DhcpDnsServerAddress2_ip'      => $secure_nat->dhcp_dns_server_address2,
            'UseNat_bool'   => $secure_nat->nat_enabled,
        );

        $data = $this->_createJsonRpcRequest('1', 'SetSecureNATOption', $set_secure_nat_option_params);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
    }
    
    private function _setLocalBridges($curl) {
        $data = $this->_createJsonRpcRequest('1', 'EnumLocalBridge', (object)[]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));

        foreach ($json_response->result->LocalBridgeList as $local_bridge) {
            $delete_local_bridge_params = array(
                'DeviceName_str'	=> $local_bridge->DeviceName_str,
                'HubNameLB_str'		=> $local_bridge->HubNameLB_str,
            );
    
            $data = $this->_createJsonRpcRequest('1', 'DeleteLocalBridge', $delete_local_bridge_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        }

        $seLocalBridges = $this->SoftEtherLocalBridges->find()->all();

        foreach ($seLocalBridges as $se_local_bridge) {
            $add_local_bridge_params = array(
                'DeviceName_str'	=> $se_local_bridge->device_name,
                'HubNameLB_str'		=> $se_local_bridge->hub_name,
                'TapMode_bool'		=> $se_local_bridge->tap_mode
            );
    
            $data = $this->_createJsonRpcRequest('1', 'AddLocalBridge', $add_local_bridge_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        }
    }
    
    private function _setL2tpIpsec($curl) {
        $seL2tpIpsec = $this->SoftEtherL2tpIpsec->find()->first();
        $set_ipsec_services_params = array(
            'L2TP_Raw_bool'         => false,
            'L2TP_IPsec_bool'       => $seL2tpIpsec->l2tp_ipsec_enabled,
            'EtherIP_IPsec_bool'    => false,
            'IPsec_Secret_str'      => $seL2tpIpsec->ipsec_secret,
            'L2TP_DefaultHub_str'   => $seL2tpIpsec->l2tp_defaulthub
        );

        $data = $this->_createJsonRpcRequest('1', 'SetIPsecServices', $set_ipsec_services_params);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); 
    }
    
    private function _setWireguard($curl) {
        $this->_setWireguardConfigs($curl);
        $this->_setWireguardPublicKeys($curl);
    }
    
    private function _setWireguardConfigs($curl) {
        $seWireguardConfigs = $this->SoftEtherWireguardConfigs->find()->first();
        $set_proto_options_params = array(
            'Protocol_str'          => 'WireGuard',
            'Name_str'      => array('Enabled', 'PresharedKey', 'PrivateKey'),
            'Type_u32'      => array(2, 1, 1),
            'Value_bin'     => array(
                    base64_encode($seWireguardConfigs->enabled),
                    base64_encode($seWireguardConfigs->preshared_key),
                    base64_encode($seWireguardConfigs->private_key)
            )
        );

        $data = $this->_createJsonRpcRequest('1', 'SetProtoOptions', $set_proto_options_params);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); 
    }
    
    private function _setWireguardPublicKeys($curl) {
        $data = $this->_createJsonRpcRequest('1', 'EnumWgk', (object)[]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        $wireguard_public_key_list = isset($json_response->result->Key_str) ? $json_response->result->Key_str : [];

        foreach ($wireguard_public_key_list as $wireguard_public_key) {
            $delete_wgk_params = array(
                'Key_str'	=> $wireguard_public_key,
            );
    
            $data = $this->_createJsonRpcRequest('1', 'DeleteWgk', $delete_wgk_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        }

        $seWireguardPublicKeys = $this->SoftEtherWireguardPublicKeys->find()->all();

        foreach ($seWireguardPublicKeys as $se_wireguard_public_key) {
            $add_wgk_params = array(
                'Hub_str'	=> $se_wireguard_public_key->hub_name,
                'Key_str'	=> $se_wireguard_public_key->public_key,
                'User_str'	=> $se_wireguard_public_key->user_name
            );
    
            $data = $this->_createJsonRpcRequest('1', 'AddWgk', $add_wgk_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        }
    }
    
    private function _setNetworkBridgeConfigs($curl) {
        $data = $this->_createJsonRpcRequest('1', 'EnumBridge', (object)[]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        $network_bridge_list = json_decode(base64_decode($json_response->result->CommandResult_bin));

        foreach ($network_bridge_list as $network_bridge) {
            if (!isset($network_bridge->ifname))
                continue;

            $delete_bridge_params = array(
                'BridgeName_str'	=> $network_bridge->ifname,
            );
    
            $data = $this->_createJsonRpcRequest('1', 'DeleteBridge', $delete_bridge_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        }

        $seNetworkBridges = $this->SoftEtherNetworkBridges->find()->contain(['SoftEtherInterfaces'])->all();

        foreach ($seNetworkBridges as $se_network_bridge) {
            $create_bridge_params = array(
                'BridgeName_str'	=> $se_network_bridge->bridge_name,
            );
            $data = $this->_createJsonRpcRequest('1', 'CreateBridge', $create_bridge_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));

            if ($se_network_bridge->status) {
                $set_up_bridge_params = array(
                    'BridgeName_str'	=> $se_network_bridge->bridge_name,
                );
                $data = $this->_createJsonRpcRequest('1', 'SetUpBridge', $set_up_bridge_params);
            } else {
                $set_down_bridge_params = array(
                    'BridgeName_str'	=> $se_network_bridge->bridge_name,
                );
                $data = $this->_createJsonRpcRequest('1', 'SetDownBridge', $set_down_bridge_params);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));

            $add_bridge_address = array(
                'BridgeName_str'	=> $se_network_bridge->bridge_name,
                'BridgeIpAddress_str'	=> $se_network_bridge->ip_address,
                'BridgeSubnetMask_str'	=> $se_network_bridge->subnet_mask,
            );
            $data = $this->_createJsonRpcRequest('1', 'AddBridgeAddress', $add_bridge_address);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));

            $this->_setInterfaces($curl, $se_network_bridge->bridge_name, $se_network_bridge->soft_ether_interfaces);
        }
    }
    
    private function _setInterfaces($curl, $bridge_name, $interfaces) {
        foreach ($interfaces as $if) {
            $add_interface_params = array(
                'BridgeName_str'	=> $bridge_name,
                'InterfaceName_str'	=> $if->if_name,
            );
    
            $data = $this->_createJsonRpcRequest('1', 'AddInterface', $add_interface_params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            if ($if->tap_mode)
                    sleep(3);
    
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
        }
    }
    
    private function _createJsonRpcRequest($id, $method_name, $params) {
        return json_encode( array( 
                'jsonrpc'       => '2.0',
                'id'            => $id,
                'method'        => $method_name,
                'params'        => $params
        ) );
    }
    
    private function _checkJsonRpc($json_response) {
        if (isset($json_response->error)) {
            throw new JsonRpcException('Json Rpc ' . $json_response->error->message);
        } elseif (isset($json_response->result->ExitStatus_u32) && $json_response->result->ExitStatus_u32 != 0) {
            throw new JsonRpcException('Json Rpc Error: ip command has an error.');
        }

        return $json_response;
    }

    private function _htonl($hl) {
        $arr = unpack('N', pack('I', $hl));
        return $arr[1];
    }
    
    private function _ip4tou32($ip4) {
        $hl = ip2long($ip4);
        $nl = $this->_htonl($hl);
        return $nl;
    }
    
    private function _u32toip4($u32) {
        $hl = $this->_htonl($u32);
        $ip = long2ip($hl);
        return $ip;
    }
    
    private function _createConfigHashValue($vpn_instance_ip_address) {
        // curl settings
        Configure::load('SoftEther');
        $se_url_start = Configure::read('se_url_start');
        $se_url_end = Configure::read('se_url_end');
        $se_admin_password = Configure::read('se_admin_password');

        $url = $se_url_start . $vpn_instance_ip_address . $se_url_end;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
                array("Content-type: application/json", "X-VPNADMIN-PASSWORD:" . $se_admin_password));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        // VPN setting
        $result = "";
        try {
            $data = $this->_createJsonRpcRequest('1', 'Flush', (object)[]);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));

            $data = $this->_createJsonRpcRequest('1', 'GetConfig', (object)[]);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $json_response = $this->_checkJsonRpc(json_decode(curl_exec($curl)));
            $commonVPNConfig = base64_decode( $json_response->result->FileData_bin );
            $patterns = array('uint ConfigRevision', 'uint64 LastCommTime', 'uint64 BroadcastBytes', 'uint64 BroadcastCount', 'uint64 UnicastBytes', 'uint64 UnicastCount', 'byte ServerCert', 'byte ServerKey', 'CreatedTime', 'UpdatedTime', 'LastLoginTime', 'byte Key', 'string TapMacAddress', 'string VirtualHostMacAddress', 'byte HashedPassword');
            foreach ($patterns as $pattern) {
                    $pattern = '/[ \t]*' . $pattern . ' (.*)\r\n/';
                    $commonVPNConfig = preg_replace($pattern, '', $commonVPNConfig);
            }
            $result = hash('sha256', $commonVPNConfig, false);
        } catch (JsonRpcException $e) {
            $result = "";
        }

        curl_close($curl);

        return $result;
    }

}

class JsonRpcException extends Exception {}
