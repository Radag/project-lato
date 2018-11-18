<?php
namespace App\Model\Manager;

use App\Model\Entities\User;
use App\Model\Entities\Group;

class SearchManager extends BaseManager 
{    
    public function searchTerm($term) {
        $term = '%' . $term . '%';
        $return = (object)['users' => [], 'groups' => []];
        $users = $this->db->fetchAll("SELECT * FROM vw_all_users WHERE name LIKE ? OR surname LIKE ?", $term , $term);
        foreach($users as $userData) {
            $user = new User($userData);
            $return->users[] = $user;
            
        }
        $groups = $this->db->fetchAll("SELECT T1.*, T2.main_color
                                       FROM `group` T1 JOIN group_scheme T2 ON T1.group_scheme_id=T2.id
                                       WHERE T1.name LIKE ?", $term);
        foreach($groups as $groupData) {
            $group = new Group($groupData);
            $return->groups[] = $group;
            
        }
        return $return;
    }
}
