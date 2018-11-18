<?php

namespace App\FrontModule\Components\Group\About;

use \Nette\Application\UI\Form;
use App\Model\Manager\GroupManager;
use App\Model\Manager\ClassificationManager;
use Nette\Utils\Validators;

class GroupSettingsForm extends \App\Components\BaseComponent
{
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var ClassificationManager @inject */
    public $classificationManager;
    
    public $scheduleTermsNum = 1;
    
    public function __construct(
        GroupManager $groupManager,
        ClassificationManager $classificationManager
    )
    {
        $this->groupManager = $groupManager;
        $this->classificationManager = $classificationManager;
    }
    
    protected function createComponentForm()
    {

        $colors = $this->groupManager->getColorsSchemes();
        $form = $this->getForm();
        $form->addText('name', 'Název skupiny', null, 255)
             ->setAttribute('placeholder', 'Název skupiny')
             ->setDefaultValue($this->presenter->activeGroup->name)
             ->setRequired('Název musí být vyplněn');
        $form->addRadioList('color','Barevné schéma', $colors)
             ->setDefaultValue($this->presenter->activeGroup->colorSchemeId);
        $form->addText('shortcut', 'Zkratka', null, 3)
             ->setDefaultValue($this->presenter->activeGroup->shortcut);
        $form->addText('subgroup', 'Název podskupiny', null, 100)
             ->setDefaultValue($this->presenter->activeGroup->subgroup);
        $form->addTextArea('description', 'Popis skupiny')
             ->setDefaultValue($this->presenter->activeGroup->description);
        $form->addText('room', 'Místnost', null, 100)
             ->setDefaultValue($this->presenter->activeGroup->room);
        
        //oprávnění
        $privileges = $this->groupManager->getPrivileges($this->presenter->activeGroup->id);
        $form->addCheckbox('PR_DELETE_OWN_MSG', 'Uživatelé mohou smazat své příspěvky')
             ->setDefaultValue($privileges->PR_DELETE_OWN_MSG);
        $form->addCheckbox('PR_CREATE_MSG', 'Uživatelé mohou vytvářet oznámení')
             ->setDefaultValue($privileges->PR_CREATE_MSG);
        $form->addCheckbox('PR_EDIT_OWN_MSG', 'Uživatelé mohou upravovat své příspěvky')
             ->setDefaultValue($privileges->PR_EDIT_OWN_MSG);  
        $form->addCheckbox('PR_SHARE_MSG', 'Příspěvky mohou být volně sdíleny mezi jinými skupinami')
             ->setDefaultValue($privileges->PR_SHARE_MSG);  
        
        //sdílení
        $form->addCheckbox('shareByLink','Povolit sdílení pomocí odkazu', array(1,0))
             ->setDefaultValue($this->presenter->activeGroup->shareByLink);
        $form->addCheckbox('shareByCode','Povolit sdílení pomocí kódu', array(1,0))
             ->setDefaultValue($this->presenter->activeGroup->shareByCode);
        
        $groupPeriods = $this->groupManager->getGroupPeriods($this->presenter->activeGroup);

        $periodItems = [];
        $activePeriod = null;
        foreach($groupPeriods as $period) {
            $periodItems[$period->id] = $period->name;
            if($period->active == 1) {
                $activePeriod = $period;
            }
        }
        
        $form->addRadioList('periods', 'Je aktivní v obdobích', $periodItems)
             ->setDefaultValue($activePeriod->id);
        
        $form->addSubmit('send', 'Uložit nastavení');

        $form->onSuccess[] = [$this, 'processForm'];
        
        return $form;
    }
    
    public function render()
    {
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->sharing = (object)['code' => $this->presenter->activeGroup->shareByCode, 'link' => $this->presenter->activeGroup->shareByLink];
        $schedule = $this->groupManager->getSchedule($this->presenter->activeGroup);
        foreach($schedule as $sch) {
            $scheRet[] = $sch;
        }
        for($i=0; $i<$this->scheduleTermsNum; $i++) {
            $scheRet[] = [];
        }
        $this->template->form = $this["form"];
        $this->template->schedule = $scheRet;
        parent::render();
    }
    
