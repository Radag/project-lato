<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\GroupSettingsForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\GroupManager;
use App\Model\Entities\Group;
use Nette\Utils\Validators;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class GroupSettingsForm extends Control
{
        
    protected $groupManager;
    protected $group;
    protected $scheduleTermsNum = 1;
    
    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
        
    }
    
    public function setGroup(Group $group)
    {
        $this->group = $group;
        
    }

    protected function createComponentForm()
    {

        $colors = array(
            1 => 'green-bg',
            4 => 'yellow-bg',
            2 => 'blue-bg',
            3 => 'purple-bg',
            5 => 'orange-bg'
        );
        
        $form = new \Nette\Application\UI\Form;
        $form->addText('name', 'Název skupiny', null, 255)
             ->setAttribute('placeholder', 'Název skupiny')
             ->setDefaultValue($this->group->name)
             ->setRequired('Nápis musí být vyplněn');
//        $form->addRadioList('color','Barevné schéma', $colors)
//             ->setDefaultValue(1);
        $form->addText('shortcut', 'Zkratka', null, 3)
             ->setDefaultValue($this->group->shortcut);
        $form->addText('subgroup', 'Název podskupiny', null, 100)
             ->setDefaultValue($this->group->subgroup)
             ->setAttribute('placeholder', 'Název podskupiny (nepovinné)');
        $form->addTextArea('description', 'Popis skupiny')
             ->setDefaultValue($this->group->description)
             ->setAttribute('placeholder', 'Popis skupiny (nepovinné)');
        $form->addText('room', 'Místnost', null, 100)
             ->setDefaultValue($this->group->room)
             ->setAttribute('placeholder', 'Místnost (nepovinné)');
        
        //oprávnění
        $privileges = $this->groupManager->getPrivileges($this->group->id);
        $form->addCheckbox('PR_DELETE_OWN_MSG', 'Uživatelé mohou smazat své příspěvky')
             ->setDefaultValue($privileges->PR_DELETE_OWN_MSG);
        $form->addCheckbox('PR_CREATE_MSG', 'Uživatelé mohou vytvářet oznámení')
             ->setDefaultValue($privileges->PR_CREATE_MSG);
        $form->addCheckbox('PR_EDIT_OWN_MSG', 'Uživatelé mohou upravovat své příspěvky')
             ->setDefaultValue($privileges->PR_EDIT_OWN_MSG);  
        $form->addCheckbox('PR_SHARE_MSG', 'Příspěvky mohou být volně sdíleny mezi jinými skupinami')
             ->setDefaultValue($privileges->PR_SHARE_MSG);  
        
        //sdílení
        $form->addCheckbox('allowSharing','Povolit sdílení', array(1,0))
             ->setDefaultValue(1);
        $form->addCheckbox('shareByCode','Povolit sdílení pomocí kódu', array(1,0))
             ->setDefaultValue($this->group->sharingOn);
        
        
        $form->addSubmit('send', 'Uložit nastavení');

        $form->onSuccess[] = [$this, 'processForm'];
        
        $form->onError[] = function(Form $form) {
            foreach($form->getErrors() as $error) {
                $this->presenter->flashMessage($error, 'error');
            }            
        };
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->activeGroup = $this->group;
        $schedule = $this->groupManager->getSchedule($this->group);
        foreach($schedule as $sch) {
            $scheRet[] = $sch;
        }
        for($i=0; $i<$this->scheduleTermsNum; $i++) {
            $scheRet[] = array();
        }
        
        $template->schedule = $scheRet;
        $template->setFile(__DIR__ . '/GroupSettingsForm.latte');
        $template->render();
    }
    
    public function handleAddScheduleRow()
    {
        $scheduleData = $this->presenter->getRequest()->getPost('schedule');
        $error = $this->validateSchedule($scheduleData);
        if(!$error) {
            $this->redrawControl('scheduleAdmin');   
        }
    }
   
    protected function validateSchedule($scheduleData) {
        $persistData = array();
        $error = false;
        foreach($scheduleData as $data) {
            if(!empty($data["TIME_FROM"]) && !empty($data["TIME_TO"])) {
                $timeFrom = explode(':', $data["TIME_FROM"]);
                $timeTo = explode(':', $data["TIME_TO"]);
                if(Validators::isNumericInt($timeFrom[0]) && Validators::isInRange($timeFrom[0], array(0,60))
                   && Validators::isNumericInt($timeFrom[1]) && Validators::isInRange($timeFrom[1], array(0,60))
                   && Validators::isNumericInt($timeTo[0]) && Validators::isInRange($timeTo[0], array(0,60))
                   && Validators::isNumericInt($timeTo[1]) && Validators::isInRange($timeTo[1], array(0,60))
                ) {
                    $persistData[] = $data;   
                } else {
                    $error = 'Časy musí být ve tvaru 12:55';
                }
            }
        } 
        
        if($error) {
            $this->presenter->flashMessage($error, 'error');
        } else {
            $this->groupManager->insertSchedule($persistData, $this->group); 
        }
        return $error;
    }
    
    public function processForm(Form $form, $values) 
    {
        $this->group->name = trim($values['name']);
        $this->group->description = $values['description'];
        $this->group->room = $values['room'];
        //$this->group->mainColor = $values['color'];
        $this->group->subgroup = $values['subgroup'];
        $this->group->shortcut = $values['shortcut'];
        $this->group->room = $values['room'];
               
        $this->groupManager->editGroup($this->group);
        //sdílení
        $privileges = [
            'PR_DELETE_OWN_MSG' => $values['PR_DELETE_OWN_MSG'],
            'PR_CREATE_MSG' => $values['PR_CREATE_MSG'],
            'PR_EDIT_OWN_MSG' => $values['PR_EDIT_OWN_MSG'],
            'PR_SHARE_MSG' => $values['PR_SHARE_MSG']
        ];
        
        $this->groupManager->editGroupPrivileges($privileges, $this->group->id);
        $this->groupManager->switchSharing($this->group, $values['shareByCode']);
        
        //rozvrh
        
        $scheduleData = $this->presenter->getRequest()->getPost('schedule');
        $error = $this->validateSchedule($scheduleData);
        if(!$error) {
            $this->presenter->flashMessage('Nastavení uloženo', 'success');
        }
        
        $this->redrawControl();
    }
}
