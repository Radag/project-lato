<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\Group;
use App\Model\Entities\User;
use App\Model\Manager\NotificationManager;
use App\Model\Manager\UserManager;
use App\Model\Entities\Classroom;

/**
 * Description of ClassroomManager
 *
 * @author Radaq
 */
class ClassroomManager extends BaseManager 
{
    
    /** @var NotificationManager @inject */
    protected $notificationManager;
    
    /** @var UserManager @inject */
    protected $userManager;

    public function getRelation(User $user, $classes)
    {
        $mm = array();
        foreach($classes as $cl) {
            $mm[] = $cl->id;
        }
        
        $relations = "";
            
        
        if(!empty(implode(",", $mm))) {
            $rows = $this->database->query("SELECT * FROM vw_user_schools WHERE ID_CLASS IN (" . implode(",", $mm) . ") AND ID_USER=?", $user->id)->fetchAll();

            foreach($rows as $class) {
                if($relations == "") {
                    $relations = $class->CLASS_NAME;
                } else {
                    $relations = $relations . ", " . $class->CLASS_NAME;
                }
                
            }
        }
        $relations = "spolužák (" . $relations . ")";
        return $relations;
    }
    
    public function getClasses(User $user)
    {
         $classes = $this->database->query("SELECT * FROM vw_user_schools WHERE ID_USER=?", $user->id)->fetchAll();
        
         $return = array();
         foreach($classes as $class) {
             $classroom = new Classroom;
             $classroom->id = $class->ID_CLASS;
             $classroom->className = $class->CLASS_NAME;
             $classroom->school = $class->SCHOOL_NAME;
             $return[] = $classroom;
             
         }
         return $return;
    }
      
        
}
