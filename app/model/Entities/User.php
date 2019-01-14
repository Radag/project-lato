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
class User extends AbstractEntity {

    public $id = null;
    public $name = null;
    public $surname = null;
    public $username = null;
    public $email = null;
    public $profileImage = null;   
    public $birthday = null;
    public $emailNotification = null;
    public $backgroundImage = null;
    public $slug;
    public $classification = null;
    public $sex = null;
    public $isFictive = null;
    public $avatar = null;
    public $unreadNotifications = 0;
    public $emailVerification = 0;
    
    protected $mapFields = [
        'id' => 'id',
        'user_id' => 'id',
        'surname' => 'surname',
        'name' => 'name',
        'email' => 'email',
        'slug' => 'slug',
        'username' => 'username',
        'birthday' => 'birthday',
        'email_notification' => 'emailNotification',
        'background_image' => 'backgroundImage',
        'sex' => 'sex',
        'is_fictive' => 'isFictive',
        'has_new_notification' => 'unreadNotifications',
        'has_new_private_message' => 'unreadPrivateMessages',
        'email_verify' => 'emailVerification'
    ];
    
    public function __construct($data = null)
    {
        $this->bindData($data);
        $this->bindUser($data);
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
    
    
    public function bindUser($data)
    {
        if(isset($data->profile_image)) {
            $this->avatar = $data->profile_image;
            $this->profileImage = self::createProfilePath($data->profile_image);
        } elseif($data){
            if(isset($data->sex)) {
                $sex = $data->sex;
            } else {
                $sex = null;
            }
            $this->profileImage = self::createProfilePath('', $sex);
        }
    }
}
