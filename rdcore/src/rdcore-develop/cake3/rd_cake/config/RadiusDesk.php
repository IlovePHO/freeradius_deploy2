<?php

$config = [];

//The groups that is defined
$config['group']['admin']   = 'Administrators';     //Has all the rights
$config['group']['ap']      = 'Access Providers';   //Has selected right
$config['group']['user']    = 'Permanent Users';    //Has very limited rights
$config['group']['sm']      = 'Site Managers';      //Has selected right

$config['language']['default']      = '4_4';     //This is the id 4 of Languages and id 4 of countries (GB_en)

//Define the connection types and if they are active or not
$config['conn_type'][0]     = ['name' => __('Direct (Fixed IP)'),  'id' => 'direct',   'active' => true];
$config['conn_type'][1]     = ['name' => __('OpenVPN'),            'id' => 'openvpn',  'active' => false];
$config['conn_type'][2]     = ['name' => __('PPTP'),               'id' => 'pptp',     'active' => false];
$config['conn_type'][3]     = ['name' => __('Dynamic Client'),     'id' => 'dynamic',  'active' => false];

//Define the location of ccd (client config directory)
//FIXME This value does not get read by the OpenvpnClients Model - investigate
$config['openvpn']['ccd_dir_location']  = '/etc/openvpn/ccd/';
$config['openvpn']['ip_half']           = '10.8.';

//Define pptp specific settings
$config['pptp']['start_ip']                        = '10.20.30.2';
$config['pptp']['server_ip']                       = '10.20.30.1';
$config['pptp']['chap_secrets']                    = '/etc/ppp/chap-secrets';

//Define dynamic specific settings
$config['dynamic']['start_ip']                     = '10.120.0.1'; //Make this a Class B subnet (64000) which will never include a value also specified for a FIXED client

//===FR3===
$config['freeradius']['path_to_dictionary_files']   = '/usr/share/freeradius/';
$config['freeradius']['main_dictionary_file']       = '/etc/freeradius/3.0/dictionary';
$config['freeradius']['radclient']                  = '/usr/bin/radclient';


//Define the configured dynamic attributes
$config['dynamic_attributes'][0]     = ['name' => 'Called-Station-Id',  'id' => 'Called-Station-Id',   'active' => true];
$config['dynamic_attributes'][1]     = ['name' => 'Mikrotik-Realm',     'id' => 'Mikrotik-Realm',      'active' => true];
$config['dynamic_attributes'][2]     = ['name' => 'NAS-Identifier',     'id' => 'NAS-Identifier',      'active' => true];

//Define nas types
$config['nas_types'][0]     = ['name' => 'Other',                  'id' => 'other',                    'active' => true];
$config['nas_types'][1]     = ['name' => 'CoovaChilli',            'id' => 'CoovaChilli',              'active' => true];
$config['nas_types'][2]     = ['name' => 'CoovaChilli-Heartbeat',  'id' => 'CoovaChilli-Heartbeat',    'active' => true];
$config['nas_types'][3]     = ['name' => 'Mikrotik',               'id' => 'Mikrotik',                 'active' => true];
$config['nas_types'][4]     = ['name' => 'Mikrotik-Heartbeat',     'id' => 'Mikrotik-Heartbeat',       'active' => true];
$config['nas_types'][5]     = ['name' => 'Telkom',                 'id' => 'Telkom',                   'active' => true];



$config['paths']['wallpaper_location']  = "/rd/resources/images/wallpapers/";
$config['paths']['dynamic_photos']      = "/cake3/rd_cake/img/dynamic_photos/";
$config['paths']['dynamic_detail_icon'] = "/cake3/rd_cake/img/dynamic_details/";
$config['paths']['real_photo_path']     = "/cake3/rd_cake/webroot/img/dynamic_photos/";
$config['paths']['absolute_photo_path'] = "/var/www/html/cake3/rd_cake/webroot/img/dynamic_photos/";
$config['paths']['ap_logo_path']        = "/cake3/rd_cake/img/access_providers/";
$config['paths']['real_ap_logo_path']   = "/cake3/rd_cake/webroot/img/access_providers/";
$config['paths']['geo_data']            = '/var/www/html/cake3/rd_cake/setup/GeoIp/data/GeoLite2-City.mmdb';


//Define default settings for the users:
$config['user_settings']['wallpaper']                       = "9.jpg";
$config['user_settings']['map']['type']                     = "ROADMAP";
$config['user_settings']['map']['zoom']                     = 5;
$config['user_settings']['map']['lng']                      = 140.44921875000003;
$config['user_settings']['map']['lat']                      = 38.27268853598098;

