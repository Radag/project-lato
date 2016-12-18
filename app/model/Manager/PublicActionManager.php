<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;

/**
 * Description of PublicActionManager
 *
 * @author Radaq
 */
class PublicActionManager extends BaseManager 
{
    const ACTION_ADD_TO_GROUP = 1;
    const ACTION_MAIL_VALIDATION = 2;
    const ACTION_LOST_PASS = 3;
    const ACTION_NEW_PASS = 4;
    
    
    public function getGroupSharingAction($hashCode) {
        $action = $this->database->query("SELECT 
                T1.ACTION_TYPE,
                T2.ID_ACTION,
                T2.ID_GROUP,
                T3.NAME,
                T3.URL_ID
        FROM public_actions T1
        LEFT JOIN group_sharing T2 ON T1.ID_ACTION=T2.ID_ACTION
        LEFT JOIN groups T3 ON T3.ID_GROUP=T2.ID_GROUP
        WHERE T1.HASH_CODE=? AND T1.ACTIVE=1 AND T2.SHARE_BY_LINK=1", $hashCode)->fetch();
        
        return $action;
    }
    
    public function getActionType($hashCode) {
        $action = $this->database->query("SELECT 
                T1.ACTION_TYPE
        FROM public_actions T1
        WHERE T1.HASH_CODE=? AND T1.ACTIVE=1", $hashCode)->fetchField();
        
        return $action;
    }
    
    
    public function addNewAction($actionType) 
    {
        $hashCode = substr(md5(openssl_random_pseudo_bytes(20)),-8);
        $this->database->table('public_actions')->insert(array(
            'HASH_CODE' => $hashCode,
            'ACTION_TYPE' => $actionType,
            'ACTIVE' => 1
        ));
        return $hashCode;
    }
}
