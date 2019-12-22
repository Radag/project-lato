<?php

namespace App\Model\Entities;

abstract class AbstractEntity
{
    
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
