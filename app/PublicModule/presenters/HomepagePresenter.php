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
        $form->addText('email', 'Váš email:')
             ->addRule(Form::EMAIL, 'Email nemá správný formát.')
             ->setRequired('Prosím vyplňte váš email.');

        $form->addText('name', 'Jméno:')
            ->setRequired('Prosím vyplňte své jméno.');
        
        $form->addText('surname', 'Příjmení:')
            ->setRequired('Prosím vyplňte své příjmení.');
        
        $form->addPassword('password1', 'Heslo:')
            ->setRequired('Prosím vyplňte své heslo.');
        
        $form->addPassword('password2', 'Heslo znovu:')
            ->setRequired('Prosím napište heslo znovu pro kontrolu.')
            ->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password1']);


        $form->addSubmit('send', 'Registrovat');

        $form->onSuccess[] = [$this, 'registerFormSucceeded'];
        
        $form->onError[] = function (Form $form) {
            foreach($form->getErrors() as $error) {
                $this->flashMessage($error, 'error');
            }
        };
        
        return $form;
    }
    
    public function registerFormSucceeded(Form $form, $values) 
    {
        try {
            $pass = $values->password1;
            $this->userManager->add($values);
            $this->flashMessage('Byl jste zaregistrován. Vítejte !', 'succes');
            
        } catch (\Exception $ex) {
            $this->flashMessage($ex->getMessage(), 'error');
        }
        
        $this->user->login($values->email, $pass);
        if($this->session->hasSection('redirect')) {    
            $redirect = $this->session->getSection('redirect');
            $link = ':' . $redirect->link . ':' . $redirect->action;
            $params = $redirect->params;
            $redirect->remove();
            $this->redirect($link, $params);
        } else {
            $this->redirect(':Front:Homepage:noticeboard');  
        }
    }
    
    public function actionDefault()
    {
        if($this->user->isLoggedIn()) {
          $this->redirect(':Front:Homepage:noticeboard');  
        }
    }    
}