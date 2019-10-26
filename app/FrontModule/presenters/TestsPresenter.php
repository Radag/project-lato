<?php
namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Test\ITestsList;
use App\FrontModule\Components\Test\IEditor;
use App\FrontModule\Components\Test\ITestFilling;
use App\FrontModule\Components\Test\ITestStart;

class TestsPresenter extends BasePresenter
{
    
    /** @var ITestsList @inject */
    public $testList; 
    
    /** @var IEditor @inject */
    public $editor; 
    
    /** @var ITestFilling @inject */
    public $testFilling;
    
    /** @var ITestStart @inject */
    public $testStart;

    public function actionList()
    {
        $this['topPanel']->setTitle('Testy');
    }
    
    public function actionEditor($id)
    {
        if(!empty($id)) {
            $this['topPanel']->setTitle('Úprava testu');
            $this['editor']->setId($id);
        } else {
            $this['topPanel']->setTitle('Nový test');
        }
    }
    
    public function actionFilling($id)
    {
        $this['testFilling']->setId($id);
        $this['topPanel']->setTitle('Test');
    }
    
    public function actionStart($id, $groupId = null)
    {
        $this['testStart']->setId($id, $groupId);
        $this['topPanel']->setTitle('Test');
    }
       
    protected function createComponentList()
    {
        return $this->testList->create();
    }
    
    protected function createComponentEditor()
    {
        return $this->editor->create();
    }
    
    protected function createComponentTestFilling()
    {
        return $this->testFilling->create();
    }
    
    protected function createComponentTestStart()
    {
        return $this->testStart->create();
    }
}
