<?php

namespace App\Presenters;

use \Nette\Application\UI\Form;
use App\Model\UserManager;
use App\Components\Authetication\SignInForm\SignInForm;


class HomepagePresenter extends BasePresenter
{
    /**
     *
     * @var UserManager $userManager
     */
    private $userManager;
    
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }
    
    protected function createComponentSignInForm()
    {
        $form = new SignInForm($this->userManager);
        return $form;
    }
    
    protected function createComponentRegisterForm()
    {
        $form = new Form;
        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Prosím vyplňte své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Prosím vyplňte své heslo.');


        $form->addSubmit('send', 'Registrovat');

        $form->onSuccess[] = [$this, 'registerFormSucceeded'];
        return $form;
    }
    
    public function registerFormSucceeded(Form $form, $values) 
    {
        try {
            $this->userManager->add($values->username, $values->password);
            $this->flashMessage('registrován', 'succes');
        } catch (\Exception $ex) {
            $this->flashMessage($ex->getMessage(), 'error');
        }
        
    }
    
    public function actionDefault()
    {
        if($this->user->isLoggedIn()) {
            $this->redirect('Stream:groups');
        }
    }
    public function actionLogout()
    {
        $this->user->logout(true);
        $this->redirect('Homepage:default');
    }
    
}
