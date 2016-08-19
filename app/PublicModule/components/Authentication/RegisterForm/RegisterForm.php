<?php

namespace App\PublicModule\Components\Authetication\RegisterForm;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;


/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class RegisterForm extends Nette\Object
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

    protected function create()
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
    
    public function processForm($form, $values)
    {
        try {
            $this->userManager->add($values->username, $values->password);
            $this->flashMessage('Byl jste zaregistrován. Vítejte !', 'succes');
            $this->getPresenter()->user->login($values->username, $values->password);
            if($this->presenter->session->hasSection('redirect')) {    
                $redirect = $this->presenter->session->getSection('redirect');
                $link = ':' . $redirect->link . ':' . $redirect->action;
                $params = $redirect->params;
                $redirect->remove();
                $this->presenter->redirect($link, $params);
            } else {
                $this->presenter->redirect(':Front:Stream:groups');  
            }
        } catch (\Exception $ex) {
            $this->flashMessage($ex->getMessage(), 'error');
        }
    }
}
