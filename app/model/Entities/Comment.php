<?php

namespace App\Model\Entities;

class Comment extends AbstractEntity
{
    
    public $id = null;
    public $idMessage = null;
    
    public $text = null;
    public $user = null;
    public $created = null;
    public $sinceStart = null;
    
}
