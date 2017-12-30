<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Model\Entities;
/**
 * Description of Notification
 *
 * @author Radaq
 */
class Notification extends AbstractEntity {
    
    public $id = null;
    public $title = null;
    public $text = null;
    public $icon = null;
    public $idUser = null;
    public $idGroup = null;
    public $idType = null;
    public $participant = null;
    public $idMessage = null;
    public $type = null;
    
    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getText() {
        return $this->text;
    }

    function getIcon() {
        return $this->icon;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setText($text) {
        $this->text = $text;
    }

    function setIcon($icon) {
        $this->icon = $icon;
    }


}
