<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\Material;

/**
 * Description of MaterialManager
 *
 * @author Radaq
 */
class MaterialManager extends Nette\Object{
 
    
    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }
    
   
    public function createMaterial(Material $material)
    {
        $this->database->beginTransaction();
        $this->database->table('material')->insert(array(
                    'TITLE' => $material->title,
                    'ID_MESSAGE' => $material->idMessage
            ));        
        $this->database->commit();
    }
}
