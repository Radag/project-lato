<?php
namespace App\Model\Manager;

use Nette;
use App\Model\Entities;
use App\Model\Manager\UserManager;
use App\Model\Manager\PublicActionManager;
use App\Model\Manager\NotificationManager;

class GroupManager extends BaseManager {
 
    const RELATION_OWNER = 'owner';
    const RELATION_TEACHER = 'teacher';
    const RELATION_STUDENT = 'student';
    const RELATION_FIC_STUDENT = 'fictive-student';
           
    /** @var UserManager @inject */
    public $userManager;
    
    /** @var PublicActionManager @inject */
    public $publicActionManager;
    
    /** @var \Dibi\Connection  */
    protected $db;
    
    public function __construct(
        Nette\Security\User $user,
        UserManager $userManager,
        PublicActionManager $publicActionManager,
        \Dibi\Connection $db
    )
    {
        $this->user = $user;
        $this->userManager = $userManager;
        $this->publicActionManager = $publicActionManager;
        $this->db = $db;
    }
    
    
    public function createGroup(Entities\Group $group) 
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
    
    public function addUserToGroup(Entities\Group $group, $userId, $relation, $fromLink = 0, $notificationManager = null)
    {
        $added = true;
        $this->db->begin();
        $row = $this->db->fetch("SELECT * FROM group_user WHERE user_id=? AND group_id=?", $userId, $group->id); 
        if(empty($row)) {
            $this->db->query("INSERT INTO group_user", [
                'user_id' => $userId,
                'group_id' => $group->id,
                'relation_type' => $relation,
                'from_link' => $fromLink
            ]);
        } elseif($row->active == 0) {
            $this->db->query("UPDATE group_user SET", [
                'active' => 1,
                'relation_type' => $relation
            ], "WHERE user_id=? AND group_id=?", $userId, $group->id);
        } else {
            $added = false;
        }
        if($added && $notificationManager && $relation === self::RELATION_STUDENT) {
            $notificationManager->addNotificationNewGroupMember($userId, $group);
        }
        
        $this->db->commit();
        return $added;
    }

    public function setGroupVisited(Entities\User $user, $idGroup)
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
    
    public function getUserGroups(Entities\User $user, $filter = null)
    {
        $return = (object)['groups' => [], 'differentRelations' => false];
        $userGroups = $this->db->fetchAll("SELECT T1.id, T3.main_color, T3.name AS color_scheme, T1.name, T1.shortcut, T1.slug, T2.relation_type, T4.id AS owner_id, T1.subgroup
            FROM `group` T1
            JOIN group_user T2 ON (T1.id=T2.group_id AND T2.user_id=? AND T2.active=1 " . (!isset($filter->relation) ? "" : "AND T2.relation_type='" . $filter->relation . "'") . ")
            JOIN group_user T4 ON (T1.id=T4.group_id AND T4.active=1 AND T4.relation_type='owner')
            JOIN group_scheme T3 ON (T1.group_scheme_id=T3.id) WHERE T1.archived=0 " .
            (isset($filter->skip_ids) ? " AND T1.id NOT IN (" . $filter->skip_ids . ")" : "")
            . "ORDER BY T1.name ASC", $user->id);   
  
        if(!empty($userGroups)) {
            foreach($userGroups as $s) {
                $group = new Entities\Group;
                $group->id = $s->id;
                $group->name = $s->name;
                $group->shortcut = $s->shortcut;
                $group->mainColor = $s->main_color;
                $group->colorScheme = $s->color_scheme;
                $group->slug = $s->slug;
                $group->relation = $s->relation_type;
                $group->subgroup = $s->subgroup;
                $group->owner = new Entities\User();
                $group->owner->id = $s->owner_id;
                $return->groups[$s->id] = $group;
            }
        $relations = [];
        }
        foreach($return->groups as $group) {
            $relations[] = $group->relation;
        }
        $return->differentRelations = !empty($relations) && (count($relations) > 1);
        return $return;
    }  

