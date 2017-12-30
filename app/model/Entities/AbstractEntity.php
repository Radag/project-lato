<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Model\Entities;
/**
 * Description of Message
 *
 * @author Radaq
 */
abstract class AbstractEntity  {
    
    protected $mapFields = [];
    
    public function __construct($data = null)
    {
        $this->bindData($data);
    }
    
    protected function bindData($data)
    {
        if(!empty($data) && is_object($data)) {
            foreach($data as $key=>$value) {
                if(isset($this->mapFields[$key])) {
                    $this->{$this->mapFields[$key]} = $value;
                }
            }   
        }
    }
}
