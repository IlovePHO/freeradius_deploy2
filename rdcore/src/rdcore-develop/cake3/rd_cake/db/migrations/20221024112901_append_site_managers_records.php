<?php

use Phinx\Migration\AbstractMigration;

class AppendSiteManagersRecords extends AbstractMigration
{
    public function up()
    {
        $group_name = "Site Managers";

        $keys_with_flags_table = [
            'AcosRights' => [
                'indexSm'				=> false,
            ],
            'EncodingSchemes' => [
                'index'					=> true,
            ],
            'PermanentUsers' => [
                'index'					=> true,
                'add'					=> true,
                'delete'				=> true,
                'viewBasicInfo'			=> true,
                'editBasicInfo'			=> true,
                'viewPersonalInfo'		=> true,
                'editPersonalInfo'		=> true,
                'privateAttrIndex'		=> true,
                'privateAttrAdd'		=> true,
                'privateAttrEdit'		=> true,
                'privateAttrDelete'		=> true,
                'changePassword'		=> false, // Not functional due to HMAC use
                'enableDisable'			=> true,
                'exportCsv'				=> true,
                'noteIndex'				=> true,
                'restrictListOfDevices'	=> true,
                # 'edit_tracking'		=> true, // missing method
                # 'view_tracking'		=> true, // missing method
                'noteAdd'				=> true,
                'noteDel'				=> true,
                'autoMacOnOff'			=> true,
                'viewPassword'			=> true,
            ],
            'Vouchers' => [
                'index'					=> true,
                'add'					=> true,
                'delete'				=> true,
                'viewBasicInfo'			=> true,
                'editBasicInfo'			=> true,
                'privateAttrIndex'		=> true,
                'privateAttrAdd'		=> true,
                'privateAttrEdit'		=> true,
                'privateAttrDelete'		=> true,
                'changePassword'		=> true,
                'exportCsv'				=> true,
                'exportPdf'				=> true,
                # 'delete_accounting_data'	=> true, // missing method
                'emailVoucherDetails'	=> true,
                'bulkDelete'			=> true,
            ],
            'Profiles' => [
                'index'					=> true,
                'indexAp'				=> true,
                'add'					=> true,
                'manageComponents'		=> true,
                'delete'				=> true,
                'noteIndex'				=> true,
                'noteAdd'				=> true,
                'noteDel'				=> true,
                'edit'					=> true,
                'simpleView'			=> true,
                'simpleAdd'				=> true,
                'simpleEdit'			=> true,
            ],
            'ProfileComponents' => [
                'add'					=> true,
                'delete'				=> true,
                'edit'					=> true,
                'exportCsv'				=> true,
                'index'					=> true,
                'noteAdd'				=> true,
                'noteDel'				=> true,
                'noteIndex'				=> true,
            ],
            'GoogleWsLinks' => [
                'callback'				=> true,
                'getOrgunit'			=> true,
                'getUser'				=> true,
                'getUserinfoMe'			=> true,
                'listOrgunitMembers'	=> true,
                'listOrgunits'			=> true,
                'listUsers'				=> true,
                'prepare'				=> true,
                'synchronize'			=> true,
            ],
            'SubGroups' => [
                'add'					=> true,
                'delete'				=> true,
                'edit'					=> true,
                'index'					=> true,
                'view'					=> true,
            ],
            'Idps' => [
                'add'					=> true,
                'delete'				=> true,
                'edit'					=> true,
                'index'					=> true,
                'view'					=> true,
            ],
            'IdpOauthClientCredentials' => [
                'add'					=> true,
                'delete'				=> true,
                'edit'					=> true,
                'index'					=> true,
            ],
            'IdpOauthClientTokens' => [
                'add'					=> true,
                'delete'				=> true,
                'edit'					=> true,
                'index'					=> true,
            ],
            'ExternalApi' => [
                'createPermanentUser'	=> true,
                'createVoucher'			=> true,
                'deletePermanentUser'	=> true,
                'deleteVoucher'			=> true,
                'getConnectionProfile'	=> true,
                'getDevices'			=> true,
                'getDeviceToken'		=> true,
                'getPermanentUsers'		=> true,
                'getUsage'				=> true,
                'getVouchers'			=> true,
                'updateConnectionProfile'	=> true,
            ],
            'ExternalApiKeys' => [
                'add'					=> true,
                'delete'				=> true,
                'edit'					=> true,
                'index'					=> true,
            ],
            'StaConfigs' => [
                'add'					=> true,
                'delete'				=> true,
                'edit'					=> true,
                'editRealms'			=> true,
                'editSubGroups'			=> true,
                'getEapMethodList'		=> true,
                'index'					=> true,
            ],
            'StaInfos' => [
                'delete'				=> true,
                'index'					=> true,
            ],
        ];

        $this->appendAccessControllObject($group_name, $keys_with_flags_table);
    }

    public function down()
    {
        // At the time of rollback, nothing is done.
    }