//Define default settings for users's Dynamic Clients map
$config['user_settings']['dynamic_client_map']['type']      = "ROADMAP";
$config['user_settings']['dynamic_client_map']['zoom']      = 18;
$config['user_settings']['dynamic_client_map']['lng']       = -71.0955740216735;
$config['user_settings']['dynamic_client_map']['lat']       = 42.3379770178396;

//Set to true to allow  the user to remove their device out of the realm it has been assigned to
$config['UserCanRemoveDevice']                              = true;

//SMTP configs are defined in the Config/app.php file. Here we specify which one to use application wide
//$config['EmailServer']						                = 'default'; //e.g. 'gmail'
$config['EmailServer']						                = 'gmail'; //e.g. 'gmail'

//== 30/3/16 -> Some server wide configurations ==
$config['server_settings']['user_stats_cut_off_days']       = 90; //3 months (make zero to have no cut off)
$config['server_settings']['radacct_cut_off_days']          = 90; //3 months (make zero to have no cut off)

//== End server wide configurations ==

$config['webFont']      = 'FontAwesome';
$config['icnSignIn']    = 'xf090@'.$config['webFont'];
$config['icnDownload']  = 'xf0ed@'.$config['webFont'];
$config['icnUpload']    = 'xf0ee@'.$config['webFont'];
$config['icnSignal']    = 'xf012@'.$config['webFont'];
$config['icnLock']      = 'xf023@'.$config['webFont'];
$config['icnYes']       = 'xf00c@'.$config['webFont'];
$config['icnMenu']      = 'xf0c9@'.$config['webFont'];
$config['icnInfo']      = 'xf129@'.$config['webFont'];
$config['icnPower']     = 'xf011@'.$config['webFont'];
$config['icnSpanner']   = 'xf0ad@'.$config['webFont'];
$config['icnHome']      = 'xf015@'.$config['webFont'];
$config['icnDynamic']   = 'xf0d0@'.$config['webFont'];
$config['icnVoucher']   = 'xf145@'.$config['webFont'];
$config['icnReload']    = 'xf021@'.$config['webFont'];
$config['icnAdd']       = 'xf067@'.$config['webFont'];
$config['icnEdit']      = 'xf040@'.$config['webFont'];
$config['icnDelete']    = 'xf1f8@'.$config['webFont'];
$config['icnPdf']       = 'xf1c1@'.$config['webFont'];
$config['icnCsv']       = 'xf1c3@'.$config['webFont'];
$config['icnRadius']    = 'xf10c@'.$config['webFont'];
$config['icnLight']     = 'xf204@'.$config['webFont'];
$config['icnLightbulb'] = 'xf0eb@'.$config['webFont'];
$config['icnNote']      = 'xf08d@'.$config['webFont'];
$config['icnKey']       = 'xf084@'.$config['webFont'];
$config['icnRealm']     = 'xf17d@'.$config['webFont'];
$config['icnNas']       = 'xf1cb@'.$config['webFont'];
$config['icnTag']       = 'xf02b@'.$config['webFont'];
$config['icnProfile']   = 'xf1b3@'.$config['webFont'];
$config['icnComponent'] = 'xf12e@'.$config['webFont'];
$config['icnActivity']  = 'xf0e7@'.$config['webFont'];
$config['icnLog']       = 'xf044@'.$config['webFont'];
$config['icnTranslate'] = 'xf0ac@'.$config['webFont'];
$config['icnConfigure'] = 'xf0ad@'.$config['webFont'];
$config['icnUser']      = 'xf007@'.$config['webFont'];
$config['icnDevice']    = 'xf10a@'.$config['webFont'];
$config['icnMesh']      = 'xf20e@'.$config['webFont'];
$config['icnBug']       = 'xf188@'.$config['webFont'];
$config['icnMobile']    = 'xf10b@'.$config['webFont'];
$config['icnDesktop']   = 'xf108@'.$config['webFont'];
$config['icnView']      = 'xf002@'.$config['webFont'];
$config['icnMeta']      = 'xf0cb@'.$config['webFont'];
$config['icnMap']       = 'xf041@'.$config['webFont'];
$config['icnConnect']   = 'xf0c1@'.$config['webFont'];
$config['icnGraph']     = 'xf080@'.$config['webFont'];
$config['icnKick']      = 'xf1e6@'.$config['webFont'];
$config['icnClose']     = 'xf00d@'.$config['webFont'];
$config['icnFinance']   = 'xf09d@'.$config['webFont'];
$config['icnOnlineShop']= 'xf07a@'.$config['webFont'];
$config['icnEmail']     = 'xf0e0@'.$config['webFont'];
$config['icnAttach']    = 'xf0c6@'.$config['webFont'];
$config['icnCut']       = 'xf0c4@'.$config['webFont'];
$config['icnTopUp']     = 'xf0f4@'.$config['webFont'];
$config['icnSubtract']  = 'xf068@'.$config['webFont'];
$config['icnWatch']     = 'xf017@'.$config['webFont'];
$config['icnStar']      = 'xf005@'.$config['webFont'];
$config['icnGrid']      = 'xf00a@'.$config['webFont'];
$config['icnFacebook']	= 'xf09a@'.$config['webFont'];
$config['icnGoogle']	= 'xf1a0@'.$config['webFont'];
$config['icnTwitter']	= 'xf099@'.$config['webFont'];
$config['icnWifi']		= 'xf012@'.$config['webFont'];
$config['icnIP']		= 'xf1c0@'.$config['webFont'];
$config['icnThumbUp']   = 'xf087@'.$config['webFont'];
$config['icnThumbDown']	= 'xf088@'.$config['webFont'];
$config['icnCPU']		= 'xf085@'.$config['webFont'];
$config['icnCamera']    = 'xf030@'.$config['webFont'];
$config['icnRedirect']  = 'xf074@'.$config['webFont'];
$config['icnDynamicNas']= 'xf239@'.$config['webFont'];
$config['icnCloud']     = 'xf0c2@'.$config['webFont'];
$config['icnVPN']       = 'xf10e@'.$config['webFont'];
$config['icnAdmin']     = 'xf19d@'.$config['webFont'];
$config['icnRadius']    = 'xf140@'.$config['webFont'];
$config['icnBan']       = 'xf05e@'.$config['webFont'];
$config['icnData']      = 'xf1c0@'.$config['webFont'];
$config['icnGears']     = 'xf085@'.$config['webFont'];
$config['icnWizard']    = 'xf0d0@'.$config['webFont'];
$config['icnShield']    = 'xf132@'.$config['webFont'];
$config['icnList']      = 'xf03a@'.$config['webFont'];
$config['icnScale']     = 'xf24e@'.$config['webFont'];
$config['icnFilter']    = 'xf0b0@'.$config['webFont'];
$config['icnDropbox']   = 'xf16b@'.$config['webFont'];
$config['icnBell']      = 'xf0f3@'.$config['webFont'];
$config['icnCheckC']    = 'xf058@'.$config['webFont'];
$config['icnGroup']     = 'xf0c0@'.$config['webFont'];
$config['icnTree']      = 'xf1bb@'.$config['webFont'];
$config['icnQrcode']    = 'xf029@'.$config['webFont'];
$config['icnHdd']       = 'xf0a0@'.$config['webFont'];
$config['icnEyeSlash']  = 'xf070@'.$config['webFont'];
$config['icnQuestion']  = 'xf059@'.$config['webFont'];
$config['icnTaxi']      = 'xf1ba@'.$config['webFont'];
$config['icnDotCircleO']= 'xf192@'.$config['webFont'];
$config['icnGlobe']     = 'xf0ac@'.$config['webFont'];
$config['icnWifi2']     = 'xf1eb@'.$config['webFont'];
$config['icnHandshake'] = 'xf2b5@'.$config['webFont'];
$config['icnSkyatlas']  = 'xf216@'.$config['webFont'];
$config['icnCalendarO'] = 'xf133@'.$config['webFont'];
$config['icnFolder']    = 'xf07b@'.$config['webFont'];
$config['icnVPN2']      = 'xf23e@'.$config['webFont'];

