<?php

namespace App\Model\Entities;

class Comment extends AbstractEntity
{
    
    public $id = null;
    public $idMessage = null;
    public $replyCommentId = null;
    
    public $text = null;
    public $user = null;
    public $created = null;
    public $dateText = "";  
    public $replies = [];
}