    private function appendAccessControllObject($group_name,
                                                $keys_with_flags_table,
                                                $default_rights = true)
    {
        // Get the aro_id of the group.
        $aro_id = $this->getGroupAroId($group_name);
        if (is_null($aro_id)) {
            return;
        }

        // Get the "Access Providers" aco.
        // Note: The authorization items for Site Managers are adapted
        //       from those of Access Providers.
        $access_providers_aco_row = $this->getAcoRow('Access Providers', null);
        if ($access_providers_aco_row === false) {
            return;
        }

        // Get the aco_id, lft, and rght of "Access Providers".
        $access_providers_aco_id   = intval($access_providers_aco_row['id']);
        $access_providers_aco_lft  = intval($access_providers_aco_row['lft']);
        $access_providers_aco_rght = intval($access_providers_aco_row['rght']);
        #var_dump($access_providers_aco_id);
        #var_dump($access_providers_aco_rght);

        // Get the "Access Providers/Controllers" aco.
        $controllers_aco_row = $this->getAcoRow('Controllers', $access_providers_aco_id);
        if ($controllers_aco_row === false) {
            return;
        }

        // Get the aco_id, lft, and rght of "Access Providers/Controllers".
        $controllers_aco_id   = intval($controllers_aco_row['id']);
        $controllers_aco_lft  = intval($controllers_aco_row['lft']);
        $controllers_aco_rght = intval($controllers_aco_row['rght']);
        #var_dump($controllers_aco_id);
        #var_dump($controllers_aco_rght);

        // Isolate the item by greatly increasing the value of aco
        // that is larger than "rght" of "Access Providers/Controllers".
        $evacuate_num = $this->generateEvacuateNum();
        $shift_count_threshold = $controllers_aco_rght;
        $this->evacuateAcoCount($shift_count_threshold, $evacuate_num);

        $appended_acos_num = 0;
        foreach ($keys_with_flags_table as $class => $methods) {
            // Check class acos record
            $class_aco_row = $this->getAcoRow($class, $controllers_aco_id);
            if ($class_aco_row === false) {
                // Insert class acos record
                $lft = $controllers_aco_rght + ($appended_acos_num * 2);
                $this->insertAco($class, $controllers_aco_id, $lft);
                $class_aco_row = $this->getAcoRow($class, $controllers_aco_id);
                $appended_acos_num++;
            }
            #var_dump($class);
            #var_dump($class_aco_id);

            $class_aco_id   = $class_aco_row['id'];
            $class_aco_lft  = $class_aco_row['lft'];
            $class_aco_rght = $class_aco_row['rght'];

            $appended_acos_method_num = 0;
            foreach ($methods as $method => $right) {
                // Check method acos record
                $method_aco_id = $this->getAcoId($method, $class_aco_id);
                if (is_null($method_aco_id)) {
                    // Insert method acos record
                    #$lft = $controllers_aco_rght + 1 + ($appended_acos_num * 2);
                    $lft = $class_aco_rght + ($appended_acos_method_num * 2);
                    $this->insertAco($method, $class_aco_id, $lft);
                    $method_aco_id = $this->getAcoId($method, $class_aco_id);
                    $appended_acos_num++;
                    $appended_acos_method_num++;
                }
                #var_dump($method);
                #var_dump($method_aco_id);

                // Check method aros_acos record
                $aro_aco_id = $this->getAroAcoId($aro_id, $method_aco_id);
                #var_dump($aro_aco_id);
                if (is_null($aro_aco_id)) {
                    // insert method aros_acos record
                    if ($right) {
                        $this->insertAroAco($aro_id, $method_aco_id, $default_rights);
                    } else {
                        $this->insertAroAco($aro_id, $method_aco_id, false);
                    }
                } else {
                    if ($right) {
                        $this->updateAroAco($aro_aco_id, $default_rights);
                    } else {
                        $this->updateAroAco($aro_aco_id, false);
                    }
                }
            }

            // Increase the value of "rght" of the class according
            // to the number of methods added.
            if ($appended_acos_method_num > 0) {
                $class_aco_rght += $appended_acos_method_num * 2;
                $this->updateAco($class_aco_id, $class_aco_lft, $class_aco_rght);
            }
        }

        if ($appended_acos_num > 0) {
            // Increase the value of "rght" of "Access Providers/Controllers"
            // according to the number of items added.
            $controllers_aco_rght += $appended_acos_num * 2;
            $this->updateAco($controllers_aco_id, $controllers_aco_lft,
                             $controllers_aco_rght);

            // Increase the value of "rght" of "Access Providers"
            // according to the number of items added.
            $access_providers_aco_rght += $appended_acos_num * 2;
            $this->updateAco($access_providers_aco_id, $access_providers_aco_lft,
                             $access_providers_aco_rght);
        }

        // The "lft" and "rght" values of the isolated items are rounded back
        // to the number of items added.
        $this->restoreAcoCount($appended_acos_num * 2, $evacuate_num);
    }

