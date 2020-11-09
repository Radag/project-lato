<?php
namespace App\Model\Manager;


use App\Model\Entities\Test\TestSetup;

class TestSetupManager extends BaseManager 
{     
    
    public function checkOwner(int $setupId) : ?TestSetup
    {
        $sql = "SELECT T1.*, IFNULL(T3.publication_time, T3.created_when) AS publication_time
                FROM test_setup T1 
                JOIN test T2 ON T2.id=T1.test_id 
                JOIN message T3 ON T1.message_id=T3.id
                WHERE T1.id=? AND T2.user_id=?";
        $testData = $this->db->fetch($sql, $setupId, $this->settings->getUser()->id);
        if(!$testData) {
            return null;
        }
        return new TestSetup($testData);
    }
     
    public function getTestSetup(int $id) : ?TestSetup
    {
		$sql = "SELECT T1.*, T2.publication_time FROM test_setup T1 JOIN message T2 ON T1.message_id=T2.id WHERE T1.id=?";
        $setup = $this->db->fetch($sql, $id);
        if(!$setup) {
            return null;
        }
		return new TestSetup($setup);
    }
    
}
