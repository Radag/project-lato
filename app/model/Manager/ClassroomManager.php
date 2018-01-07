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
        if($relations != "") {
            $relations = ", spolužák (" . $relations . ")";
        }
        return $relations;
    }
    
    public function getClasses(User $user)
    {
         $classes = $this->db->fetchAll("SELECT T3.id, T2.name AS school_name, T3.name AS class_name, T3.grade FROM school_class_user T1
            LEFT JOIN school T2 ON T1.school_id=T2.id
            LEFT JOIN school_class T3 ON T1.class_id=T3.id
            WHERE T1.user_id=?", $user->id);
        
         $return = array();
         foreach($classes as $class) {
             $classroom = new Classroom;
             $classroom->id = $class->id;
             $classroom->className = $class->class_name;
             $classroom->school = $class->school_name;
             $classroom->classGrade = $class->grade;
             $return[] = $classroom;
             
         }
         return $return;
    }
      
        
}
