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

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class GroupManager extends Nette\Object{
 
    const RELATION_OWNER = 3;
    const RELATION_TEACHER = 1;
    const RELATION_STUDENT = 2;
    
    /** @var Nette\Database\Context */
    private $database;
    
    /** @var NotificationManager */
    private $notificationManager;
    
    /** @var UserManager */
    private $userManager;


    public function __construct(Nette\Database\Context $database, NotificationManager $notificationManager, UserManager $userManager)
    {
        $this->database = $database;
        $this->notificationManager = $notificationManager;
        $this->userManager = $userManager;
    }

    public function setGroupVisited(User $user, $idGroup)
    {
        $this->database->query("UPDATE user_group SET LAST_VISIT=NOW() WHERE ID_USER=? AND ID_GROUP=?", $user->id, $idGroup);
    }
    
    public function isUserInGroup($idUser, $idGroup) 
    {
        $id = $this->database->query("SELECT T1.ID_GROUP FROM (SELECT DISTINCT ID_GROUP FROM user_group WHERE ID_USER=? AND ACTIVE=1
            UNION 
            SELECT DISTINCT ID_GROUP FROM groups WHERE ID_OWNER=?) T1 WHERE T1.ID_GROUP=?"
                , $idUser, $idUser, $idGroup)->fetchField();
        return !empty($id);
    }
    
    
    public function getUserGroups(User $user)
    {
        $return = array();
        $yourGroups = $this->database->query("SELECT T1.ID_GROUP, T3.MAIN_COLOR, T2.NAME, T2.SHORTCUT, T2.GROUP_TYPE, T2.URL_ID FROM (
            SELECT DISTINCT ID_GROUP FROM user_group WHERE ID_USER=? AND ACTIVE=1
            UNION 
            SELECT DISTINCT ID_GROUP FROM groups WHERE ID_OWNER=? AND ARCHIVED=0) T1
            JOIN groups T2 ON (T1.ID_GROUP = T2.ID_GROUP AND T2.ARCHIVED=0)
            LEFT JOIN group_color_scheme T3 ON T2.COLOR_SCHEME=T3.ID_SCHEME", $user->id, $user->id)->fetchAll(); 
        
        if(!empty($yourGroups)) {
            foreach($yourGroups as $s) {
                $group = new Group;
                $group->id = $s->ID_GROUP;
                $group->name = $s->NAME;
                $group->shortcut = $s->SHORTCUT;
                $group->groupType = $s->GROUP_TYPE;
                $group->mainColor = $s->MAIN_COLOR;
                $group->urlId = $s->URL_ID;
                $return[$s->ID_GROUP] = $group;
            }
        }
        return $return;
    }

    public function createGroup(Group $group) 
    {
        $this->database->beginTransaction();
        $this->database->table('groups')->insert(array(
                'NAME' => $group->name,
                'ID_OWNER' => $group->owner->id,
                'GROUP_TYPE' => $group->groupType,
                'SHORTCUT' => $group->shortcut,
                'COLOR_SCHEME' => $group->mainColor
        ));
        $idGroup = $this->database->query("SELECT MAX(ID_GROUP) FROM groups")->fetchField();
               
        $urlId = $idGroup . '_' . \Nette\Utils\Strings::webalize($group->name);
        $this->database->query("UPDATE groups SET URL_ID=? WHERE ID_GROUP=?", $urlId, $idGroup);
              
        $this->database->commit();         
    }
    
    public function getGroup($idGroup)
    {
        $group = $this->database->query("SELECT 
                T1.ID_GROUP,
                T1.URL_ID,
                T1.NAME,
                T1.SHORTCUT,
                T1.ID_OWNER AS OWNER_ID,
                T2.NAME AS OWNER_NAME,
                T2.SURNAME AS OWNER_SURNAME,
                T3.MAIN_COLOR,
                T4.STUDENTS,
                T5.SHARE_BY_LINK,
                T6.HASH_CODE
        FROM groups T1
        LEFT JOIN user T2 ON T1.ID_OWNER=T2.ID_USER
        LEFT JOIN group_color_scheme T3 ON T1.COLOR_SCHEME=T3.ID_SCHEME
        LEFT JOIN (SELECT COUNT(ID_USER) AS STUDENTS, ID_GROUP FROM user_group WHERE ACTIVE=1 GROUP BY ID_GROUP) T4 ON T4.ID_GROUP=T1.ID_GROUP
        LEFT JOIN group_sharing T5 ON T1.ID_GROUP=T5.ID_GROUP
        LEFT JOIN public_actions T6 ON T5.ID_ACTION=T6.ID_ACTION
        WHERE T1.URL_ID=?", $idGroup)->fetch();

        $groupModel = new Group();
        $user = new User();
        $user->surname = $group->OWNER_SURNAME;
        $user->name = $group->OWNER_NAME;
        $user->id = $group->OWNER_ID;
        $groupModel->id = $group->ID_GROUP;
        $groupModel->name = $group->NAME;
        $groupModel->shortcut = $group->SHORTCUT;
        $groupModel->mainColor = $group->MAIN_COLOR;
        $groupModel->numberOfStudents = $group->STUDENTS;
        $groupModel->owner = $user;
        $groupModel->sharingOn = $group->SHARE_BY_LINK;
        $groupModel->sharingCode = $group->HASH_CODE;
        $groupModel->urlId = $group->URL_ID;
        
        return $groupModel;       
    }
    
    /**
     * 
     * @param User $user
     * @return Group
     */
    public function getGroups(User $user)
    {
        $return = array();
        $userGroups = $this->getUserGroups($user);
        
        if(!empty($userGroups)) {
            $groups = $this->database->query("SELECT 
                        T1.ID_GROUP,
                        T1.URL_ID,
                        T1.NAME,
                        T1.SHORTCUT,
                        T2.NAME AS OWNER_NAME,
                        T2.SURNAME AS OWNER_SURNAME,
                        T2.ID_USER AS OWNER_ID,
                        T3.MAIN_COLOR,
                        T4.STUDENTS,
                        T5.NEW_MESSAGE,
                        T6.PATH,
                        T6.FILENAME
                FROM groups T1
                LEFT JOIN user T2 ON T1.ID_OWNER=T2.ID_USER
                LEFT JOIN group_color_scheme T3 ON T1.COLOR_SCHEME=T3.ID_SCHEME
                LEFT JOIN (SELECT COUNT(ID_USER) AS STUDENTS, ID_GROUP FROM user_group WHERE ID_RELATION=2 AND ACTIVE=1 GROUP BY ID_GROUP) T4 ON T4.ID_GROUP=T1.ID_GROUP 
                LEFT JOIN file_list T6 ON T6.ID_FILE=T2.PROFILE_IMAGE
                LEFT JOIN (
                    SELECT COUNT(T2.ID_MESSAGE) AS NEW_MESSAGE, T1.ID_GROUP FROM user_group T1
                    LEFT JOIN message T2 ON (T1.ID_GROUP=T2.ID_GROUP AND T2.CREATED_WHEN>T1.LAST_VISIT)
                    WHERE T1.ID_USER=? AND T1.ACTIVE=1
                    GROUP BY T1.ID_GROUP
                ) T5 ON T5.ID_GROUP=T1.ID_GROUP
                WHERE T1.ARCHIVED=0 AND T1.GROUP_TYPE=2 AND T1.ID_GROUP IN (" . implode(',', array_keys($userGroups)) . ")", $user->id)->fetchAll();
            foreach($groups as $group) {
                $groupModel = new Group();
                $teacher = new User();
                $teacher->surname = $group->OWNER_SURNAME;
                $teacher->name = $group->OWNER_NAME;
                $teacher->id = $group->OWNER_ID;
                $teacher->profileImage = "https://cdn.lato.cz/" . $group->PATH . "/" . $group->FILENAME;
                $groupModel->id = $group->ID_GROUP;
                $groupModel->name = $group->NAME;
                $groupModel->shortcut = $group->SHORTCUT;
                $groupModel->mainColor = $group->MAIN_COLOR;
                $groupModel->numberOfStudents = $group->STUDENTS;
                $groupModel->owner = $teacher;
                $groupModel->newMessages = $group->NEW_MESSAGE;
                $groupModel->urlId = $group->URL_ID;
                $return[] = $groupModel;
            }
        } 
        return $return;       
    }
    
 
    public function removeUserFromGroup($idGroup, $idUser)
    {
        $this->database->query("UPDATE user_group SET ACTIVE=0 WHERE ID_USER=? AND ID_GROUP=?", $idUser, $idGroup);
    }
    
    public function archiveGroup($idGroup)
    {
        $this->database->query("UPDATE groups SET ARCHIVED=1 WHERE ID_GROUP=?", $idGroup);
    }
    
    public function addUserToGroup($idGroup, $idUser, $relation, $fromLink = null)
    {
        $row = $this->database->query("SELECT * FROM user_group WHERE ID_USER=? AND ID_GROUP=?", $idUser, $idGroup)->fetch();
        
        if(empty($row)) {
            $this->database->table('user_group')->insert(array(
                'ID_USER' => $idUser,
                'ID_GROUP' => $idGroup,
                'ID_RELATION' => $relation,
                'FROM_LINK' => $fromLink
            ));  
        } else {
            $this->database->query("UPDATE user_group SET ACTIVE=1 WHERE ID_USER=? AND ID_GROUP=?", $idUser, $idGroup);
        }

        
//        $user = $this->userManager->get($idUser);
//        $group = $this->getGroup($idGroup);
//        $notification = new \App\Model\Entities\Notification;
//        $notification->idUser = $group->teacher->id;
//        $notification->title = "Nový člen";
//        $notification->text = "Do vaší skupiny " . $group->name . " se přidal nový člen " . $user->username . ".";
  
      //  $this->notificationManager->addNotification($notification);
    }
    
    public function switchSharing(Group $group, $state) 
    {
        $this->database->beginTransaction();
        $id = $this->database->query("SELECT ID FROM group_sharing WHERE ID_GROUP=?", $group->id)->fetchField();
        if(empty($id)) {
            $this->database->table('public_actions')->insert(array(
                'HASH_CODE' => substr(md5(openssl_random_pseudo_bytes(20)),-8),
                'ACTION_TYPE' => 1,
                'ACTIVE' => 1
            ));
            $idAction = $this->database->query("SELECT MAX(ID_ACTION) FROM public_actions")->fetchField();
            $this->database->table('group_sharing')->insert(array(
                'ID_GROUP' => $group->id,
                'ID_ACTION' => $idAction,
                'SHARE_BY_LINK' => $state
            ));
        } else {
            $this->database->query("UPDATE group_sharing SET SHARE_BY_LINK=? WHERE ID_GROUP=?", $state, $group->id);
        }
        
        $this->database->commit();
        
    }
    
    public function getGroupUsers($idGroup)
    {
         $users = $this->database->query("SELECT DISTINCT T1.ID_USER, T2.NAME, T2.SURNAME, T2.USERNAME, T2.PROFILE_PATH, T2.PROFILE_FILENAME FROM 
            (SELECT ID_OWNER AS ID_USER FROM groups WHERE ID_GROUP=? 
            UNION SELECT ID_USER FROM user_group WHERE ID_GROUP=? AND ACTIVE=1) T1
            LEFT JOIN vw_user_detail T2 ON T1.ID_USER = T2.ID_USER", $idGroup, $idGroup)->fetchAll();
        
         $userArray = array();
         foreach($users as $us) {
             $user = new \App\Model\Entities\User;
             $user->id = $us->ID_USER;
             $user->surname = $us->SURNAME;
             $user->name = $us->NAME;
             $user->username = $us->USERNAME;
             if($us->PROFILE_FILENAME) {
                $user->profileImage = "https://cdn.lato.cz/" . $us->PROFILE_PATH . "/" . $us->PROFILE_FILENAME;
             }
             $userArray[] = $user;
             
         }
         return $userArray;
    }
      
        
}
