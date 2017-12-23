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
class User {

    public $id = null;
    public $urlId = null;
    public $name = null;
    public $surname = null;
    public $username = null;
    public $email = null;
    public $profileImage = null;   
    public $birthday = null;
    public $emailNotification = null;
    public $backgroundImage = null;

    private $classification = null;
    
    public function __construct($data = null)
    {
        if($data) {
            $this->bindUser($data);
        }
    }
    
    public function getClassification() {
        if($this->classification === null) {
            $this->classification = (object)['items' => array(), 'averageGrade' => null, 'lastDate' => null];
        }
        
        return $this->classification;
    }
    
    public static function createProfilePath($path, $sex = null)
    {
        if(!empty($path)) {
            $profileImage = $path;
        } else {
            if(empty($sex) || $sex == 'M') {
                $profileImage = '/images/default-avatar_man.png';
            } else {
                $profileImage = '/images/default-avatar_woman.png';
            }
        }
        return $profileImage;
    }
    
    
    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getEmail() {
        return $this->email;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    public function bindUser($data)
    {
        isset($data->ID_USER) ? $this->id = $data->ID_USER : true;
        isset($data->SURNAME) ? $this->surname = $data->SURNAME : true;
        isset($data->NAME) ? $this->name = $data->NAME : true;
        isset($data->EMAIL) ? $this->email = $data->EMAIL : true;
        isset($data->URL_ID) ? $this->urlId = $data->URL_ID : true;
        isset($data->USERNAME) ? $this->username = $data->USERNAME : true;
        isset($data->BIRTHDAY) ? $this->birthday = $data->BIRTHDAY : true;
        isset($data->EMAIL_NOTIFICATION) ?  $this->emailNotification = $data->EMAIL_NOTIFICATION : true;
        if(isset($data->PROFILE_IMAGE)) {
            $this->profileImage = self::createProfilePath($data->PROFILE_IMAGE, $data->SEX);
        } else {
            $this->profileImage = self::createProfilePath('', $data->SEX);
        }
        
        isset($data->BACKGROUND_IMAGE) ? $this->backgroundImage = $data->BACKGROUND_IMAGE : true;
    }

}
