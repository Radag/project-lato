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
 
    const RELATION_OWNER = 'owner';
    const RELATION_TEACHER = 'teacher';
    const RELATION_STUDENT = 'student';
       
    /** @var NotificationManager @inject */
    protected $notificationManager;
    
    /** @var UserManager @inject */
    protected $userManager;
    
    /** @var PublicActionManager @inject */
    protected $publicActionManager;
    
    /** @var \Dibi\Connection  */
    protected $db;
    
    public function __construct(Nette\Database\Context $database,
                    Nette\Security\User $user,
                    UserManager $userManager,
                    PublicActionManager $publicActionManager,
                    NotificationManager $notificationManager,
            \Dibi\Connection $db
    )
    {
            $this->database = $database;
            $this->user = $user;
            $this->userManager = $userManager;
            $this->publicActionManager = $publicActionManager;
            $this->notificationManager = $notificationManager;
            $this->db = $db;
    }
    
    
    public function createGroup(Group $group) 
    {
        $this->db->begin();
        $this->db->query("INSERT INTO `group`", [
            'name' => $group->name,
            'shortcut' => $group->shortcut,
            'group_scheme_id' => $group->mainColor,
        ]);
        $group->id = $this->db->getInsertId();
        
        $codePass = true;
        while($codePass) {
            $code =  strtoupper(substr(md5(openssl_random_pseudo_bytes(20)),-8));
            $exist = $this->db->fetchSingle("SELECT id FROM `group` WHERE code=?", $code);
            if(!$exist) {
                $codePass = false;
            }
        }
        
        $slug = $group->id . '_' . \Nette\Utils\Strings::webalize($group->name);
        $this->db->query("UPDATE `group` SET", [
            'code' => $code,
            'slug' => $slug
        ], "WHERE id=?", $group->id);
        
        $this->addUserToGroup($group, $group->owner->id, self::RELATION_OWNER);
        $this->addGroupPeriod($group, 'První období', 1);
        $this->db->commit();
        return $slug;
    }
    
    public function addUserToGroup(Group $group, $userId, $relation, $fromLink = 0)
    {
        $this->db->begin();
        $row = $this->db->fetch("SELECT * FROM group_user WHERE user_id=? AND group_id=?", $userId, $group->id);
        
        if(empty($row)) {
            $this->db->query("INSERT INTO group_user", [
                'user_id' => $userId,
                'group_id' => $group->id,
                'relation_type' => $relation,
                'from_link' => $fromLink
            ]); 
        } else {
            $this->db->query("UPDATE group_user SET", [
                'active' => 1,
                'relation_type' => $relation
            ], "WHERE user_id=? AND group_id=?", $userId, $group->id);
        }
        $this->db->commit();
    }

    public function setGroupVisited(User $user, $idGroup)
    {
        $this->db->query("UPDATE group_user SET last_visit=NOW() WHERE user_id=? AND group_id=?", $user->id, $idGroup);
    }
    
    public function isUserInGroup($idUser, $idGroup)
    {
        $id = $this->db->fetchSingle("SELECT group_id FROM group_user WHERE active=1 AND group_id=? AND user_id=?", $idGroup, $idUser);
        return !empty($id);
    }
    
    public function getColorsSchemes() 
    {
        return $this->db->fetchPairs("SELECT id, main_color FROM group_scheme");
    }
    
    public function getUserGroups(User $user)
    {
        $return = [];
        $userGroups = $this->db->fetchAll("SELECT T1.id, T3.main_color, T1.name, T1.shortcut, T1.slug, T2.relation_type 
            FROM `group` T1
            JOIN group_user T2 ON (T1.id=T2.group_id AND T2.user_id=? AND T2.active=1)
            JOIN group_scheme T3 ON (T1.group_scheme_id=T3.id)
            ORDER BY T1.name ASC", $user->id);   
  
        if(!empty($userGroups)) {
            foreach($userGroups as $s) {
                $group = new Group;
                $group->id = $s->id;
                $group->name = $s->name;
                $group->shortcut = $s->shortcut;
                $group->mainColor = $s->main_color;
                $group->slug = $s->slug;
                $group->relation = $s->relation_type;
                $return[$s->id] = $group;
            }
        }
        return $return;
    }  
    
    public function getGroup($urlIdGroup)
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
                T3.ID_SCHEME,
                T3.CODE,
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
        WHERE T1.URL_ID=?", $urlIdGroup)->fetch();
        
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
        $groupModel->colorSchemeId = $group->ID_SCHEME;
        $groupModel->colorScheme = $group->CODE;  
        return $groupModel;       
    }
    
    public function getUserGroup($groupSlug, User $user)
    {
        $group = $this->db->fetch("SELECT 
               T1.id,
               T1.slug,
               T1.name,
               T1.shortcut,
               T1.description,
               T1.room,
               T1.subgroup,
               T1.group_scheme_id,
               T1.code,
               T2.relation_type,
               T3.code AS scheme_code,
               T5.id AS owner_id,
               T5.name AS owner_name,
               T5.surname AS owner_surname,
               T5.slug AS owner_slug,
               T5.profile_image,
               T5.sex AS owner_sex,
               T6.share_by_link,
               T6.share_by_code,
               T7.hash_code AS public_code
            FROM `group` T1
            JOIN group_user T2 ON (T1.id = T2.group_id AND T2.user_id=?)
            JOIN group_scheme T3 ON (T1.group_scheme_id = T3.id)
            LEFT JOIN group_user T4 ON (T4.group_id=T1.id AND T4.relation_type='owner')
            JOIN user T5 ON T4.user_id=T5.id
            LEFT JOIN group_sharing T6 ON T6.group_id=T1.id
            LEFT JOIN public_actions T7 ON (T7.id = T6.action_id AND T7.active=1)
            WHERE T1.slug=? AND T2.active=1", $user->id, $groupSlug);
        if($group) {
            $owner = new User();
            $owner->surname = $group->owner_surname;
            $owner->name = $group->owner_name;
            $owner->id = $group->owner_id;
            $owner->slug = $group->owner_slug;
            $owner->profileImage = User::createProfilePath($group->profile_image, $group->owner_sex);
           
            $groupModel = new Group();
            $groupModel->owner = $owner;
            $groupModel->id = $group->id;
            $groupModel->name = $group->name;
            $groupModel->shortcut = $group->shortcut;
            $groupModel->slug = $group->slug;
            $groupModel->description = $group->description;
            $groupModel->room = $group->room;
            $groupModel->subgroup = $group->subgroup;
            $groupModel->colorSchemeId = $group->group_scheme_id;
            $groupModel->colorScheme = $group->scheme_code;
            $groupModel->relation = $group->relation_type;
            $groupModel->shareByCode = $group->share_by_code;
            $groupModel->shareByLink = $group->share_by_link;
            $groupModel->interCode = $group->code;
            $groupModel->publicCode = $group->public_code;
            return $groupModel;   
        } else {
            return null;
        }
    }
    
    public function getPrivileges($idGroup)
    {
        return $this->db->fetch("SELECT 
                T1.id,
                T1.PR_DELETE_OWN_MSG,
                T1.PR_CREATE_MSG,
                T1.PR_EDIT_OWN_MSG,
                T1.PR_SHARE_MSG
        FROM `group` T1 WHERE T1.id=?", $idGroup);
    }
    
    
    /**
     * 
     * @param User $user
     * @return Group
     
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
    */
    
 
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
        $this->db->query("UPDATE `group` SET", [
            'name' => $group->name,
            'description' => $group->description,
            'room' => $group->room,
            'subgroup' => $group->subgroup,
            'shortcut' => $group->shortcut,
            'group_scheme_id' => $group->colorSchemeId
        ], "WHERE id=?", $group->id);
    }
    
    public function editGroupPrivileges($privileges, $idGroup)
    {      
        $this->database->query("UPDATE groups SET ? WHERE ID_GROUP=?", $privileges, $idGroup);
    }
    
    public function getGroupByCode($code) 
    {
        $group = $this->database->query("SELECT T1.ID_GROUP, T1.URL_ID FROM groups T1 JOIN group_sharing T2 ON T1.ID_GROUP=T2.ID_GROUP WHERE T2.SHARE_BY_CODE=1 AND T1.CODE=?", $code)->fetch();
        if(empty($group)) {
            return false;
        } else {
            return new Group($group);
        }
    }
    
    public function switchSharing(Group $group, $stateByLink, $stateByCode) 
    {
        $this->db->begin();
        $id = $this->db->fetchSingle("SELECT id FROM group_sharing WHERE group_id=?", $group->id);
        if(empty($id)) {
            $action = $this->publicActionManager->addNewAction(PublicActionManager::ACTION_ADD_TO_GROUP);
            $this->db->query('INSERT INTO group_sharing', [
                'group_id' => $group->id,
                'action_id' => $action->id,
                'share_by_link' => (int)$stateByLink,
                'share_by_code' => (int)$stateByCode
            ]);
        } else {
            $this->db->query("UPDATE group_sharing SET", [
                'share_by_link' => (int)$stateByLink,
                'share_by_code' => (int)$stateByCode
            ], "WHERE group_id=?", $group->id);
        }
        $this->db->commit();          
    }
    
    public function getGroupUsers($idGroup, $filterRelation = null)
    {         
        if($filterRelation === null) {
            $users = $this->db->fetchAll("SELECT DISTINCT T1.ID_USER, T2.SEX, T2.NAME, T2.SURNAME, T2.USERNAME, T2.PROFILE_IMAGE, T2.URL_ID FROM 
            (SELECT ID_OWNER AS ID_USER FROM groups WHERE ID_GROUP=? 
            UNION SELECT ID_USER FROM user_group WHERE ID_GROUP=? AND ACTIVE=1) T1
            LEFT JOIN user T2 ON T1.ID_USER = T2.ID_USER", $idGroup, $idGroup);
        } else {
            $users = $this->db->fetchAll("SELECT
                    T2.sex, T2.name, T2.surname, T2.username, T2.profile_image, T2.slug 
                FROM group_user T1
                JOIN user T2 ON T1.user_id = T2.id
                WHERE T1.group_id=? AND T1.active=1 AND T1.relation_type=?", $idGroup, $filterRelation);
        }
         
        $userArray = [];
        foreach($users as $us) {
            $userArray[] = new User($us);
             
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
        return $this->db->fetchAll("SELECT * FROM group_schedule WHERE group_id=?", $group->id);
    }
    
    public function setActivePeriod(Group $group, $period)
    {
        $this->db->query("UPDATE group_period SET active=0, deactivated_when=NOW() WHERE group_id=? AND active=1", $group->id);
        $this->db->query("UPDATE group_period SET active=1 WHERE group_id=? AND id=?", $group->id, $period);
    }
    
    public function addGroupPeriod(Group $group, $periodName, $active = 0)
    {
        $this->db->query("INSERT INTO group_period", [
            'group_id' => $group->id,
            'name' => $periodName,
            'active' => $active
        ]);
    }
    
    public function getGroupPeriods(Group $group)
    {
        return $this->db->fetchAll("SELECT * FROM group_period WHERE group_id=?", $group->id);
    }
           
        
}
