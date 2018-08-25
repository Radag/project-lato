<?php
namespace App\FrontModule\Components\Group\AddUserForm;

use App\Model\Manager\FictiveUserManager;
use App\Model\Manager\GroupManager;

class CreateForm extends \App\Components\BaseComponent
{
    /** @var FictiveUserManager @inject */
    public $fictiveUserManager;

    /** @var GroupManager */
    protected $groupManager;
    
    public function __construct(
        GroupManager $groupManager,
        FictiveUserManager $fictiveUserManager
    )
    {
        $this->groupManager = $groupManager;
        $this->fictiveUserManager = $fictiveUserManager;
    }
    
    public function createComponentForm()
    {
        $form = $this->getForm();
        $form->addText('name')
             ->setRequired();
        $form->addText('surname')
             ->setRequired();

        $form->onSuccess[] = function($form, $values) {
            $student = new \App\Model\Entities\User();
            $student->name = $values->name;
            $student->surname = $values->surname;
            $userId = $this->fictiveUserManager->createFictiveUser($student, $this->presenter->activeGroup);
            $this->groupManager->addUserToGroup($this->presenter->activeGroup, $userId, GroupManager::RELATION_FIC_STUDENT);
            $this->presenter->flashMessage('Student vytvořen.');    
            $this->redirect('this');
        };
        return $form;
    }
   
}