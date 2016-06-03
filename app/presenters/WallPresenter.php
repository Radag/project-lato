<?php

namespace App\Presenters;



class WallPresenter extends BasePresenter
{

    public function startup() {
        parent::startup();
        if(!$this->getUser()->isLoggedIn()) {
            $this->redirect('Homepage:default');
        }
    }
    
}