//=== Dynamic RADIUS Clients 23/1/17 ===
/*--- This has to be the same value as the ons specified in--
sudo vi /etc/freeradius/sites-enabled/dynamic-clients
       FreeRADIUS-Client-Secret = "testing123"

TIP: Also remember to change the values in:
ApProfiles.php
MESHdesk.php
*/
$config['DynamicClients']['shared_secret'] = 'testing123';

//=== EXPERIMENTAL STUFF =====
//--Show experimental menus---
$config['experimental']['active']   = false;
$config['extensions']['active']     = true;

//=== White Label ====
$config['whitelabel']['active']     = true; //JUL 2021 Always make this true
$config['whitelabel']['hName']      = 'RADIUSdesk';
$config['whitelabel']['hBg']        = 'fff';
$config['whitelabel']['hFg']        = '005691';
$config['whitelabel']['imgActive']  = true;
$config['whitelabel']['imgFile']    = 'logo.png';
$config['whitelabel']['fName']      = 'RADIUSdesk';

//=== Language List =====
$config['Admin']['i18n'][0]     = [
    'id'        => '4_4',
    'country'   => 'United Kingdom',
    'language'  => 'English',
    'text'      =>	'United Kingdom -> English',
    'rtl'       => false,
    'icon_file' => '/cake3/rd_cake/img/flags/GB.png',
    'active'    => true
];
$config['Admin']['i18n'][1]     = [
    'id'        => '110_5',
    'country'   => '日本',
    'language'  => '日本語',
    'text'      =>	'日本 -> 日本語',
    'rtl'       => false,
    'icon_file' => '/cake3/rd_cake/img/flags/JP.png',
    'active'    => true
];

