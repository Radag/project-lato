<?php

namespace App\PublicModule\Presenters;

use App\Model\Manager\GroupManager;
use App\Model\Manager\PublicActionManager;
use \Nette\Application\UI\Form;

class ActionPresenter extends BasePresenter
{

    /** @var GroupManager @inject */
    public $groupManager;
        
    public function actionDefault($id)
    {
        $action = $this->publicActionManager->getActionType($id);
        switch ($action) {
            case PublicActionManager::ACTION_ADD_TO_GROUP :
                $this->addToGroup($id);
                break;
            case PublicActionManager::ACTION_MAIL_VALIDATION :
                $this->validateMail();
                break;
            case PublicActionManager::ACTION_LOST_PASS :
                $this->newPassword($action);
                break;
        }
    }    
    
    protected function addToGroup($id) 
    {
        $action = $this->publicActionManager->getGroupSharingAction($id);
        if($this->user->isLoggedIn()) {
            if($this->groupManager->isUserInGroup($this->user->id, $action->group_id)) {
                $this->template->isInGroup = true;
                $this->flashMessage('Již jste členem této skupiny.');
                $this->redirect(':Front:Group:default', array('id'=>$action->slug));
            } else {
                $this->template->isInGroup = false;
                $this->template->action = $action;
            }
        } else {
            $redirectSection = $this->getSession()->getSection('redirect');
            $redirectSection->params = $this->getParameters();
            $redirectSection->action = $this->getAction();
            $redirectSection->link = $this->getRequest()->getPresenterName();
            $this->redirect('Homepage:default');
        }
    }
    
    protected function validateMail() 
    {
        $email =  base64_decode($this->request->getParameter('email'));
        $idUser = $this->request->getParameter('idUser');
        $user = $this->userManager->getUserByMail($email);
        if($user && $user->id == $idUser) {
            $this->userManager->verifyEmail($user, $email);
            $this->presenter->flashMessage('Email byl úspěšně ověřen', 'success');
            $this->userManager->freeLogin = true;
            $this->presenter->user->login($email, null);
            $this->redirect(':Front:Homepage:confirm-success');
        } else {
            $this->presenter->flashMessage('Špatný link', 'error');
            $this->redirect(':Public:Homepage:default');
        }
        
    }
    
    protected function newPassword() 
    {
        if($this->request->getMethod() == 'POST') {
            $secret = $this->request->getPost('secret');
            $email = $this->request->getPost('email');
        } else {
            $secret = $this->request->getParameter('secret');
            $email = $this->request->getParameter('email');
        }

        $user = $this->userManager->getUserByMail(base64_decode($email), $secret);
        
        if($user) {  
            $this['newPasswordForm']->setValues(array('secret' => $secret, 'email' => $email));
            $this->setView('newPassword');
        } else {
            throw new \ErrorException('CHYBA');
        }
        
    }
    
    protected function createComponentNewPasswordForm()
    {
        $form = new Form;
        $form->addPassword('password1', 'Heslo:')
            ->setRequired('Prosím vyplňte své heslo.');        
        $form->addPassword('password2', 'Heslo znovu:')
            ->setRequired('Prosím napište heslo znovu pro kontrolu.')
            ->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password1']);
        $form->addHidden('secret');
        $form->addHidden('email');

        $form->addSubmit('send', 'Odeslat');

        $form->onSuccess[] = [$this, 'newPasswordFormSucceeded'];
        
        $form->onError[] = function (Form $form) {
            foreach($form->getErrors() as $error) {
                $this->flashMessage($error, 'error');
            }
        };
        
        return $form;
    }
    
    public function newPasswordFormSucceeded(Form $form, $values) 
    {
        $secret = $values->secret;
        $email = $values->email;
        $user = $this->userManager->getUserByMail(base64_decode($email), $secret);
        if($user) {
            $this->userManager->updatePassword($user, $values->password2);
        }
        
        $this->flashMessage('Heslo bylo nastaveno', 'success');
        $this->redirect(':Public:Homepage:default');  
    }
    
    public function handleAddToGroup($id)
    {
        $action = $this->publicActionManager->getGroupSharingAction($id);
        if(!empty($action) && $action->action_type === PublicActionManager::ACTION_ADD_TO_GROUP) {
            if($this->user->isLoggedIn()) {
                $group = new \App\Model\Entities\Group($action);
                $group->id = $action->group_id;
                $this->groupManager->addUserToGroup($group, $this->user->id, GroupManager::RELATION_STUDENT, 1);
                $this->flashMessage('Byl jste přidán do skupiny.');
                $this->redirect(':Front:Group:default', ['id'=>$action->slug]);
            } else {
                $redirectSection = $this->getSession()->getSection('redirect');
                $redirectSection->params = $this->getParameters();
                $redirectSection->action = $this->getAction();
                $redirectSection->link = $this->getRequest()->getPresenterName();
                $this->flashMessage('Musíte se první přihlásit nebo zaregistrovat.');
                $this->redirect('Homepage:default');
            }
        } else {
           
        }
    }
}