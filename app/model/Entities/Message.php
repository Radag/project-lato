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
class Message {
    
    const TYPE_NOTICE = 1;
    const TYPE_MATERIAL = 2;
    const TYPE_TASK = 3;
    const TYPE_HOMEWORK = 4;
    
    public $id = null;
    public $text = null;
    public $user = null;
    public $created = null;
    public $idGroup = null;
    public $attachments = null;
    public $followed = null;
    public $priority = null;
    public $deleted = null;
    public $idType = null;
    public $task;
    public $links = null;
    
    function getId() {
        return $this->id;
    }

    function getText() {
        return $this->text;
    }

    function getUser() {
        return $this->user;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setText($text) {
        $this->text = $text;
    }

    function setUser($user) {
        $this->user = $user;
    }

    function getCreated() {
        return $this->created;
    }

    function setCreated($created) {
        $this->created = $created;
    }

}
