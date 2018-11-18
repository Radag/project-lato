<?php
namespace App\Model\Manager;

class PublicActionManager extends BaseManager 
{
    const ACTION_ADD_TO_GROUP = 1;
    const ACTION_MAIL_VALIDATION = 2;
    const ACTION_LOST_PASS = 3;
    const ACTION_NEW_PASS = 4;
    
    
    public function getGroupSharingAction($hashCode) {
        $action = $this->db->fetch("SELECT 
                T1.action_type,
                T2.action_id,
                T2.group_id,
                T3.name,
                T3.slug
        FROM public_actions T1
        LEFT JOIN group_sharing T2 ON T1.id=T2.action_id
        LEFT JOIN `group` T3 ON T3.id=T2.group_id
        WHERE T1.hash_code=? AND T1.active=1 AND T2.share_by_link=1", $hashCode);
        
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
        $codePass = true;
        while($codePass) {
            $code = substr(md5(openssl_random_pseudo_bytes(20)),-8);
            $exist = $this->db->fetchSingle("SELECT id FROM `public_actions` WHERE hash_code=?", $code);
            if(!$exist) {
                $codePass = false;
            }
        }
        
        $this->db->query("INSERT INTO public_actions", [
            'hash_code' => $code,
            'action_type' => $actionType
        ]);

        return (object)['code' => $code, 'id' => $this->db->getInsertId()];
    }
}
