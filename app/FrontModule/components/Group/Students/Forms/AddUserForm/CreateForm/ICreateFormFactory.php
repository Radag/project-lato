<?php
namespace App\FrontModule\Components\Group\AddUserForm;



interface ICreateFormFactory
{
    /**
     * @return CreateForm
    */
    public function create();
    
}
