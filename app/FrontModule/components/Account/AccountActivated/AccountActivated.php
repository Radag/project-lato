<?php

namespace App\FrontModule\Components\Account;

use App\Model\Manager\UserManager;

class AccountActivated extends \App\Components\BaseComponent
{

    /** @var UserManager $userManager */
    public $userManager;

    public static $avatarList = [
        "/images/default_avatars/male_1.png",
	"/images/default_avatars/female_2.png",
	"/images/default_avatars/male_3.png",
	"/images/default_avatars/female_4.png",
	"/images/default_avatars/male_5.png",
	"/images/default_avatars/female_6.png"
    ];
    
    public function __construct(
        UserManager $userManager
    )
    {
        $this->userManager = $userManager;
    }
    
    public function render() {
       
        parent::render();
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        
        $form->addRadioList('avatar', 'avatar', self::$avatarList)
             ->setRequired();
        
        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            $file = ['fullPath' => "https://www.lato.cz" . self::$avatarList[$values->avatar]];
            $this->userManager->assignProfileImage($this->presenter->activeUser, $file);
            $this->presenter->redirect(':Front:Homepage:noticeboard');
        };
        return $form;        
    }
    
}
