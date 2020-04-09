<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\PublicModule\Components\Authetication;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\Service\ReCaptchaService;
use App\Mail\MailManager;


class RegisterForm extends \App\Components\BaseComponent
{
    
    /** @var UserManager */
    public $userManager;
    
    /** @var MailManager */
    public $mailManager;
    
    /** @var ReCaptchaService */
    public $reCaptchaService;
    
    public function __construct(
        UserManager $userManager,
        MailManager $mailManager,
        ReCaptchaService $reCaptchaService
    )
    {
        $this->userManager = $userManager;
        $this->mailManager = $mailManager;
        $this->reCaptchaService = $reCaptchaService;
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm(false);
        $form->addText('email', 'Váš email:')
             ->addRule(Form::EMAIL, 'Email nemá správný formát.')
             ->setRequired('Vložte e-mail');

        $form->addText('name', 'Jméno:')
            ->setRequired('Vložte jméno');
        
        $form->addText('surname', 'Příjmení:')
            ->setRequired('Vložte příjmení');
        
        $form->addPassword('password1', 'Heslo:')
            ->setRequired('Vložte heslo');
        
        $form->addPassword('password2', 'Heslo znovu:')
            ->setRequired('Zopakujte heslo')
            ->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password1']);

        $form->addCheckbox('terms')
             ->setRequired('Potvrďte svůj souhlas s podmínkami užívání');

		$form->addSelect('role', 'Role', ['student' => 'Student', 'teacher' => 'Učitel']);
		
        $form->addSubmit('send', 'Registrovat');
        $form->onValidate[] = function(Form $form, $values) {
            $exist = $this->userManager->getUserByMail($values->email);
            if($exist) {
                $form->addError("Uživatel s tímto emailem je již registrován");
            }
        };
        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }  
    
    public function processForm(Form $form, $values) 
    {
        $code = $this->presenter->getRequest()->getPost('g-recaptcha-response');
        if (!$this->reCaptchaService->checkCode($code)) {
            $this->presenter->flashMessage('Špatná captcha');
            $this->presenter->redirect('this');
        }
        
        try {
            $idUser = $this->userManager->add($values);
            $this->mailManager->sendRegistrationMail($values, $idUser, $this->presenter);
            $this->presenter->flashMessage('Byl jste zaregistrován. Vítejte !', 'success');
            
        } catch (\Exception $ex) {
            $this->presenter->flashMessage($ex->getMessage(), 'error');
            return false;
        }
        $this->presenter->redirect(':Public:Homepage:confirm'); 
        
        
        //$this->presenter->user->login($values->email, $pass);
        
//        if($this->presenter->session->hasSection('redirect')) {    
//            $redirect = $this->presenter->session->getSection('redirect');
//            $link = ':' . $redirect->link . ':' . $redirect->action;
//            $params = $redirect->params;
//            $redirect->remove();
//            $this->presenter->redirect($link, $params);
//        } else {
//            $this->presenter->redirect(':Public:Homepage:confirm');  
//        }
    }
}
