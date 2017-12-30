<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Model\Entities;
/**
 * Description of Message
 *
 * @author Radaq
 */
class Message extends AbstractEntity {
    
    const TYPE_NOTICE = 'notice';
    const TYPE_MATERIALS = 'material';
    const TYPE_TASK = 'task';
    
    public $id = null;
    public $text = null;
    public $user = null;
    public $created = null;
    public $idGroup = null;
    public $attachments = null;
    public $followed = null;
    public $priority = null;
    public $deleted = null;
    public $type = null;
    public $task;
    public $top = null;
    public $title = null;
    

}
