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
    //byl vyhozem ze skupiny
    const TYPE_REMOVE_FROM_GROUP = 'removed_group_member';
    //byl přidán do skupiny
    const TYPE_NEW_CLASSIFICATION = 'new_classification';
    
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
            'type' => $notification->type
        ]);
        $this->db->query("UPDATE user_real SET has_new_notification=has_new_notification+1 WHERE id=?", $notification->idUser);
    }
    
    public function addNotificationNewMessage(Entities\Message $message, Entities\Group $group, $groupManager) {
        $notification = new Entities\Notification();
        $notification->title = "Nový přispěvek v " . $group->name;
        $notification->text = $message->text;
        $notification->type = self::TYPE_ADD_GROUP_MSG;
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
        $notification->data = json_encode([
            'commentId' => $comment->id
        ]);
        $notification->idUser = $this->db->fetchSingle("SELECT user_id FROM message WHERE id=?", $comment->idMessage);
        $this->addNotification($notification);    
    }
    
    public function addNotificationNewGroupMember($userId, Entities\Group $group) 
    { 
        $notification = new Entities\Notification();
        $user = $this->db->fetch("SELECT * FROM user WHERE id=?", $userId);
        $notification->title = "Nový člen";
        $notification->text = "Do vaší skupiny " . $group->name . " se přidal nový člen " . $user->name .  ' ' . $user->surname . ".";
        $notification->idUser = $group->owner->id;
        $notification->type = self::TYPE_NEW_GROUP_MEMBER;
        $notification->data = json_encode([
            'userId' => $userId
        ]);
        $this->addNotification($notification);          
    }
    
    public function addNotificationRemoveFromGroup(Entities\User $user, Entities\Group $group) 
    { 
        $notification = new Entities\Notification();
        $notification->title = "Student odešel ze skupiny";
        $notification->text = "Student " . $user->name . ' ' . $user->surname . " odešel ze skupiny.";
        $notification->type = self::TYPE_REMOVE_FROM_GROUP;
        $notification->idUser = $group->owner->id;
        $this->addNotification($notification);          
    }
    
    public function getNotifications(Entities\User $user)
    {
        $return = [];
        $notifications = $this->db->fetchAll("SELECT 
                    T1.id,
                    T1.text,
                    T1.title,
                    T1.is_read
                FROM notification T1 
                WHERE T1.user_id=?
                ORDER BY T1.created_when DESC LIMIT 5", $user->id);
        foreach($notifications as $notif) {
            $notification = new Entities\Notification();
            $notification->title = $notif->title;
            $notification->text = $notif->text;
            $notification->id = $notif->id;
            $notification->isRead = $notif->is_read;
            $return[] = $notification;
        }
        return $return;
    }
        
    public function setAllNotificationRead($idUser)
    {
        $this->db->query("UPDATE user_real SET has_new_notification=0 WHERE id=?", $idUser);
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
            }
        }
        return $return;
    }
}
