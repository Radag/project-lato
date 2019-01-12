<?php
namespace App\PublicModule\Presenters;

use Nette;
use App\Model\Manager\UserManager;
use App\PublicModule\Components\Authetication\SignInForm\SignInForm;
use App\PublicModule\Components\Authetication\RegisterForm;
use App\PublicModule\Components\Authetication\LostPasswordForm;
use App\Mail\MailManager;
use App\Model\Manager\PublicActionManager;

class BasePresenter extends Nette\Application\UI\Presenter
{
    
    /**  @var UserManager @inject */
    public $userManager;
    
    /** @var MailManager @inject */
    public $mailManager;
    
    /** @var PublicActionManager @inject */
    public $publicActionManager;        
    
    public function startup(): void 
    {        
        if($this->user->isLoggedIn()) {
            $this->redirect(':Front:Homepage:noticeboard');  
        }
        if($this->user->isLoggedIn()) {
            $this->template->isLogged = true;
        } else {
            $this->template->isLogged = false;
        }
        parent::startup();
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
    
    public function flashMessage($message, $type = 'info') {
        parent::flashMessage($message, $type);
        $this->redrawControl('flashMessages');
    }
}
