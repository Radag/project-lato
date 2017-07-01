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
    
    const RELATION_TEACHER = 1;
    const RELATION_STUDENT = 2;
    const RELATION_OWNER = 3;
    
    public $id = null;
    public $urlId = null;
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
    
    
    private $mapFields = array(
        'ID_GROUP' => 'id',
        'NAME' => 'name',
        'SHORTCUT' => 'shortcut',
        'MAIN_COLOR' => 'mainColor',
        'STUDENTS' => 'numberOfStudents',
        'NEW_MESSAGE' => 'newMessages',
        'URL_ID' => 'urlId'
    );
    
    
    public function __construct($data = null) {
        if(!empty($data) && is_object($data)) {
            foreach($data as $key=>$value) {
                if(isset($this->mapFields[$key])) {
                    $this->{$this->mapFields[$key]} = $value;
                }
            }   
        }
    }
}
