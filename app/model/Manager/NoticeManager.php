<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;

/**
 * Description of TaskManager
 *
 * @author Radaq
 */
class NoticeManager extends BaseManager 
{
 
   
    public function getNotices(\App\Model\Entities\User $user, $limit)
    {
        return $this->database->query("SELECT * FROM notices WHERE ID_USER=? LIMIT ?", $user->id, $limit)->fetchAll();
    }
}
