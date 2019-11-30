<?php
namespace App\PublicModule\Presenters;

use Nette;
use App\Model\Manager\UserManager;
use App\Model\Manager\PublicActionManager;
use App\Service\ReCaptchaService;
use App\PublicModule\Components\Authetication\SignInForm\SignInForm;
use App\PublicModule\Components\Authetication\RegisterForm;
use App\PublicModule\Components\Authetication\LostPasswordForm;
use App\Mail\MailManager;

class BasePresenter extends Nette\Application\UI\Presenter
{
    
    /**  @var UserManager @inject */
    public $userManager;
    
    /** @var MailManager @inject */
    public $mailManager;
    
    /** @var PublicActionManager @inject */
    public $publicActionManager;
    
    /** @var ReCaptchaService @inject */
    public $reCaptchaService;
    
    public $menuItems = [
        '#page-header' => 'Domů',
        '#about' => 'O projektu',
        '#contact' => 'Kontakt'
    ];
    
    public function startup(): void 
    {        
        if($this->user->isLoggedIn() && !$this->isLinkCurrent('Action:*')) {
            //$this->redirect(':Front:Homepage:noticeboard');  
        }
        if($this->user->isLoggedIn()) {
            $this->template->isLogged = true;
        } else {
            $this->template->isLogged = false;
        }
        $this->template->showMainScreen = true;
        $this->template->menuItems = $this->menuItems;
        parent::startup();
    }
    
    public function createComponentSignInForm()
    {
        return new SignInForm($this->userManager);
    }
    
    public function createComponentRegisterForm()
    {
        return new RegisterForm($this->userManager, $this->mailManager, $this->reCaptchaService);
    }
    
    protected function createComponentLostPasswordForm()
    {
        return new LostPasswordForm($this->userManager, $this->mailManager);
    }
    
    public function flashMessage($message, string $type = 'info'): \stdClass 
    {
        $flash = parent::flashMessage($message, $type);
        $this->redrawControl('flashMessages');
        return $flash;
    }
    
    public function handleLogout()
    {
        $this->user->logout(true);
        $this->redirect(':Public:Homepage:default');
    }
}