//=== HomeServerPools Type List =======
$config['HomeServerPools']['Types'] =[
    ['type'=>'fail-over'],
//    ['type'=>'load-balance'],
//    ['type'=>'client-balance'],
//    ['type'=>'client-port-balance'],
//    ['type'=>'keyed-balance'],
];

//=== idps Type List =======
$config['idps']['Types'] =[
    ['type'=>'google_workspace','name'=>'Google Workspace'],
// example
//  ['type'=>'microsoft365','name'=>'Microsoft 365'],
    ['type'=>'direct','name'=>'Direct input'],
];

// enable rolling token; enhanced security
$config['Dashboard']['enable_rolling_token'] = false;

// This value must be changed because it is used for AES encryption.
// Either rewrite it directly or set the AES_KEY environment
// variable in /etc/nginx/fastcgi.conf.
$config['AES']['key'] = env('AES_KEY', '0123456789ABCDEF0123456789ABCDEF');

// The following values are used to specify the file containing
// the HMAC key used to generate the password for authentication.
// Example: /etc/hmac/hmac_{hmac_key_suffix_variable}.key
$config['ExternalApi']['HmacKey'] = [
    'dir'    => '/etc/hmac/',
    'prefix' => 'hmac_',
    'ext'    => '.key',
];

$config['ExternalApi']['Radius'] = [
    'ca_cert_path' => '/etc/freeradius/certs/ca.pem',
    'server_cn'    => 'radius.example.org',
];

// The following values are used in the command call to sign the connection profile.
// Certificate-related paths should be changed as necessary.
$config['ExternalApi']['CmdPath'] = [
    'sudo'    => '/usr/bin/sudo',
    'openssl' => '/usr/bin/openssl',
    'xmlsec1' => '/usr/bin/xmlsec1',
    'sync_hmac' => '/usr/local/sbin/sync_hmac_key.sh',
];

$config['ExternalApi']['Mobileconfig'] = [
    'pl_uuid'           => '4d63afb9-0215-406e-9a47-0614cf3587c0',
    'plid_prefix'       => 'ExampleUniv.',
    'ca_cert_name'      => 'CA cert.',
    'description'       => 'Configure Wi-Fi for Example Univ.',
    'payload_display_name' => 'Wi-Fi Connection Profile',

    'signer_chain_path' => '/etc/certs/s3/signer_chain.pem',
    'signer_cert_path'  => '/etc/certs/s3/signer_cert.pem',
    'signer_key_path'   => '/etc/certs/s3/signer_privkey.pem',
];

// Value for connection profile for windows
$config['ExternalApi']['Windows'] = [
    // Carrier ID (Set a globally-unique UUID for the operator.)
    'carrier_id'           => 'e9178ac5-3117-42a5-bdef-13c085873bf2',
    // Subscriber ID
    'subscriver_id'        => '1234567890', // may be dummy
    // Author ID
    'author_id'            => '311',
    // SHA-1 hash of the Trusted Root CA certificate
    // ex. `openssl x509 -inform DER -in SCRoot2ca.cer -noout -fingerprint -sha1`
    //     SHA1 Fingerprint=5F:3B:8C:F2:F8:10:B3:7D:78:B4:CE:EC:19:19:C3:73:34:B9:C7:74
    'trusted_root_ca_hash' => '5F:3B:8C:F2:F8:10:B3:7D:78:B4:CE:EC:19:19:C3:73:34:B9:C7:74',
    // xmlsec1 command requires certificates packed in .pfx format.
    // All certificates will be embedded in the profile.
    // To create a .pfx file,
    //   cat cert.pem ca.pem | openssl pkcs12 -export -inkey privkey.pem -password "pass:$password" -out signer_cert.pfx
    'signer_cert_pfx_path' => '/etc/certs/s3/signer_cert.pfx',
    'pfx_password'         => 'password',
    // Additional (I)CA certificates to embed. (optional)
    //'ca_file'              => '/etc/certs/s3/signer_chain.pem',
];

return $config;

?>