    private function getGroupAroId($group_name)
    {
        $sql = sprintf('SELECT id FROM groups WHERE name = "%s"', $group_name);
        $group = $this->fetchRow($sql);
        if ($group === false) {
            return null;
        }

        $group_id = intval($group['id']);
        $sql = sprintf('SELECT id FROM aros WHERE parent_id IS NULL AND model = "Groups" AND foreign_key = %d',
                       $group_id);

        $row = $this->fetchRow($sql);
        if ($row !== false) {
            return intval($row['id']);
        }
        return null;
    }

    private function getAcoRow($alias, $parent_id = null)
    {
        $sql = sprintf('SELECT * FROM acos WHERE alias = "%s"', $alias);
        if (is_null($parent_id)) {
            $sql .= ' AND parent_id IS NULL';
        } else {
            $sql .= sprintf(' AND parent_id = %d', $parent_id);
        }
        return $this->fetchRow($sql);
    }

    private function getAcoId($alias, $parent_id = null)
    {
        $row = $this->getAcoRow($alias, $parent_id);
        if ($row !== false) {
            return intval($row['id']);
        }
        return null;
    }

    private function getAcoLft($alias, $parent_id = null)
    {
        $row = $this->getAcoRow($alias, $parent_id);
        if ($row !== false) {
            return intval($row['lft']);
        }
        return null;
    }

    private function getAcoRght($alias, $parent_id = null)
    {
        $row = $this->getAcoRow($alias, $parent_id);
        if ($row !== false) {
            return intval($row['rght']);
        }
        return null;
    }

    private function getAroAcoId($aro_id, $aco_id)
    {
        $sql = sprintf('SELECT id FROM aros_acos WHERE aro_id = %d AND aco_id = %d',
                       $aro_id, $aco_id);

        $row = $this->fetchRow($sql);
        if ($row !== false) {
            return intval($row['id']);
        }
        return null;
    }

    private function insertAco($alias, $parent_id, $lft = null, $rght = null)
    {
        if (isset($lft) && is_null($rght)) {
            $rght = $lft + 1;
        }

        $table = $this->table('acos');
        $row = [
            'parent_id' => $parent_id,
            'alias'     => $alias,
            'lft'       => $lft,
            'rght'      => $rght,
        ];
        $table->insert($row);
        $table->saveData();
    }

    private function updateAco($id, $lft, $rght)
    {
        $sql = 'UPDATE acos SET';
        if (is_null($lft)) {
            $sql .= ' lft IS NULL';
        } else {
            $sql .= sprintf(' lft = %d', $lft);
        }
        if (is_null($rght)) {
            $sql .= ', rght IS NULL';
        } else {
            $sql .= sprintf(', rght = %d', $rght);
        }
        $sql .= sprintf(' WHERE id = %d', $id);
        $this->execute($sql);
    }

    private function insertAroAco($aro_id, $aco_id, $enable = true)
    {
        $table = $this->table('aros_acos');
        $row = [
            'aro_id'  => $aro_id,
            'aco_id'  => $aco_id,
            '_create' => ($enable == true ? 1 : -1),
            '_read'   => ($enable == true ? 1 : -1),
            '_update' => ($enable == true ? 1 : -1),
            '_delete' => ($enable == true ? 1 : -1),
        ];
        $table->insert($row);
        $table->saveData();
    }

    private function updateAroAco($id, $enable = true)
    {
        $sql  = 'UPDATE aros_acos SET';
        $sql .= sprintf('  _create = %d', ($enable == true ? 1 : -1));
        $sql .= sprintf(', _read   = %d', ($enable == true ? 1 : -1));
        $sql .= sprintf(', _update = %d', ($enable == true ? 1 : -1));
        $sql .= sprintf(', _delete = %d', ($enable == true ? 1 : -1));
        $sql .= sprintf(' WHERE id = %d', $id);
        $this->execute($sql);
    }

    private function getAcosRghtMax()
    {
        $sql = 'SELECT rght FROM acos ORDER BY rght DESC LIMIT 1';
        $row = $this->fetchRow($sql);
        if ($row === false) {
            return 0;
        } else {
            return intval($row['rght']);
        }
    }

    private function generateEvacuateNum()
    {
        $rght_max = $this->getAcosRghtMax();
        return 10 ** (strlen($rght_max) + 1);
    }

    private function evacuateAcoCount($threshold, $evacuate_num = 10000)
    {
        $sql = sprintf('UPDATE acos SET lft = lft + %d WHERE lft >= %d',
                       $evacuate_num, $threshold);
        $this->execute($sql);

        $sql = sprintf('UPDATE acos SET rght = rght + %d WHERE rght >= %d',
                       $evacuate_num, $threshold);
        $this->execute($sql);
    }

    private function restoreAcoCount($count, $evacuate_num = 10000)
    {
        $diff = $evacuate_num - $count;

        $sql = sprintf('UPDATE acos SET lft = lft - %d WHERE lft > %d',
                       $diff, $evacuate_num);
        $this->execute($sql);

        $sql = sprintf('UPDATE acos SET rght = rght - %d WHERE rght > %d',
                       $diff, $evacuate_num);
        $this->execute($sql);
    }
}
