<?php

namespace App\PublicModule\Presenters;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\PublicModule\Components\Authetication\SignInForm\SignInForm;
use App\Mail\MailManager;
use App\Model\Manager\PublicActionManager;

class HomepagePresenter extends BasePresenter
{
    /**
     *
     * @var UserManager $userManager
     */
    private $userManager;
    
    /**
     *
     * @var MailManager $mailManager
     */
    private $mailManager;
    
    /**
     * @var PublicActionManager $publicActionManager
     */
    private $publicActionManager;
    
    public function __construct(UserManager $userManager, MailManager $mailManager, PublicActionManager $publicActionManager)
    {
        $this->userManager = $userManager;
        $this->mailManager = $mailManager;
        $this->publicActionManager = $publicActionManager;
    }
    
    protected function createComponentSignInForm()
    {
        $form = new SignInForm($this->userManager);
        return $form;
    }
    
    protected function createComponentLostPasswordForm()
    {
        $form = new Form;
        $form->addText('email', 'Váš email:')
             ->setAttribute('placeholder', 'Váš email')
             ->addRule(Form::EMAIL, 'Email nemá správný formát.')
             ->setRequired('Prosím vyplňte váš email.');

        $form->addSubmit('send', 'Odeslat');

        $form->onSuccess[] = [$this, 'lostPasswordFormSucceeded'];
        
        $form->onError[] = function (Form $form) {
            foreach($form->getErrors() as $error) {
                $this->flashMessage($error, 'error');
            }
        };
        
        return $form;
    }
    
    protected function createComponentRegisterForm()
    {
        $form = new Form;
        $form->addText('email', 'Váš email:')
             ->setAttribute('placeholder', 'Váš email')
             ->addRule(Form::EMAIL, 'Email nemá správný formát.')
             ->setRequired('Prosím vyplňte váš email.');

        $form->addText('name', 'Jméno:')
             ->setAttribute('placeholder', 'Jméno')
            ->setRequired('Prosím vyplňte své jméno.');
        
        $form->addText('surname', 'Příjmení:')
             ->setAttribute('placeholder', 'Příjmení')
            ->setRequired('Prosím vyplňte své příjmení.');
        
        $form->addPassword('password1', 'Heslo:')
             ->setAttribute('placeholder', 'Heslo')
            ->setRequired('Prosím vyplňte své heslo.');
        
        $form->addPassword('password2', 'Heslo znovu:')
             ->setAttribute('placeholder', 'Heslo znovu')
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
    
    public function lostPasswordFormSucceeded(Form $form, $values) 
    {
        $email = $values->email;
        $this->mailManager->sendLostPasswordMail($email, $this);
        $this->flashMessage('Pokud byl email zaregistrován, byl na něj odeslán email.', 'success');
        $this->redirect(':Public:Homepage:default');  
    }
    
    public function registerFormSucceeded(Form $form, $values) 
    {
        try {
            $pass = $values->password1;
            $idUser = $this->userManager->add($values);
            
            $this->mailManager->sendRegistrationMail($values, $idUser, $this);
            
            $this->flashMessage('Byl jste zaregistrován. Vítejte !', 'success');
            
        } catch (\Exception $ex) {
            $this->flashMessage($ex->getMessage(), 'error');
            return false;
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