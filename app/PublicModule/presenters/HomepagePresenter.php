<?php

namespace App\PublicModule\Presenters;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\PublicModule\Components\Authetication\SignInForm\SignInForm;


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
            $pass = $values->password;
            $this->userManager->add($values->username, $values->password);
            $this->flashMessage('Byl jste zaregistrován. Vítejte !', 'succes');
            
        } catch (\Exception $ex) {
            $this->flashMessage($ex->getMessage(), 'error');
        }
        
        $this->user->login($values->username, $pass);
        if($this->session->hasSection('redirect')) {    
            $redirect = $this->session->getSection('redirect');
            $link = ':' . $redirect->link . ':' . $redirect->action;
            $params = $redirect->params;
            $redirect->remove();
            $this->redirect($link, $params);
        } else {
            $this->redirect(':Front:Stream:groups');  
        }
        
    }
    
    public function actionDefault()
    {
        if($this->user->isLoggedIn()) {
          $this->redirect(':Front:Stream:groups');  
        }
    }    
}