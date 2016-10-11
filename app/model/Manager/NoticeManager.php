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
        return $this->database->query("SELECT * FROM notices WHERE ID_USER=? ORDER BY CREATED_WHEN DESC LIMIT ?", $user->id, $limit)->fetchAll();
    }
    
    
    public function insertNotice(\App\Model\Entities\Notice $notice)
    {
        $this->database->table('notices')->insert(array(
                    'TEXT' => $notice->text,
                    'ID_USER' => $notice->user,
                    'CREATED_BY' => $this->user->id
            ));
    }
}
