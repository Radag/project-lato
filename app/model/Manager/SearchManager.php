<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\User;
use App\Model\Entities\Group;
/**
 * Description of TaskManager
 *
 * @author Radaq
 */
class SearchManager extends BaseManager 
{     
           
    
    public function searchTerm($term) {
        $term = '%' . $term . '%';
        $return = (object)array('users' => array(), 'groups' => array());
        $users = $this->database->fetchAll("SELECT * FROM vw_user_detail WHERE name LIKE ? OR surname LIKE ?", $term , $term);
        foreach($users as $userData) {
            $user = new User();
            $user->id = $userData->ID_USER;
            $user->surname = $userData->SURNAME;
            $user->name = $userData->NAME;
            $user->email = $userData->EMAIL;
            $user->urlId = $userData->URL_ID;
            $user->profileImage = User::createProfilePath($userData->PROFILE_PATH, $userData->PROFILE_FILENAME, $userData->SEX);
            $return->users[] = $user;
            
        }
        $groups = $this->database->fetchAll("SELECT T1.*, T2.MAIN_COLOR FROM groups T1 LEFT JOIN group_color_scheme T2 ON T1.COLOR_SCHEME=T2.ID_SCHEME WHERE T1.name LIKE ?", $term);
        foreach($groups as $groupData) {
            $group = new Group();
            $group->name = $groupData->NAME;
            $group->shortcut = $groupData->SHORTCUT;
            $group->mainColor = $groupData->MAIN_COLOR;
            $group->urlId = $groupData->URL_ID;
            $return->groups[] = $group;
            
        }
        return $return;
    }
}