    public function getUserGroup($groupSlug, Entities\User $user, $isId = false)
    {
        $group = $this->db->fetch("SELECT 
               T1.id,
               T1.slug,
               T1.name,
               T1.shortcut,
               T1.show_deleted,
               T1.description,
               T1.room,
               T1.subgroup,
               T1.group_scheme_id,
               T1.code,
               T2.relation_type,
               T3.code AS scheme_code,
               T3.main_color,
               T5.id AS owner_id,
               T5.name AS owner_name,
               T5.surname AS owner_surname,
               T5.slug AS owner_slug,
               T9.profile_image,
               T5.sex AS owner_sex,
               T6.share_by_link,
               T6.share_by_code,
               T7.hash_code AS public_code,
               T8.id AS period_id,
               T8.name AS period_name,
               T1.pr_user_msg_create,
               T1.pr_share_msg
            FROM `group` T1
            JOIN group_user T2 ON (T1.id = T2.group_id AND T2.user_id=?)
            JOIN group_scheme T3 ON (T1.group_scheme_id = T3.id)
            LEFT JOIN group_user T4 ON (T4.group_id=T1.id AND T4.relation_type='owner')
            JOIN user T5 ON T4.user_id=T5.id
            JOIN user_real T9 ON T9.id=T5.id
            LEFT JOIN group_sharing T6 ON T6.group_id=T1.id
            LEFT JOIN public_actions T7 ON (T7.id = T6.action_id AND T7.active=1)
            LEFT JOIN group_period T8 ON (T8.group_id = T1.id AND T8.active=1)
            WHERE " . ($isId ? "T1.id=?" : "T1.slug=?") . "AND T2.active=1 AND T1.archived=0", $user->id, $groupSlug);
        
        if($group) {
            $owner = new Entities\User();
            $owner->surname = $group->owner_surname;
            $owner->name = $group->owner_name;
            $owner->id = $group->owner_id;
            $owner->slug = $group->owner_slug;
            $owner->profileImage = Entities\User::createProfilePath($group->profile_image, $group->owner_sex);
           
            $groupModel = new Entities\Group();
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
            $groupModel->mainColor = $group->main_color;
            $groupModel->relation = $group->relation_type;
            $groupModel->shareByCode = $group->share_by_code;
            $groupModel->shareByLink = $group->share_by_link;
            $groupModel->interCode = $group->code;
            $groupModel->publicCode = $group->public_code;
            $groupModel->activePeriodId = $group->period_id;
            $groupModel->activePeriodName = $group->period_name;
            $groupModel->pr_user_msg_create = $group->pr_user_msg_create;
            $groupModel->pr_share_msg = $group->pr_share_msg;
            if($groupModel->relation == 'owner') {
                $groupModel->showDeleted = $group->show_deleted;
            }
            
            return $groupModel;   
        } else {
            return null;
        }
    }
    
 
    public function removeUserFromGroup(Entities\Group $group, Entities\User $user)
    {
        $this->db->query("UPDATE group_user SET active=0 WHERE user_id=? AND group_id=?", $user->id, $group->id);
    }
    
    public function archiveGroup($idGroup)
    {
        $this->db->query("UPDATE `group` SET archived=1 WHERE id=?", $idGroup);
    }
    
    public function setDeleted(Entities\Group $group, $deleted)
    {
        $this->db->query("UPDATE `group` SET", [
            'show_deleted' => $deleted
        ], "WHERE id=?", $group->id);
    }
    
    public function editGroup(Entities\Group $group)
    {
        $this->db->query("UPDATE `group` SET", [
            'name' => $group->name,
            'description' => $group->description,
            'room' => $group->room,
            'subgroup' => $group->subgroup,
            'shortcut' => $group->shortcut,
            'group_scheme_id' => $group->colorSchemeId,
            'pr_share_msg' => $group->pr_share_msg,
            'pr_user_msg_create' => $group->pr_user_msg_create
        ], "WHERE id=?", $group->id);
    }
    
    public function editGroupPrivileges($privileges, $idGroup)
    {      
        $this->database->query("UPDATE groups SET ? WHERE ID_GROUP=?", $privileges, $idGroup);
    }
    
    
    public function switchSharing(Entities\Group $group, $stateByLink, $stateByCode) 
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
    
    public function getGroupUsers($idGroup, $filterRelation, $filterIds = null, $exludeId = null)
    {
        if($filterIds !== null) {
             $users = $this->db->fetchAll("SELECT
                T2.id, T2.sex, T2.name, T2.surname, T2.profile_image, T2.slug, T2.is_fictive
            FROM group_user T1
            JOIN vw_all_users T2 ON T1.user_id = T2.id
            WHERE T1.group_id=? AND T1.active=1 AND T1.relation_type IN (?) AND T1.user_id IN (?)
            ORDER BY T2.surname, T2.name ASC", $idGroup, $filterRelation, $filterIds);
        } else {
            if($exludeId !== null) {
                $users = $this->db->fetchAll("SELECT
                T2.id, T2.sex, T2.name, T2.surname, T2.profile_image, T2.slug, T2.is_fictive
                FROM group_user T1
                JOIN vw_all_users T2 ON T1.user_id = T2.id
                WHERE T1.group_id=? AND T1.active=1 AND T1.relation_type IN (?) AND T1.user_id NOT IN (?) 
                ORDER BY T2.surname, T2.name ASC", $idGroup, $filterRelation, $exludeId);
            } else {
                $users = $this->db->fetchAll("SELECT
                T2.id, T2.sex, T2.name, T2.surname, T2.profile_image, T2.slug, T2.is_fictive
                FROM group_user T1
                JOIN vw_all_users T2 ON T1.user_id = T2.id
                WHERE T1.group_id=? AND T1.active=1 AND T1.relation_type IN (?) 
                ORDER BY T2.surname, T2.name ASC", $idGroup, $filterRelation);
            }

        }
         
        $userArray = [];     
        foreach($users as $us) {
            $userArray[] = new Entities\User($us);
             
        }
        return $userArray;
    }
    
    public function insertSchedule($schedule, Entities\Group $group)
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
    
    public function getSchedule(Entities\Group $group)
    {
        return $this->db->fetchAll("SELECT * FROM group_schedule WHERE group_id=?", $group->id);
    }
    
    public function setActivePeriod(Entities\Group $group, $period)
    {
        $this->db->query("UPDATE group_period SET active=0, deactivated_when=NOW() WHERE group_id=? AND active=1", $group->id);
        $this->db->query("UPDATE group_period SET active=1 WHERE group_id=? AND id=?", $group->id, $period);
    }
    
    public function addGroupPeriod(Entities\Group $group, $periodName, $active = 0)
    {
        $this->db->query("INSERT INTO group_period", [
            'group_id' => $group->id,
            'name' => $periodName,
            'active' => $active
        ]);
    }
    
    public function getGroupPeriods(Entities\Group $group)
    {
        return $this->db->fetchAll("SELECT * FROM group_period WHERE group_id=?", $group->id);
    }
    
    public function getProfileUserGroups($profileId, $guestId)
    {
        $return = [];
        $groups = $this->db->fetchAll("SELECT 
                T2.id, T2.name, T2.shortcut, T3.code AS scheme_code, T3.main_color ,T2.slug, T4.id AS is_my FROM
            group_user T1
            JOIN `group` T2 ON T1.group_id=T2.id
            JOIN group_scheme T3 ON T3.id=T2.group_scheme_id
            JOIN group_user T4 ON (T1.group_id=T4.group_id AND T4.user_id=? AND T4.active=1)
            WHERE T1.user_id=? AND T2.archived=0 AND T1.active=1", $profileId, $guestId);
        
        foreach($groups as $group) {
            $groupObject = new Entities\Group($group);
            $groupObject->isMy = empty($group->is_my) ? false : true;
            $return[] = $groupObject;
        }
        return $return;
    }
           
        
    public function getGroupByCode($code) 
    {
        $group = $this->db->fetch("SELECT T1.*, T2.user_id AS owner FROM `group` T1 JOIN group_user T2 ON (T2.group_id=T1.id AND T2.relation_type='owner') WHERE T1.archived=0 AND T1.code=?", $code);
        if($group) {
            $groupEntity = new Entities\Group($group);
            $groupEntity->owner = new Entities\User();
            $groupEntity->owner->id = $group->owner;
            return $groupEntity;
        } else {
            return false;
        }
    }
}
