<?php

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\Message;
use App\Model\Entities\Comment;
use App\Model\Entities\User;
use App\Model\Manager\NotificationManager;
use App\Model\Manager\GroupManager;

class CommentsManager extends BaseManager {
     
    /** @var NotificationManager @inject */
    public $notificationManager;
    
    /** @var GroupManager @inject */
    public $groupManager;
    
    public function __construct(
        Nette\Security\User $user,
        NotificationManager $notificationManager,
        GroupManager $groupManager,
        \Dibi\Connection $db
    )
    {
        $this->user = $user;
        $this->notificationManager = $notificationManager;
        $this->groupManager = $groupManager;
        $this->db = $db;
    }
	
    public function createComment(Comment $comment)
    {
        $this->db->query("INSERT INTO comment", [
            'text' => $comment->text,
            'user_id' => $comment->user->id,
            'message_id' => $comment->idMessage,
            'reply_comment_id' => $comment->replyCommentId,
            'created_by' => $this->user->id
        ]);
        $comment->id = $this->db->getInsertId();
        $this->notificationManager->addNotificationNewComment($comment);
    }
	
	
    public function getMessageComments($messageId)
    {
        $commentsData = $this->db->fetchAll("SELECT 
                    T2.id,
					T2.text,
					T2.reply_comment_id,
					T2.created_when, 
					T3.name AS user_name, 
					T3.id AS user_id,
					T4.profile_image,
					T3.sex,
					T3.slug,
					T3.surname AS user_surname
					FROM comment T2
				JOIN user T3 ON T2.user_id=T3.id
				JOIN user_real T4 ON T3.id=T4.id
				WHERE T2.message_id=? ORDER BY T2.reply_comment_id ASC, T2.created_when ASC", $messageId);
        
        $comments = [];
        $count = 0;
        if($commentsData) {
            foreach($commentsData as $comment) {
				$count++;
                $comm = new Comment();
                $user = new User();
                $user->surname = $comment->user_surname;
                $user->name = $comment->user_name;  
                $user->id = $comment->user_id;
                $user->slug = $comment->slug;
                $user->profileImage = User::createProfilePath($comment->profile_image, $comment->sex);

                $comm->text = $comment->text;
                $comm->id = $comment->id;
                $comm->replyCommentId = $comment->reply_comment_id;
                $comm->created = $comment->created_when;
                $comm->dateText = $this->getCreatedText($comm->created);
                $comm->user = $user;
				if($comm->replyCommentId && isset($comments[$comm->replyCommentId])) {
					$comments[$comm->replyCommentId]->replies[] = $comm;
				} else {
					$comments[$comm->id] = $comm;
				}                
            }
        }		

        return (object)['comments' => $comments, 'count' => $count];
    }
    
    public function getComments($groupId, $delete, $filtres)
    {
        $commentsData = $this->db->fetchAll("SELECT 
                    T1.id AS message_id,
                    T2.id,
					T2.reply_comment_id,
                    T2.text, 
                    T2.created_when, 
                    T3.name AS user_name, 
                    T3.id AS user_id,
                    T4.profile_image,
                    T3.sex,
                    T3.slug,
                    T3.surname AS user_surname
                FROM message T1 
                JOIN comment T2 ON T1.id=T2.message_id
                JOIN user T3 ON T2.user_id=T3.id
                JOIN user_real T4 ON T4.id=T3.id
                WHERE T1.group_id=? AND T1.deleted IN (?)
				ORDER BY T2.reply_comment_id ASC, T2.created_when ASC", $groupId, $delete);
        
        $return = [];
        if($commentsData) {
            foreach($commentsData as $comment) {
                $comm = new Comment();
                $user = new User();
                $user->surname = $comment->user_surname;
                $user->name = $comment->user_name;  
                $user->id = $comment->user_id;
                $user->slug = $comment->slug;
                $user->profileImage = User::createProfilePath($comment->profile_image, $comment->sex);

                $comm->text = $comment->text;
                $comm->replyCommentId = $comment->reply_comment_id;
                $comm->id = $comment->id;
                $comm->created = $comment->created_when;
                $comm->dateText = $this->getCreatedText($comm->created);				
                $comm->user = $user;
				if(!isset($return[$comment->message_id])) {
					$return[$comment->message_id] = (object)['comments' => [], 'count' => 0];
				}				
				if($comm->replyCommentId && isset($return[$comment->message_id]->comments[$comm->replyCommentId])) {
					$return[$comment->message_id]->comments[$comm->replyCommentId]->replies[] = $comm;
					$return[$comment->message_id]->count++;
				} else {
					$return[$comment->message_id]->comments[$comm->id] = $comm;
					$return[$comment->message_id]->count++;
				}
                
            }
        }

        return $return;
    }
	
    private function getCreatedText($created) 
	{
		$days = ["den", "dny", "dní"];
		$hours = ["hodina", "hodiny", "hodin"];
		$minutes = ["minuta", "minuty", "minut"];
		
		$since = $created->diff(new \DateTime());
		$totalDates = $since->format('%a');
		if ($totalDates < 1) {
			if($since->h < 1) {
				if($since->i < 1) {
					return "před chvílí";
				} else {
					return $this->getFormatedDate($since->i, $minutes);
				}				
			} else {
				return $this->getFormatedDate($since->h, $hours);
			}
		} elseif ($totalDates < 8) {
			return $this->getFormatedDate($totalDates, $days);		
		} elseif ($since->y < 1) {
			return $created->format("j. n.");
		} else {
			return $created->format("j. n. Y");
		}
	}
	
	private function getFormatedDate($number, $words) 
	{
		if($number == 1) {
			return "1 " . $words[0];
		} elseif($number < 5) {
			return $number . " " . $words[1];
		} else {
			return $number . " " . $words[2];
		}
	}	
}
