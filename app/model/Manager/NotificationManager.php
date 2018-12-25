<?php
namespace App\Model\Manager;

use App\Model\Entities;

class NotificationManager extends BaseManager
{   
    //přidání zprávy do skupiny
    const TYPE_ADD_GROUP_MSG = 'new_message';
    //přidání komentáře do skupiny
    const TYPE_ADD_COMMENT = 'new_comment';
    //nový člen ve skupině
    const TYPE_NEW_GROUP_MEMBER = 'new_group_member';
    //byl přidán do skupiny
    const TYPE_GROUP_INVITE_MEMBER = 'new_group_invite';
    //byl vyhozem ze skupiny
    const TYPE_REMOVE_FROM_GROUP = 'removed_group_member';
    //byl vyhozem ze skupiny
    const TYPE_LEFT_GROUP = 'left_group';
    //nové hodnocení
    const TYPE_NEW_CLASSIFICATION = 'new_classification';
    
    public $types = [
        self::TYPE_ADD_GROUP_MSG => 'nová zpráva',
        self::TYPE_ADD_COMMENT => 'nový komentář',
        self::TYPE_NEW_GROUP_MEMBER => 'nový člen skupiny',
        self::TYPE_REMOVE_FROM_GROUP => 'byl jste vyhozen ze skupiny',
        self::TYPE_LEFT_GROUP => 'člen odešel ze skupiny',
        self::TYPE_NEW_CLASSIFICATION => 'nová známka',
        self::TYPE_GROUP_INVITE_MEMBER => 'byl jste přidán do skupiny'
    ];
    
    /** @var GroupManager @inject */
    public $groupManager;
    
    public function __construct(
        \Nette\Security\User $user,
        GroupManager $groupManager,
        \Dibi\Connection $db
    )
    {
        $this->user = $user;
        $this->db = $db;
    }
    
    
    public function addNotification(Entities\Notification $notification)
    {
        $this->db->query("INSERT INTO notification", [
            'user_id' => $notification->idUser,
            'text' => $notification->text,
            'title' => $notification->title,
            'data' => $notification->data,
            'type' => $notification->type,
            'trigger_user_id' => $notification->triggerUser
        ]);
        $this->db->query("UPDATE user_real SET has_new_notification=has_new_notification+1 WHERE id=?", $notification->idUser);
    }
    
    public function addNotificationNewMessage(Entities\Message $message, Entities\Group $group, $groupManager) {
        $notification = new Entities\Notification();
        $notification->title = $group->name;
        $notification->text = ""
                . "Příspěvek typu " . $message->getType();
        $notification->type = self::TYPE_ADD_GROUP_MSG;
        $notification->triggerUser = $this->user->id;
        $notification->data = json_encode([
            'groupId' => $group->id,
            'messageId' => $message->id
        ]);
        $users = $groupManager->getGroupUsers($group->id, [GroupManager::RELATION_OWNER, GroupManager::RELATION_STUDENT], null, $message->user->id);

        if(!empty($users)) {
            foreach($users as $user) {
                $notification->idUser = $user->id;
                $this->addNotification($notification);    
            } 
        }
    }
    
    public function addNotificationNewComment(Entities\Comment $comment) {
        $notification = new Entities\Notification();
        $notification->title = "Nový komentář k příspěvku";
        $notification->text = $comment->text;
        $notification->type = self::TYPE_ADD_COMMENT;
        $notification->triggerUser = $this->user->id;
        $notification->data = json_encode([
            'commentId' => $comment->id
        ]);
        $notification->idUser = $this->db->fetchSingle("SELECT user_id FROM message WHERE id=?", $comment->idMessage);
        $this->addNotification($notification);    
    }
    
    /* student se sám přidal do skupiny */
    public function addNotificationNewGroupMember($userId, Entities\Group $group) 
    { 
        $notification = new Entities\Notification();
        $user = $this->db->fetch("SELECT * FROM user WHERE id=?", $userId);
        $notification->title = $group->name;
        $notification->text = "Přidal se " . $user->name .  ' ' . $user->surname . ".";
        $notification->idUser = $group->owner->id;
        $notification->triggerUser = $this->user->id;
        $notification->type = self::TYPE_NEW_GROUP_MEMBER;
        $notification->data = json_encode([
            'userId' => $userId
        ]);
        $this->addNotification($notification);          
    }
    
    /* student byl přidán do skupiny */
    public function addNotificationInviteGroupMember(Entities\Group $group, Entities\User $user) 
    { 
        $notification = new Entities\Notification();
        $notification->title = $group->name;
        $notification->text = "Byl jste přidán do skupiny";
        $notification->idUser = $user->id;
        $notification->triggerUser = $this->user->id;
        $notification->type = self::TYPE_GROUP_INVITE_MEMBER;
        $notification->data = json_encode([
            'groupId' => $group->id
        ]);
        $this->addNotification($notification);          
    }
    
