<?php

namespace App\FrontModule\Components\NewClassificationForm;

use \Nette\Application\UI\Form;
use App\Model\Entities\Classification;
use App\Model\Manager\ClassificationManager;
use App\Model\Manager\GroupManager;

class UserClassificationForm extends \App\Components\BaseComponent
{
        
    protected $classificationManager;
    protected $groupManager;
    protected $activeGroup;

    protected $grades = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '—' => '—', 'N' => 'N'];

    public function __construct(
        ClassificationManager $classificationManager,
        GroupManager $groupManager,
        \App\Model\Entities\Group $activeGroup)
    {
        $this->groupManager = $groupManager;
        $this->classificationManager = $classificationManager;
        $this->activeGroup = $activeGroup;
        
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addText('name', 'Název hodnocení')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addText('date', 'Datum')
             ->setValue(date("d. m. Y"));
        $form->addHidden('idClassification');
        $form->addSubmit('send', 'Vytvořit');
        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
        
    public function setUsers($users) 
    {       
        $this->template->classificationUsers = $users;
    }
    
    public function processForm(Form $form, $values) 
    {
        $classificationGroup = new \App\Model\Entities\ClassificationGroup();
        $classificationGroup->name = $values->name;
        $classificationGroup->idPeriod = $this->presenter->activeGroup->activePeriodId;
        $classificationGroup->classificationDate = \DateTime::createFromFormat('d. m. Y', $values->date);
        $classificationGroup->group = $this->presenter->activeGroup;
        $users = $this->presenter->getRequest()->getPost('users');
        if(is_array($users)) {
            $classificationGroup->forAll = 0;
            foreach($users as $idUser) {
                $classification = new Classification;
                $classification->idUser = $idUser;
                $classificationGroup->classifications[] = $classification;
            }
        } else {
            $classificationGroup->forAll = 1;
        }
        $id = $this->classificationManager->createGroupClassification($classificationGroup);
        $this->presenter->redirect('Group:usersClassification', ['classificationGroupId' => $id]);
    }
}
