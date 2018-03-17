<?php
namespace App\Model\Manager;

use App\Model\Entities\Notification;


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
    
    
    public function addNotification(Notification $notification)
    {
        $this->db->query("INSERT INTO notification", [
            'user_id' => $notification->idUser,
            'text' => $notification->text,
            'title' => $notification->title,
            'data' => $notification->data,
            'type' => $notification->type
        ]);
    }
    
    public function addNotificationNewMessage(\App\Model\Entities\Message $message, \App\Model\Entities\Group $group, $groupManager) {
        $notification = new Notification();
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
    
    public function addNotificationNewComment(\App\Model\Entities\Comment $comment) {
        $notification = new Notification();
        $notification->title = "Nový komentář k příspěvku";
        $notification->text = $comment->text;
        $notification->type = self::TYPE_ADD_COMMENT;
        $notification->data = json_encode([
            'commentId' => $comment->id
        ]);
        $notification->idUser = $this->db->fetchSingle("SELECT user_id FROM message WHERE id=?", $comment->idMessage);
        $this->addNotification($notification);    
    }
    
    
    public function getNotifications($user)
    {
        $return = [];
        $messages = $this->db->fetchAll("SELECT 
                    T1.id,
                    T1.text,
                    T1.title
                FROM notification T1 
                WHERE T1.user_id=?
                ORDER BY T1.created_when DESC LIMIT 5", $user->id);
        foreach($messages as $message) {
            $mess = new Notification();
            $mess->title = $message->title;
            $mess->text = $message->text;
            $mess->id = $message->id;
            $return[] = $mess;
        }
        return $return;
    }
    
    public function getUnreadNumber($user)
    {
        return $this->db->fetchSingle("SELECT COUNT(id) FROM notification WHERE user_id=? AND is_read IS NULL", $user->id);
    }
    
    public function setNotificationRead($idUser)
    {
        $data = array('IS_READ' => date('Y-m-d H:i:s'));
        $this->database->query("UPDATE notification SET ? WHERE ID_USER=? AND IS_READ IS NULL", $data, $idUser);
    }

    public function addNotificationNewGroupMember(\App\Model\Entities\User $user, \App\Model\Entities\Group $group) 
    { 
        $notification = new Notification();
        $notification->participant = $user;
        $notification->title = "Nový člen";
        $notification->text = "Do vaší skupiny " . $group->name . " se přidal nový člen " . $user->username . ".";
        $notification->idUser = $group->owner->id;
        $notification->type = self::TYPE_NEW_GROUP_MEMBER;
        
        $allowedUsers = $this->getUserAllowedNotification(array($group->owner), self::TYPE_NEW_GROUP_MEMBER);
        if(!empty($allowedUsers['notification'][$group->owner->id])) {
            $this->addNotification($notification);          
        }

    }
    
    public function addNotificationRemoveFromGroup($users, \App\Model\Entities\Group $group) 
    { 
        $notification = new Notification();
        $notification->title = "Byl jste vyhozen ze skupiny";
        $notification->text = "Byl jste vyhozen ze skupiny " . $group->name . ".";
        $notification->idGroup = $group->id;
        $notification->idType = self::TYPE_REMOVE_FROM_GROUP;
        
        $allowedUsers = $this->getUserAllowedNotification($users, self::TYPE_REMOVE_FROM_GROUP);
        
        foreach($allowedUsers['notification'] as $user) {
            $notification->idUser = $user->id;
            $this->addNotification($notification);          
        }
    }
    
}
