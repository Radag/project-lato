<?php
namespace App\Model\Manager;


use App\Model\Entities\Test\Test;
use App\Model\Entities\Test\Question;
use App\Model\Entities\Test\Option;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Answer;
use App\Model\Entities;

class TestManager extends BaseManager 
{     
    
    public function createTest(Test $test, Entities\User $user) : int {
        $this->db->query("INSERT INTO test", [
            'user_id' => $user->id,            
            'name' => $test->name
        ]);
        return $this->db->getInsertId();
    }
    
    public function createGroupTest($testId, $groupId) : int {
        $this->db->query("INSERT INTO group_test", [
            'test_id' => $testId,            
            'group_id' => $groupId,
            'time_limit' => 60 * 3
        ]);
        return $this->db->getInsertId();
    }
    
    public function getGroupTests($groupId) {
        $testsData = $this->db->fetchAll("SELECT T1.*, T2.time_limit, CONCAT(T3.name, ' ', T3.surname) as author 
            FROM test T1 JOIN group_test T2 ON T1.id=T2.test_id
            JOIN user T3 ON T1.user_id=T3.id
            WHERE T2.group_id=?", $groupId);
        $tests = [];
        foreach($testsData as $testData) {
            $tests[] = new Test($testData);
        }
        return $tests;
    }
    
    public function updateTest(Test $test) {
        $this->db->query("UPDATE test SET", [
            'name' => $test->name
        ], "WHERE id=?", $test->id);
    }
    
    public function insertQuestion(Question $question) : int {
        $this->db->query("INSERT INTO test_question", [        
            'test_id' => $question->testId,
            'question' => $question->question,            
            'number' => $question->number  
        ]);
        return $this->db->getInsertId();
    }
    
    public function updateQuestion(Question $question, int $testId) {
        $this->db->query("UPDATE test_question SET ", [
            'question' => $question->question,            
            'number' => $question->number  
        ], "WHERE id=? AND test_id=?", $question->id, $testId);
    }
    
    public function deleteQuestion(int $questionId, int $testId) {
        $this->db->query("DELETE FROM test_question WHERE id=? AND test_id=?", $questionId, $testId);
    }
    
    public function insertOption(Option $option) : int {
        $this->db->query("INSERT INTO test_question_option", [        
            'question_id' => $option->questionId,
            'name' => $option->name,                      
            'number' => $option->number,
            'is_correct' => $option->isCorrect ? 1 : 0
        ]);
        return $this->db->getInsertId();
    }
    
    public function updateOption(Option $option, $testId) {
        $this->db->query("UPDATE test_question_option T1
          JOIN test_question T2 ON T1.question_id = T2.id
          SET ", [
            'T1.name' => $option->name,                      
            'T1.number' => $option->number,              
            'T1.is_correct' => $option->isCorrect ? 1 : 0
        ], "WHERE T1.id=? AND T2.test_id=?", $option->id, $testId);
    }
    
    public function deleteOption(int $optionId, int $testId) {
        $this->db->query("DELETE T1 FROM test_question_option T1
            JOIN test_question T2 ON T1.question_id = T2.id
            WHERE T1.id=? AND T2.test_id=?", $optionId, $testId);
    }
    
    public function getTests(Entities\User $user): array {
        $tests = $this->db->fetchAll("SELECT * FROM test WHERE user_id=?", $user->id);
        $return = [];
        foreach($tests as $test) {
            $return[] = new Test($test);
        }
        return $return;
    }
    
    public function getTest($id, $userId, $questions = null) : ?Test {
        $testData = $this->db->fetch("SELECT * FROM test WHERE id=? AND user_id=?", $id, $userId);
        if(empty($testData)) {
            return null;
        }
        $test = new Test($testData);
        $sql = "SELECT
                    T1.id,
                    T1.question,
                    T1.number,
                    T1.type,
                    T2.id AS option_id,
                    T2.name AS option_name,
                    T2.is_correct AS option_is_correct,
                    T2.number AS option_number
                FROM test_question T1 
                LEFT JOIN test_question_option T2 ON T1.id=T2.question_id
                WHERE T1.test_id=?";
        
        if($questions === null) {
            $questionsData = $this->db->fetchAll($sql, $id);
        } else {
            $sql .= " AND T1.id IN (?)";
            $questionsData = $this->db->fetchAll($sql, $id, $questions);
        }
        
        foreach($questionsData as $question) {
            if(empty($test->questions[$question->id])) {
                $test->questions[$question->id] = new Question($question);
            }
            if($question->option_id) {
                $optionObject = new Option();
                $optionObject->id = $question->option_id;
                $optionObject->name = $question->option_name;
                $optionObject->number = $question->option_number;
                $optionObject->isCorrect = $question->option_is_correct == 1 ? true : false;
                $test->questions[$question->id]->options[] = $optionObject;
            }
        }
        return $test;
    }
    
    public function createFilling(Filling $filling) : int
    {
        $this->db->query("INSERT INTO test_filling", [        
            'test_id' => $filling->testId,
            'user_id' => $filling->userId,            
            'group_id' => $filling->groupId,
            'questions' => json_encode($filling->questions),
            'questions_count' => $filling->questionsCount
        ]);
        return $this->db->getInsertId();
    }
    
    public function getFilling(int $fillingId) : Filling {
        $fillingData =  $this->db->fetch("SELECT * FROM test_filling WHERE id=?", $fillingId);
        $filling = new Filling($fillingData);
        $filling->isFinished = $fillingData->is_finished === 1;
        $filling->answers = $this->getAnswers($fillingId);
        $filling->questions = json_decode($fillingData->questions);
        return $filling;
    }
    
    public function getAnswers(int $fillingId) : array {
        $answersData =  $this->db->fetchAll("SELECT * FROM test_filling_answer WHERE test_filling_id=?", $fillingId);
        $answers = [];
        foreach($answersData as $answer)
        {
            $answers[] = new Answer($answer);
        }  
        return $answers;
    }
    
    public function updateFilling(Filling $filling) {
        $this->db->query("UPDATE test_filling SET ", [
            'is_finished' => $filling->isFinished,
            'correct_count' => $filling->correctCount,
            'percent' => $filling->percent
        ], "WHERE id=?", $filling->id);
    }
    
    public function saveAnswer(Answer $answer) : int
    {
        $this->db->query("INSERT INTO test_filling_answer", [        
            'test_filling_id' => $answer->fillingId,
            'question_id' => $answer->questionId,
            'answer' => json_encode($answer->answer),
            'is_correct' => $answer->isCorrect ? 1 : 0
        ]);
        return $this->db->getInsertId();
    }
    
    public function clearTestAnswers(int $fillingId)
    {
        $this->db->query("DELETE FROM test_filling_answer WHERE test_filling_id=?", $fillingId);
    }
    
    public function getTestSetup(int $testId, int $groupId)
    {
        $setup = $this->db->fetch("SELECT * FROM group_test WHERE group_id=? AND test_id=?", $groupId, $testId);
        $testSetup = new Entities\Test\TestSetup($setup);
        return $testSetup;
    }
    
}
