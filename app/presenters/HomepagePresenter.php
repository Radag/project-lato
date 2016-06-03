<?php

namespace App\Presenters;

use \Nette\Application\UI\Form;
use App\Model\UserManager;


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
        $form = new Form;
        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Prosím vyplňte své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Prosím vyplňte své heslo.');

        $form->addCheckbox('remember', 'Zůstat přihlášen');

        $form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = [$this, 'signInFormSucceeded'];
        return $form;
    }
    
    public function signInFormSucceeded(Form $form, $values) 
    {

        try {
            $this->user->setAuthenticator($this->userManager);
            $this->user->login($values->username, $values->password);
            $this->flashMessage('přihlášen', 'succes');
        } catch (\Exception $ex) {
            $this->flashMessage($ex->getMessage(), 'error');
        }
        $this->redirect('Wall:default');
        
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
    
    public function actionLogout()
    {
        $this->user->logout(true);
        $this->redirect('Homepage:default');
    }
    
}
