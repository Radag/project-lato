<?php
namespace App\Model;

use App\Model\Entities\User;

class LatoSettings 
{
    /** @var User */
    private $activeUser = null;
    
    public function setUser(User $user)
    {
        $this->activeUser = $user;
    }
    
    public function getUser() : User
    {
        return $this->activeUser;
    }
}
