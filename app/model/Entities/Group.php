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
class Group extends AbstractEntity {
    
    public $id = null;
    public $slug = null;
    public $name = null;
    public $email = null;
    public $shortcut = null;
    public $mainColor = null;
    public $colorScheme = null;
    public $colorSchemeId = null;
    public $numberOfStudents = null;
    public $groupType = null;
    public $newMessages = null;
    //teacher a owner jsou ti stejný - skupina má zatím pouze jednoho učitele
    public $teacher = null;
    public $owner = null;
    public $relation = null;
    
    public $description = null;
    public $room = null;
    public $subgroup = null;
    
    public $interCode = null;
    public $publicCode = null;
    public $shareByLink = null;
    public $shareByCode = null;
    public $deleted = null;
    public $classification = null;
    
    public $statistics = null;
    public $userJoin = null;
    
    public $activePeriodId = null;
    public $activePeriodName = null;
    
    
    protected $mapFields = [
        'id' => 'id',
        'name' => 'name',
        'shortcut' => 'shortcut',
        'MAIN_COLOR' => 'mainColor',
        'STUDENTS' => 'numberOfStudents',
        'NEW_MESSAGE' => 'newMessages',
        'URL_ID' => 'urlId'
    ];

}
