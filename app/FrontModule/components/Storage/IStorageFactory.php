<?php

namespace App\FrontModule\Components;


interface IStorageFactory
{
    /**
     * @return Storage 
    */
    public function create();
    
}
