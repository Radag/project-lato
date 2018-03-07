<?php
namespace App\FrontModule\Components\Group\AddUserForm;



interface ImportFormFactory
{
    /**
     * @return ImportForm
    */
    public function create();
    
}
