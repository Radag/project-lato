<?php

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Archive\IGroups;

class ArchivePresenter extends BasePresenter
{    
    /** @var IGroups @inject */
    public $groups;
                    
    public function actionDefault()
    {
        $this['topPanel']->setTitle('Archiv skupin');
    }
    
    protected function createComponentGroups()
    {
        return $this->groups->create();
    }
}
