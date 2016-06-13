<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model;

use Nette;
use App\Model\Entities\Group;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class GroupManager extends Nette\Object{
 
    
    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
            $this->database = $database;
    }

    
    public function getGroups()
    {
        $return = array();
        $groups = $this->database->query("SELECT 
                        T1.ID_GROUP,
                        T1.NAME,
                        T1.SHORTCUT,
                        T1.COLOR_SCHEME,
                        T2.NAME AS TEACHER_NAME,
                        T2.SURNAME AS TEACHER_SURNAME
                FROM groups T1
                LEFT JOIN user T2 ON T1.ID_TEACHER=T2.ID_USER")->fetchAll();
        foreach($groups as $group) {
            $groupModel = new Group();
            $user = new Entities\User();
            $user->surname = $group->TEACHER_SURNAME;
            $user->name = $group->TEACHER_NAME;
            $groupModel->colorScheme = $group->COLOR_SCHEME;
            $groupModel->id = $group->ID_GROUP;
            $groupModel->name = $group->NAME;
            $groupModel->shortcut = $group->SHORTCUT;
            $groupModel->teacher = $user;
            $return[] = $groupModel;
        }
        
        return $return;
    }
    
 
      
    
}
