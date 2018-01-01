<?php

namespace App\PublicModule\Presenters;

use App\Model\Manager\UserManager;
use App\PublicModule\Components\Authetication\SignInForm\SignInForm;
use App\PublicModule\Components\Authetication\RegisterForm;
use App\PublicModule\Components\Authetication\LostPasswordForm;
use App\Mail\MailManager;
use App\Model\Manager\PublicActionManager;

class HomepagePresenter extends BasePresenter
{
    /**  @var UserManager */
    private $userManager;
    
    /** @var MailManager */
    private $mailManager;
    
    /** @var PublicActionManager */
    private $publicActionManager;
    
    public function __construct(
        UserManager $userManager,
        MailManager $mailManager,
        PublicActionManager $publicActionManager
    )
    {
        $this->userManager = $userManager;
        $this->mailManager = $mailManager;
        $this->publicActionManager = $publicActionManager;
    }
    
    public function createComponentSignInForm()
    {
        return new SignInForm($this->userManager);
    }
    
    public function createComponentRegisterForm()
    {
        return new RegisterForm($this->userManager, $this->mailManager);
    }
    
    protected function createComponentLostPasswordForm()
    {
        return new LostPasswordForm($this->userManager, $this->mailManager);
    }
       
    public function actionDefault()
    {
        if($this->user->isLoggedIn()) {
            $this->redirect(':Front:Homepage:noticeboard');  
        }
    }    
}