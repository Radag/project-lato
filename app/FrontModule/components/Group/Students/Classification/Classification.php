<?php

namespace App\FrontModule\Components\Group;

use App\Model\Manager\ClassificationManager;
use App\Model\Manager\TestManager;
use App\Model\Manager\TestSetupManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\NotificationManager;
use App\Model\Entities\ClassificationGroup;
use App\FrontModule\Components\Test\ITestSetup;

class Classification extends \App\Components\BaseComponent
{    
    /** @var UserManager */
    public $userManager;
    
    /** @var ClassificationManager */
    public $classificationManager;
    
    /** @var NotificationManager */
    public $notificationManager;
    
    /** @var GroupManager */
    public $groupManager;
    
    /** @var TaskManager */
    public $taskManager;
    
    /** @var ClassificationGroup */
    public $classificationGroup;

	/** @var TestSetupManager */
	public $testSetupManager;

	/** @var TestManager */
	public $testManager;

	/** @var  ITestSetup */
	protected $testSetupForm;

    public $members = null;
    
    public $grades = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '—' => '—', 'N' => 'N'];
    
    /** @persistent **/
    public $sort = 'submit';
    
    public function __construct(
        ClassificationManager $classificationManager,
        UserManager $userManager,
        GroupManager $groupManager,
        NotificationManager $notificationManager,
        TaskManager $taskManager,
		TestSetupManager $testSetupManager,
		TestManager $testManager,
        ITestSetup $testSetupForm
    )
    {
        $this->notificationManager = $notificationManager;
        $this->classificationManager = $classificationManager;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->taskManager = $taskManager;
		$this->testSetupManager = $testSetupManager;
		$this->testManager = $testManager;
		$this->testSetupForm = $testSetupForm;
    } 
        
    public function render()
    {
        $members = $this->getMembers();
        //pokud je to task, tak se načtou všechny odevzdané úkoly
        if($this->classificationGroup && $this->classificationGroup->task) {
            foreach($members as $member) {
                $commit = $this->taskManager->getCommitByUser($this->classificationGroup->task->id, $member->id);
                if($commit) {
                    $commit->isLate = $this->classificationGroup->task->deadline < $commit->created;
                    $member->order = (new \Datetime)->getTimestamp() - $commit->created->getTimestamp();
                }
                $this->classificationGroup->task->commitArray[$member->id] = $commit;
            }
        } else {
            $this->sort = 'name';
        }
      
        if($this->sort === 'submit') {
            $newArray = [];
            foreach($members as $i => $mem) {
                if(isset($mem->order)) {
                    while (isset($newArray[$mem->order])) {
                        $mem->order = $mem->order + 1;
                    }
                    $newArray[$mem->order] = $mem;
                } else {
                    $newArray[$i] = $mem;
                }
            }        
            krsort($newArray);
            $members = $newArray;
        }

		$this->template->activeUser = $this->presenter->activeUser;
		$this->template->classificationGroup = $this->classificationGroup;
		$this->template->members = $members;
        if (!$this->classificationGroup || $this->classificationGroup->type === 'test') {
        	$this->setTemplateName('ClassificationTest');
			parent::render();
		} else {
			parent::render();
		}
    }
    
    public function getMembers()
    {
        if($this->members === null) {
            if(!$this->classificationGroup || $this->classificationGroup->forAll === 1) {
                //pokud je to hodnocení v rámci tasku, tak se berou všichni studenti ze skupiny
                $students = null;
            } else {
                //jinak se berou jenom založená hodnocení
                $students = [];
                foreach($this->classificationGroup->classifications as $cla) {
                    $students[] = $cla->user->id;
                }
            }
            
            $this->members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id, [GroupManager::RELATION_STUDENT, GroupManager::RELATION_FIC_STUDENT], $students);        
        }
        return $this->members;
    }
    
    public function setGroupClassification($id)
    {
        if($this->classificationManager->canEditClassificationGroup($id, $this->presenter->activeUser)) {
            $this->classificationGroup = $this->classificationManager->getGroupClassification($id);
        } else {
            $this->presenter->redirect('Group:default');
        }
    }

	public function setTest($testSetupId)
	{
		$testSetup = $this->testSetupManager->checkOwner($testSetupId);
		if($testSetup) {
			if($testSetup->classificationGroupId) {
				$this->classificationGroup = $this->classificationManager->getGroupClassification($testSetup->classificationGroupId);
			}
			$this['testSetupForm']->setDefault($testSetupId);
			$this->template->test = $this->testManager->getTestForUser($testSetup->testId);
			$this->template->testSetup = $testSetup;
			$this->template->testResults = $this->testManager->getUsersResults($testSetup);
		} else {
			$this->presenter->redirect('Group:default');
		}
	}
    
    public function handleChangeSort($sort)
    {
        if($sort === 'submit') {
            $this->sort = 'submit';
        } else {
            $this->sort = 'name';
        }
        $this->redrawControl();
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();   
        $form->addHidden('date');
        $form->addHidden('name');
        $form->addHidden('members');
        $form->addSubmit('send', 'Uložit');
        if($this->classificationGroup) {
			$form->addHidden('id')
				->setValue($this->classificationGroup->id);
		}

         
        //vtyvoření známkování pro všechny členy hodnocení
        foreach($this->getMembers() as $member) {
            $form->addSelect('grade' . $member->id, 'Známka', $this->grades)
                 ->setDefaultValue('—');
            $form->addTextArea('notice' . $member->id, 'Poznámka')
                 ->setAttribute('placeholder', 'Vložit poznámku k hodnocení ...');
        }        
        
        //vložení už zadaných známek
		if($this->classificationGroup) {
			foreach ($this->classificationGroup->classifications as $classification) {
				$form->setValues([
					'notice' . $classification->user->id => empty($classification->notice) ? null : $classification->notice,
					'grade' . $classification->user->id => empty($classification->grade) ? '—' : $classification->grade
				]);
			}
		}
        
        $form->onSuccess[] = function(\Nette\Application\UI\Form $form, $values) {
            if(!$this->classificationManager->canEditClassificationGroup($values->id, $this->presenter->activeUser)) {
                throw \InvalidArgumentException();
            }            
            $classificationGroup = $this->classificationManager->getGroupClassification($values->id);            
            $vals = [];
            foreach($values as $key=>$val) {
                if(strpos($key, 'grade') === 0) {
                    $vals[substr($key, 5)]['grade'] = $val;
                }
                
                if(strpos($key, 'notice') === 0) {
                    $vals[substr($key, 6)]['notice'] = $val;
                }
            }
            foreach($vals as $idUser=>$val) {
                $classification = new \App\Model\Entities\Classification();
                $classification->grade = $val['grade'];
                $classification->notice = $val['notice'];
                $classification->idClassificationGroup = $values->id;
                $classification->group = $this->presenter->activeGroup;
                $classification->idUser = $idUser;
                $classification->idPeriod = $this->presenter->activeGroup->activePeriodId;
                $this->classificationManager->createClassification($classificationGroup, $classification);
                if(($val['grade'] && $val['grade'] !== '—') || !empty($val['notice'])) {
                    if(isset($classificationGroup->classifications[$idUser])) {
                        if($val['grade'] != $classificationGroup->classifications[$idUser]->grade ||  $val['notice'] != $classificationGroup->classifications[$idUser]->notice) {                        
                            $this->notificationManager->addClassification($idUser, $this->presenter->activeGroup);
                        }
                    } else {
                        $this->notificationManager->addClassification($idUser, $this->presenter->activeGroup);
                    }
                }                
            }
            
            $this->presenter->flashMessage('Uloženo', 'success');
            $this->presenter->redirect('Group:usersList');
        };
        
        return $form;
    }
            
    public function createComponentEditClassGroupForm()
    {
        $form = $this->getForm();
        $form->addText('name', 'Název')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addText('date', 'Datum')
             ->setAttribute('placeholder', 'Datum (nepovinné)');
        $form->addHidden('id');
        $form->addSubmit('send', 'Potvrdit');

        if($this->classificationGroup) {
			$form->setDefaults([
				'name' => $this->classificationGroup->name,
				'date' => $this->classificationGroup->classificationDate ? $this->classificationGroup->classificationDate->format('d. m. Y') : null,
				'id' => $this->classificationGroup->id
			]);
		}
        
        $form->onSuccess[] = function($form, $values) {
            if(!$this->classificationManager->canEditClassificationGroup($values->id, $this->presenter->activeUser)) {
                throw \InvalidArgumentException();
            }
            if(!empty($values->date)) {
                $values->date = \DateTime::createFromFormat('d. m. Y', $values->date);
            } else {
                $values->date = null;
            }
            $this->classificationGroup->classificationDate = $values->date;
            $this->classificationGroup->name = $values->name;
            $this->classificationManager->updateClassificationGroup($values);
            $this->presenter->payload->reloadModal = true;
            $this->redrawControl('classification-header');
            $this->redrawControl('classification-form');
        };
        return $form;
    }

	public function createComponentTestSetupForm()
	{
		return $this->testSetupForm->create();
	}

	public function handleDeleteTestFilling($id)
	{
		$this->testManager->deleteFilling($id);
		$this->presenter->redirect('this');
	}
}
