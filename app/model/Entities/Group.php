<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Model\Entities;
/**
 * Description of User
 *
 * @author Radaq
 */
class Group {

    const TYPE_SUBJECT = 1;
    const TYPE_GROUP = 2;
    
    public $id = null;
    public $urlId = null;
    public $name = null;
    public $email = null;
    public $teacher = null;
    public $shortcut = null;
    public $mainColor = null;
    public $numberOfStudents = null;
    public $groupType = null;
    public $newMessages = null;
    public $owner = null;
    public $description = null;
    public $room = null;
    public $subgroup = null;
    
    public $interCode = null;
    public $publicCode = null;
    public $shareByLink = null;
    public $shareByCode = null;
    public $deleted = null;
}
