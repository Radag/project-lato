<?php
namespace App\FrontModule\Components\NewGroupForm;

use \Nette\Application\UI\Form;
use App\Model\Manager\GroupManager;
use App\Model\Manager\NotificationManager;

class JoinGroupForm extends \App\Components\BaseComponent
{
    /** @var GroupManager */
    public $groupManager;
    
    /** @var NotificationManager */
    public $notificationManager;
    
    public function __construct(
        GroupManager $groupManager,
        NotificationManager $notificationManager
    )
    {
        $this->groupManager = $groupManager;
        $this->notificationManager = $notificationManager;        
    }
    
    protected function createComponentForm()
    {
       
        $form = $this->getForm();
        $form->addText('code', 'Kód skupiny')
             ->setRequired('Vložte kód skupiny.');        
        
        $form->addSubmit('send', 'Zapsat se');

        $form->onSuccess[] = [$this, 'processForm'];
        
        $form->onValidate[] = [$this, 'validateCode'];

        return $form;
    }
    
    public function processForm(Form $form, $values) 
    {
        $group = $this->groupManager->getGroupByCode($values->code);
        if(!empty($group)) {
            $this->groupManager->addUserToGroup($group, $this->presenter->activeUser->id, GroupManager::RELATION_STUDENT, 0, $this->notificationManager);
            $this->presenter->flashMessage('Byl jste přiřazen do skupiny ' . $group->name, 'success');
            $this->presenter->redirect(':Front:Group:default', ['id' => $group->slug]);
        } else {
            $this->presenter->flashMessage('Zadaný köd není platný.', 'warning');
        }     
    }
    
    public function validateCode(Form $form, $values)
    {
        $group = $this->groupManager->getGroupByCode($values->code);
        if($group) {
            return true;
        } else {
            $form->addError("Špatný kód");
            return false;
        }
        
    }
}
