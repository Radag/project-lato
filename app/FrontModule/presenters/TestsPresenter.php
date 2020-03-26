<?php
namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Test\ITestsList;
use App\FrontModule\Components\Test\IEditor;

class TestsPresenter extends BasePresenter
{
    /** @var ITestsList @inject */
    public $testList; 
    
    /** @var IEditor @inject */
    public $editor; 
    
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
       
    protected function createComponentList()
    {
        return $this->testList->create();
    }
    
    protected function createComponentEditor()
    {
        return $this->editor->create();
    }
}
