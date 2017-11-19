<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use App\Model\Entities\User;

/**
 * Description of SchoolManager
 *
 * @author Radaq
 */
class SchoolManager extends BaseManager 
{     
            
    public function insertSchool($schoolName, $className, User $user) {
        $schoolId = $this->database->query("SELECT ID_SCHOOL FROM school WHERE NAME=? ",$schoolName)->fetchField();
        if(empty($schoolId)) {
            $this->database->table('school')->insert(array(
                'NAME' => $schoolName
            ));
            $schoolId = $this->database->query('SELECT MAX(ID_SCHOOL) FROM school')->fetchField();
        }
        $classId = $this->database->query("SELECT ID_CLASS FROM school_class WHERE NAME=? AND ID_SCHOOL=?", $className, $schoolId)->fetchField();
        if(empty($classId)) {
            $this->database->table('school_class')->insert(array(
                'ID_SCHOOL' => $schoolId,
                'NAME' => $className
            ));
            $classId = $this->database->query('SELECT MAX(ID_CLASS) FROM school_class')->fetchField();
        }
        $this->database->query("DELETE FROM school_class_user WHERE ID_USER=?", $user->id);
        $this->database->table('school_class_user')->insert(array(
                'ID_SCHOOL' => $schoolId,
                'ID_USER' => $user->id,
                'ID_CLASS' => $classId
        ));
    }
    public function getSchool(User $user) {
        return $this->database->query("SELECT T2.NAME AS SCHOOL, T3.NAME AS CLASS FROM 
            school_class_user T1
            LEFT JOIN school T2 ON T1.ID_SCHOOL = T2.ID_SCHOOL
            LEFT JOIN school_class T3 ON T1.ID_CLASS = T3.ID_CLASS
            WHERE T1.ID_USER = ? ", $user->id)->fetch();
    }
}