    /* student byl vyhozen ze skupiny */
    public function addWasRemovedFromGroup(Entities\User $user, Entities\Group $group) 
    { 
        $notification = new Entities\Notification();
        $notification->title = $group->name;
        $notification->text = "Byl jste vyhozen ze skupiny";
        $notification->type = self::TYPE_REMOVE_FROM_GROUP;
        $notification->idUser = $user->id;
        $notification->triggerUser = $this->user->id;
        $this->addNotification($notification);          
    }
    
    /* student sám odešel ze skupiny */
    public function addLeftGroup(Entities\User $user, Entities\Group $group) 
    { 
        $notification = new Entities\Notification();
        $notification->title = "Student " . $user->name . ' ' . $user->surname;
        $notification->text = "Odešel ze skupiny " . $group->name . ".";
        $notification->type = self::TYPE_LEFT_GROUP;
        $notification->idUser = $group->owner->id;
        $notification->triggerUser = $this->user->id;
        $this->addNotification($notification);          
    }
    
    
    /* student sám odešel ze skupiny */
    public function addClassification($idUser, Entities\Group $group) 
    { 
        $notification = new Entities\Notification();
        $notification->title = $group->name;
        $notification->text = "Přibylo nové hodnocení.";
        $notification->type = self::TYPE_NEW_CLASSIFICATION;
        $notification->idUser = $idUser;
        $notification->triggerUser = $this->user->id;
        $this->addNotification($notification);          
    }
    
    public function getNotifications(Entities\User $user)
    {
        $return = (object)['new' => [], 'old' => []];
        $notifications = $this->db->fetchAll("SELECT 
                    T1.id,
                    T1.text,
                    T1.title,
                    T1.is_read,
                    T1.created_when,
                    T1.data,
                    T2.profile_image,
                    T2.name,
                    T2.surname,
                    T1.type
                FROM notification T1 
                JOIN vw_all_users T2 ON T1.trigger_user_id=T2.id
                WHERE T1.user_id=?
                ORDER BY IF(T1.is_read IS NULL,NULL,T1.created_when), T1.created_when DESC LIMIT 15", $user->id);

        foreach($notifications as $notif) {
            $notification = new Entities\Notification($notif);
            $notification->triggerUser = new Entities\User();
            $notification->triggerUser->name = $notif->name;
            $notification->triggerUser->surname = $notif->surname;
            $notification->triggerUser->profileImage = $notif->profile_image;
            $notification->type = $this->types[$notif->type];
            if($notification->isRead === null) {
                $return->new[] = $notification;
            } else {
                $return->old[] = $notification;
            }            
        }
        return $return;
    }
        
    public function setAllNotificationRead($idUser, $global = true)
    {
        if($global) {
            $this->db->query("UPDATE user_real SET has_new_notification=0 WHERE id=?", $idUser);
        } else {
            $this->db->query("UPDATE notification SET `is_read`=NOW() WHERE user_id=? AND `is_read` IS NULL", $idUser);
        }
    }
    
    public function getReadNotification($id, Entities\User $user)
    {
        $return = (object)['link' => 'Homepage:noticeboard', 'args' => []];
        $notification = $this->db->fetch("SELECT * FROM notification WHERE user_id=? AND id=?", $user->id, $id);
        if(!empty($notification)) {
            $this->db->query("UPDATE notification SET is_read=NOW() WHERE id=?", $notification->id);
            $data = json_decode($notification->data);
            if($notification->type === self::TYPE_ADD_COMMENT) {
                $comment = $this->db->fetch("SELECT * FROM comment WHERE id=?", $data->commentId);
                if($comment) {
                    $message = $this->db->fetch("SELECT * FROM message WHERE id=?", $comment->message_id);
                    $group = $this->db->fetch("SELECT * FROM `group` WHERE id=?", $message->group_id);
                    $return->link = 'Group:message'; 
                    $return->args = ['id' => $group->slug, 'idMessage' => $message->id];
                }
            } elseif ($notification->type === self::TYPE_ADD_GROUP_MSG) {
                $message = $this->db->fetch("SELECT * FROM message WHERE id=?", $data->messageId);
                if($message) {
                    $group = $this->db->fetch("SELECT * FROM `group` WHERE id=?", $message->group_id);
                    $return->link = 'Group:message'; 
                    $return->args = ['id' => $group->slug, 'idMessage' => $message->id];
                }                
            } elseif ($notification->type === self::TYPE_NEW_GROUP_MEMBER) {
                $user = $this->db->fetch("SELECT * FROM user WHERE id=?", $data->userId);
                if($user) {
                    $return->link = 'Profile:default'; 
                    $return->args = ['id'=>$user->slug];
                }                
            } elseif ($notification->type === self::TYPE_GROUP_INVITE_MEMBER) {                
                $return->link = 'Group:default'; 
                $return->args = ['id'=>$data->groupId];              
            } elseif ($notification->type === self::TYPE_NEW_CLASSIFICATION) {                
                $return->link = 'Homepage:classification';        
            }
        }
        return $return;
    }
}
