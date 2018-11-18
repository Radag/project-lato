<?php
namespace App\FrontModule\Presenters;

use App\FrontModule\Components\SearchForm;
use App\FrontModule\Components\Account\IAccountActivated;
use App\FrontModule\Components\IStorage;
use App\FrontModule\Components\IClassification;
use App\FrontModule\Components\INoticeboard;

class HomepagePresenter extends BasePresenter
{
    /** @var INoticeboard @inject */
    public $noticeboard; 
    
    /** @var IClassification @inject */
    public $classification; 

    /** @var IStorage @inject */
    public $storage; 
    
    /** @var IAccountActivated @inject */
    public $accountActivated; 

    public function actionDefault()
    {
        $this->redirect(':Front:Homepage:noticeboard');
    }
    
    public function actionNoticeboard()
    {        
        $this['topPanel']->setTitle('Nástěnka');
    }
    
    public function actionClassification()
    {
        $this['topPanel']->setTitle('Klasifikace'); 
    }
    
    public function actionStorage()
    {
        $this['topPanel']->setTitle('Úložiště');
    }
    
    protected function createComponentSearchForm()
    {
        return new SearchForm($this->searchManager);
    }
    
    protected function createComponentActivatedForm()
    {
        return $this->accountActivated->create();
    }
    
    protected function createComponentStorage()
    {
        return $this->storage->create();
    }
    
    protected function createComponentClassification()
    {
        return $this->classification->create();
    }
    
    protected function createComponentNoticeboard()
    {
        return $this->noticeboard->create();
    }
    
    /*     
    public function actionNotices()
    {
        $this['topPanel']->setTitle('Poznámky');
        $this->template->notices = $this->noticeManager->getNotices($this->activeUser, 10);
    }
    
    public function actionTimetable()
    {
        $this['topPanel']->setTitle('Rozvrh');
        $groups = [];//$this->groupManager->getUserGroups($this->activeUser, true);
        $schedule = [];//$this->scheduleManger->getWeekSchedule($groups);
        
        $maxHour = 0;
        $minHour = 24;
        foreach($schedule as $day) {
            foreach($day as $hour) {
                if($maxHour < $hour->TIME_FROM->format("%H")) {
                    $maxHour = $hour->TIME_FROM->format("%H");
                }
                if($minHour > $hour->TIME_FROM->format("%H")) {
                    $minHour = $hour->TIME_FROM->format("%H");
                }
            }
        }
        
        $this->template->maxHour = $maxHour;
        $this->template->minHour = $minHour;
        $this->template->schedule = $schedule;
        $this->template->days = $this->days;
        
    }     
     */
}
