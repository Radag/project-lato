<?php
namespace App\Model\Manager;

use App\Model\Entities\User;

class SchoolManager extends BaseManager 
{     
            
    public function insertSchool($schoolName, $className, User $user) {
        $this->db->begin();
        
        $schoolId = $this->db->fetchSingle("SELECT id FROM school WHERE name=? ", $schoolName);
        if(empty($schoolId)) {
            $this->db->query("INSERT INTO school", [
                'name' => $schoolName
            ]);
            $schoolId = $this->db->getInsertId();
        }
        $classId = $this->db->fetchSingle("SELECT id FROM school_class WHERE name=? AND school_id=?", $className, $schoolId);
        if(empty($classId)) {
            $this->db->query("INSERT INTO school_class", [
                'school_id' => $schoolId,
                'name' => $className
            ]);
            $classId = $this->db->getInsertId();
        }
        $this->db->query("DELETE FROM school_class_user WHERE user_id=?", $user->id);
        $this->db->query("INSERT INTO school_class_user", [
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'class_id' => $classId
        ]);        
        $this->db->commit();
    }
    
    public function removeSchools(User $user) 
    {
        return $this->db->query("DELETE FROM school_class_user WHERE user_id=?", $user->id);
    }
    
    public function getSchool(User $user)
    {
        return $this->db->fetch("SELECT T2.name AS school, T3.name AS class FROM 
            school_class_user T1
            LEFT JOIN school T2 ON T1.school_id = T2.id
            LEFT JOIN school_class T3 ON T1.class_id= T3.id
            WHERE T1.user_id = ? ", $user->id);
    }
}
