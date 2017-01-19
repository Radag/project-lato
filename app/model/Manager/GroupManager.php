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
use App\Model\Manager\PublicActionManager;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class GroupManager extends BaseManager {
 
    const RELATION_OWNER = 3;
    const RELATION_TEACHER = 1;
    const RELATION_STUDENT = 2;
    
    /** @var NotificationManager @inject */
    protected $notificationManager;
    
    /** @var UserManager @inject */
    protected $userManager;
    
    /** @var PublicActionManager @inject */
    protected $publicActionManager;
    
    public function __construct(Nette\Database\Context $database,
                    Nette\Security\User $user,
                    UserManager $userManager,
                    PublicActionManager $publicActionManager,
                    NotificationManager $notificationManager
    )
    {
            $this->database = $database;
            $this->user = $user;
            $this->userManager = $userManager;
            $this->publicActionManager = $publicActionManager;
            $this->notificationManager = $notificationManager;
    }
    

    public function setGroupVisited(User $user, $idGroup)
    {
        $this->database->query("UPDATE user_group SET LAST_VISIT=NOW() WHERE ID_USER=? AND ID_GROUP=?", $user->id, $idGroup);
    }
    
    public function isUserInGroup($idUser, $idGroup)
    {
        $id = $this->database->query("SELECT DISTINCT ID_GROUP FROM vw_user_groups WHERE ID_GROUP=? AND ID_USER=?", $idGroup, $idUser)->fetchField();
        return !empty($id);
    }
    
    
    public function getUserGroups(User $user)
    {
        $return = array();
        $yourGroups = $this->database->query("SELECT ID_GROUP, MAIN_COLOR, NAME, SHORTCUT, GROUP_TYPE, URL_ID FROM 
            vw_user_groups_detail WHERE ID_USER=? AND ARCHIVED=0 ORDER BY NAME ASC", $user->id)->fetchAll(); 
        
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
                'COLOR_SCHEME' => $group->mainColor,
                'CODE' => strtoupper(substr(md5(openssl_random_pseudo_bytes(20)),-8))
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
                T1.DESCRIPTION,
                T1.ROOM,
                T1.SUBGROUP,
                T1.ID_OWNER AS OWNER_ID,
                T2.URL_ID AS OWNER_URL_ID,
                T2.NAME AS OWNER_NAME,
                T2.SURNAME AS OWNER_SURNAME,
                T2.PROFILE_PATH AS OWNER_PROFILE_PATH,
                T2.PROFILE_FILENAME AS OWNER_PROFILE_FILENAME,
                T2.SEX AS OWNER_SEX,
                T3.MAIN_COLOR,
                T4.STUDENTS,
                T5.SHARE_BY_LINK,
                T5.SHARE_BY_CODE,
                T1.CODE AS INTER_CODE,
                T6.HASH_CODE AS PUBLIC_CODE
        FROM groups T1
        LEFT JOIN vw_user_detail T2 ON T1.ID_OWNER=T2.ID_USER
        LEFT JOIN group_color_scheme T3 ON T1.COLOR_SCHEME=T3.ID_SCHEME
        LEFT JOIN (SELECT COUNT(ID_USER) AS STUDENTS, ID_GROUP FROM user_group WHERE ACTIVE=1 AND ID_RELATION=2 GROUP BY ID_GROUP) T4 ON T4.ID_GROUP=T1.ID_GROUP
        LEFT JOIN group_sharing T5 ON T1.ID_GROUP=T5.ID_GROUP
        LEFT JOIN public_actions T6 ON T5.ID_ACTION=T6.ID_ACTION
        WHERE T1.URL_ID=?", $idGroup)->fetch();

        $groupModel = new Group();
        $teacher = new User();
        $teacher->surname = $group->OWNER_SURNAME;
        $teacher->name = $group->OWNER_NAME;
        $teacher->id = $group->OWNER_ID;
        $teacher->urlId = $group->OWNER_URL_ID;
        $teacher->profileImage = User::createProfilePath($group->OWNER_PROFILE_PATH, $group->OWNER_PROFILE_FILENAME, $group->OWNER_SEX);
        $groupModel->id = $group->ID_GROUP;
        $groupModel->name = $group->NAME;
        $groupModel->shortcut = $group->SHORTCUT;
        $groupModel->mainColor = $group->MAIN_COLOR;
        $groupModel->numberOfStudents = $group->STUDENTS;
        $groupModel->owner = $teacher;
        $groupModel->interCode = $group->INTER_CODE;
        $groupModel->publicCode = $group->PUBLIC_CODE;
        $groupModel->shareByLink = $group->SHARE_BY_LINK;
        $groupModel->shareByCode = $group->SHARE_BY_CODE;
        $groupModel->urlId = $group->URL_ID;
        $groupModel->description = $group->DESCRIPTION;
        $groupModel->room = $group->ROOM;
        $groupModel->subgroup = $group->SUBGROUP;
        
        return $groupModel;       
    }
    
    public function getPrivileges($idGroup)
    {
        $group = $this->database->query("SELECT 
                T1.ID_GROUP,
                T1.PR_DELETE_OWN_MSG,
                T1.PR_CREATE_MSG,
                T1.PR_EDIT_OWN_MSG,
                T1.PR_SHARE_MSG
        FROM groups T1 WHERE T1.ID_GROUP=?", $idGroup)->fetch();
        
        return $group;
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
                WHERE T1.ARCHIVED=0 AND T1.GROUP_TYPE=2 AND T1.ID_GROUP IN (" . implode(',', array_keys($userGroups)) . ") ORDER BY T1.NAME ASC", $user->id)->fetchAll();
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
    
    
    public function editGroup(Group $group)
    {
        $data = [
            'NAME' => $group->name,
            'DESCRIPTION' => $group->description,
            'ROOM' => $group->room,
            'SUBGROUP' => $group->subgroup,
            'SHORTCUT' => $group->shortcut
        ];
        
        $this->database->query("UPDATE groups SET ? WHERE ID_GROUP=?", $data, $group->id);
    }
    
    public function editGroupPrivileges($privileges, $idGroup)
    {      
        $this->database->query("UPDATE groups SET ? WHERE ID_GROUP=?", $privileges, $idGroup);
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

        $data['user'] = $this->userManager->get($idUser);
        $data['group'] = $this->getGroup($idGroup);
        $this->notificationManager->addNotificationType(NotificationManager::TYPE_NEW_GROUP_MEMBER, $data);
    }
    
    public function getGroupByCode($code) 
    {
        return $this->database->query("SELECT T1.id_group FROM groups T1 JOIN group_sharing T2 ON T1.ID_GROUP=T2.ID_GROUP WHERE T2.SHARE_BY_CODE=1 AND T1.CODE=?", $code)->fetchField();
        
    }
    
    public function switchSharing(Group $group, $stateByLink, $stateByCode) 
    {
        $this->database->beginTransaction();
        $id = $this->database->query("SELECT ID FROM group_sharing WHERE ID_GROUP=?", $group->id)->fetchField();
        if(empty($id)) {
            $this->publicActionManager->addNewAction(PublicActionManager::ACTION_ADD_TO_GROUP);
            $idAction = $this->database->query("SELECT MAX(ID_ACTION) FROM public_actions")->fetchField();
            $this->database->table('group_sharing')->insert(array(
                'ID_GROUP' => $group->id,
                'ID_ACTION' => $idAction,
                'SHARE_BY_LINK' => (int)$stateByLink,
                'SHARE_BY_CODE' => (int)$stateByCode
            ));
        } else {
            $this->database->query("UPDATE group_sharing SET SHARE_BY_LINK=?, SHARE_BY_CODE=? WHERE ID_GROUP=?", (int)$stateByLink, (int)$stateByCode, $group->id);
        }
        
        $this->database->commit();
        
    }
    
    public function getGroupUsers($idGroup, $filterRelation = null)
    {         
        if($filterRelation === null) {
            $users = $this->database->query("SELECT DISTINCT T1.ID_USER, T2.SEX, T2.NAME, T2.SURNAME, T2.USERNAME, T2.PROFILE_PATH, T2.URL_ID, T2.PROFILE_FILENAME FROM 
            (SELECT ID_OWNER AS ID_USER FROM groups WHERE ID_GROUP=? 
            UNION SELECT ID_USER FROM user_group WHERE ID_GROUP=? AND ACTIVE=1) T1
            LEFT JOIN vw_user_detail T2 ON T1.ID_USER = T2.ID_USER", $idGroup, $idGroup)->fetchAll();
        } else {
            $users = $this->database->query("SELECT DISTINCT T1.ID_USER, T2.SEX, T2.NAME, T2.SURNAME, T2.USERNAME, T2.PROFILE_PATH, T2.URL_ID, T2.PROFILE_FILENAME FROM 
            (SELECT ID_USER FROM user_group WHERE ID_GROUP=? AND ID_RELATION=? AND ACTIVE=1) T1
            LEFT JOIN vw_user_detail T2 ON T1.ID_USER = T2.ID_USER", $idGroup, $filterRelation)->fetchAll();
        }
         
 
         $userArray = array();
         foreach($users as $us) {
             $user = new \App\Model\Entities\User;
             $user->id = $us->ID_USER;
             $user->surname = $us->SURNAME;
             $user->name = $us->NAME;
             $user->username = $us->USERNAME;
             $user->urlId = $us->URL_ID;
             if($us->PROFILE_FILENAME) {
                $user->profileImage = "https://cdn.lato.cz/" . $us->PROFILE_PATH . "/" . $us->PROFILE_FILENAME;
             } else {
                if($us->SEX == 'M') {
                    $user->profileImage = '/images/default-avatar_man.png';
                } else {
                    $user->profileImage = '/images/default-avatar_woman.png';
                }
             }
             $userArray[] = $user;
             
         }
         return $userArray;
    }
      
    
    
    public function insertSchedule($schedule, Group $group)
    {
        $this->database->beginTransaction();
        $this->database->query('DELETE FROM group_schedule WHERE ID_GROUP=?', $group->id);
        foreach($schedule as $data) {
            $this->database->table('group_schedule')->insert(array(
                'ID_GROUP' => $group->id,
                'DAY_IN_WEEK' => $data['DAY_IN_WEEK'],
                'TIME_FROM' => $data['TIME_FROM'],
                'TIME_TO' => $data['TIME_TO'],
            ));  
        }
        $this->database->commit();
    }
    
    public function getSchedule(Group $group)
    {
        return $this->database->query("SELECT * FROM group_schedule WHERE ID_GROUP=?", $group->id)->fetchAll();
    }
    
    
    public function addGroupToPeriods(Group $group, $activePeriods)
    {
        $this->database->beginTransaction();
        $this->database->query("DELETE FROM group_period WHERE ID_GROUP=?", $group->id);
        foreach($activePeriods as $period) {
            $this->database->table('group_period')->insert(array(
                'ID_GROUP' => $group->id,
                'ID_PERIOD' => $period
            )); 
        }
        $this->database->commit();
    }
    
    public function getGroupPeriods(Group $group)
    {
        $return = array();
        $periods = $this->database->query("SELECT T2.* FROM group_period T1 LEFT JOIN school_period T2 ON T1.ID_PERIOD=T2.ID_PERIOD WHERE T1.ID_GROUP=?", $group->id)->fetchAll();
        
        foreach($periods as $period) {
            $return[$period->ID_PERIOD] = $period;
        }
        
        return $return;
    }
        
        
}