    public function handleAddScheduleRow()
    {
        $scheduleData = $this->presenter->getRequest()->getPost('schedule');
        $error = $this->validateSchedule($scheduleData);
        if(!$error) {
            $this->redrawControl('scheduleAdmin');   
        }
    }
    
    public function handleChangeSharing()
    {
        $shareByLink = $this->presenter->getRequest()->getPost('shareByLink') !== null ? 1 : 0;
        $shareByCode = $this->presenter->getRequest()->getPost('shareByCode') !== null ? 1 : 0;
        $this['form']['shareByLink']->setValue($shareByLink);
        $this['form']['shareByCode']->setValue($shareByCode);
        $this->groupManager->switchSharing($this->presenter->activeGroup, $shareByLink, $shareByCode);
        $this->presenter->activeGroup->shareByCode = $shareByCode;
        $this->presenter->activeGroup->shareByLink = $shareByLink;
        $this->redrawControl('shareSection'); 
        $this->redrawControl('settingForm');
    }
   
    protected function validateSchedule($scheduleData) {
        $persistData = [];
        $error = false;
        if($scheduleData) {
            foreach($scheduleData as $data) {
                if(!empty($data["TIME_FROM"]) && !empty($data["TIME_TO"])) {
                    $timeFrom = explode(':', $data["TIME_FROM"]);
                    $timeTo = explode(':', $data["TIME_TO"]);
                    if(count($timeFrom) === 2 && count($timeTo) === 2
                       && Validators::isNumericInt($timeFrom[0]) && Validators::isInRange($timeFrom[0], array(0,60))
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
        }        
        if($error) {
            $this->presenter->flashMessage($error, 'error');
        } else {
            //$this->groupManager->insertSchedule($persistData, $this->group); 
        }
        return $error;
    }
    
    public function processForm(Form $form, $values) 
    {
        $group = $this->presenter->activeGroup;
        $group->name = trim($values->name);
        $group->description = $values->description;
        $group->room = $values->room;
        $group->colorSchemeId = $values->color;
        $group->subgroup = $values->subgroup;
        $group->shortcut = $values->shortcut;
        $group->room = $values->room;
               
        $this->groupManager->editGroup($group);
        $this->groupManager->setActivePeriod($group, $values->periods);
        
        //sdílení
//        $privileges = [
//            'PR_DELETE_OWN_MSG' => $values['PR_DELETE_OWN_MSG'],
//            'PR_CREATE_MSG' => $values['PR_CREATE_MSG'],
//            'PR_EDIT_OWN_MSG' => $values['PR_EDIT_OWN_MSG'],
//            'PR_SHARE_MSG' => $values['PR_SHARE_MSG']
//        ];
        
        //$this->groupManager->editGroupPrivileges($privileges, $this->group->id);
        //$this->groupManager->switchSharing($group, $values->shareByLink, $values->shareByCode);
        
        //rozvrh
        $scheduleData = $this->presenter->getRequest()->getPost('schedule');
        $error = $this->validateSchedule($scheduleData);
        if(!$error) {
            $this->presenter->flashMessage('Nastavení uloženo', 'success');
        }
        
        $this->presenter->redirect('Group:about', ['id' => $this->presenter->getParameter('id')]);
    }
    
    public function createComponentNewPeriod() 
    {
        $form = $this->getForm();
        $form->addText('name', 'Název období', null, 255)
             ->setAttribute('placeholder', 'Název období')
             ->setRequired('Musíte zadat jméno');
        $form->addSubmit('send', 'Uložit nastavení');

        $form->onSuccess[] = function($form, $values) {
            $this->groupManager->addGroupPeriod($this->presenter->activeGroup, $values->name);
            $form->setValues([], true);
            
            $this->redrawControl('settingForm');
            $this->redrawControl('periodSettings');
            $this->redrawControl('periodForm');
        };
        
        return $form;
    }
}
