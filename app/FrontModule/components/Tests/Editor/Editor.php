<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;

class Editor extends \App\Components\BaseComponent
{
   
    private $testManager;
    
    public function __construct(TestManager $testManager)
    {
        $this->testManager = $testManager;
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm(false);
        $form->addText('name', 'Název testu')
             ->setRequired('Vložte název testu.'); 
        $form->addSubmit('send', 'Zapsat se');

        $form->onSuccess[] = [$this, 'processForm'];
        
        return $form;
    }
    
    public function render() 
    {
        $this->template->questions = [
            (object)[
                'number' => 1,
                'name' => "",
                'options' => [
                    (object) [
                        'number' => 1,
                        'name' => "",
                        'correct' => false
                    ]
                ]
            ]
        ]; 
        parent::render();      
    }

    public function processForm(Form $form, $values) 
    {
//        $group = $this->groupManager->getGroupByCode($values->code);
//        if(!empty($group)) {
//            $this->groupManager->addUserToGroup($group, $this->presenter->activeUser->id, GroupManager::RELATION_STUDENT, 0, $this->notificationManager);
//            $this->presenter->flashMessage('Byl jste přiřazen do skupiny ' . $group->name, 'success');
//            $this->presenter->redirect(':Front:Group:default', ['id' => $group->slug]);
//        } else {
//            $this->presenter->flashMessage('Zadaný köd není platný.', 'warning');
//        }     
    }
}
