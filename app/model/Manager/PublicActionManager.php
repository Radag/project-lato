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
class PublicActionManager extends Nette\Object{
 
    
    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    public function getAction($hashCode) {
        $action = $this->database->query("SELECT 
                T1.ACTION_TYPE,
                T2.ID_ACTION,
                T2.ID_GROUP,
                T3.NAME,
                T3.URL_ID
        FROM public_actions T1
        LEFT JOIN group_invitations T2 ON T1.ID_ACTION=T2.ID_ACTION
        LEFT JOIN groups T3 ON T3.ID_GROUP=T2.ID_GROUP
        WHERE T1.HASH_CODE=? AND T1.ACTIVE=1", $hashCode)->fetch();
        
        return $action;
    }
}
