<?php

namespace App\Model\Manager;


class FictiveUserManager extends BaseManager 
{     
    
    public function createFictiveUser($student, $group) 
    {
        $this->db->query('INSERT INTO user', [
            'name' => $student->name,
            'surname' => $student->surname
        ]);
        $userId = $this->db->getInsertId();
        $slug = $userId . '_' . \Nette\Utils\Strings::webalize($student->name . '_' . $student->surname);
        $this->db->query("UPDATE user SET ", ['slug' => $slug], "WHERE id=?", $userId);
        
        $this->db->query('INSERT INTO user_fictive', [
            'id' => $userId,
            'group_id' => $group->id,
        ]);
        return $userId;
    }
}
