<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{
    
    public function actionDefault()
    {
        if($this->user->isLoggedIn()) {
            $this->redirect(':Front:Stream:groups');
        }
    }
    
    public function actionLogout()
    {
        $this->user->logout(true);
        $this->redirect(':Public:Homepage:default');
    }
    
}
