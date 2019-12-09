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
    
    public function createTestSetup(Entities\Test\TestSetup $testSetup) : int {
        $this->db->query("INSERT INTO test_setup", [
            'test_id' => $testSetup->testId,            
            'group_id' => $testSetup->groupId,          
            'time_limit' => $testSetup->timeLimit,          
            'questions_count' => $testSetup->questionsCount,          
            'number_of_repetitions' => $testSetup->numberOfRepetitions,
            'publication_time' => $testSetup->publicationTime,
            'classification_group_id' => $testSetup->classificationGroupId,
            'random_sort' => $testSetup->randomSort,
            'deadline' => $testSetup->deadline
        ]);
        return $this->db->getInsertId();
    }
    
    public function updateTestSetup(Entities\Test\TestSetup $testSetup) {
        $this->db->query("UPDATE test_setup SET ", [   
            'time_limit' => $testSetup->timeLimit,          
            'questions_count' => $testSetup->questionsCount,          
            'number_of_repetitions' => $testSetup->numberOfRepetitions,
            'publication_time' => $testSetup->publicationTime,
            'classification_group_id' => $testSetup->classificationGroupId,
            'random_sort' => $testSetup->randomSort,
            'deadline' => $testSetup->deadline
        ], "WHERE id=?", $testSetup->id);
    }
    
    public function getGroupTests($groupId) {
        $userId = $this->settings->getUser()->id;
        
        $testsData = $this->db->fetchAll("SELECT 
                T1.*, 
                T2.time_limit,
                CONCAT(T3.name, ' ', T3.surname) as author, 
                T2.created_at, 
                T2.publication_time, 
                T2.id AS setup_id,
                T2.deadline,
                T2.classification_group_id,
                T2.number_of_repetitions,
                T2.filled_per_students,
                T2.filled_totaly,
                T2.average_percent,
                T4.id AS group_id,
                T4.slug AS group_slug,
                T5.grade
            FROM test T1 
            JOIN test_setup T2 ON T1.id=T2.test_id
            JOIN `user` T3 ON T1.user_id=T3.id
            JOIN `group` T4 ON T2.group_id=T4.id 
            LEFT JOIN classification T5 ON (T5.classification_group_id=T2.classification_group_id AND T5.user_id=?)
            WHERE T2.group_id=? AND (T2.publication_time IS NULL OR T2.publication_time < NOW())", $userId, $groupId);
        
        $stats = $this->db->fetchAll("SELECT 
                        T1.setup_id,
                        AVG(T1.percent) AS percent_avg,
                        COUNT(T1.id) AS filled_count
                FROM test_filling T1
                JOIN test_setup T2 ON T2.id=T1.setup_id
                WHERE T1.user_id=? AND T2.group_id=?
                GROUP BY T1.setup_id", $userId, $groupId);
        
        $myStats = [];
        foreach($stats as $stat) {
            $myStats[$stat->setup_id] = $stat;
        }
        
        $tests = [];
        foreach($testsData as $testData) {
            $test = new Test($testData);
            $test->created = $testData->created_at;
            $test->setup = new Entities\Test\TestSetup();
            $test->setup->id = $testData->setup_id;
            $test->setup->deadline = $testData->deadline;
            $test->setup->classificationGroupId = $testData->classification_group_id;
            $test->setup->numberOfRepetitions = $testData->number_of_repetitions;
            $test->setup->isCreator = $testData->user_id == $this->settings->getUser()->id;
            $test->setup->group = new Entities\Group;
            $test->setup->group->id = $testData->group_id;
            $test->setup->group->slug = $testData->group_slug;
            if($test->setup->deadline) {
                $test->setup->timeLeft = (new \DateTime())->diff($test->setup->deadline);
            }
            $test->summary = new Entities\Test\TestSummary();
            if($test->setup->isCreator) {                
                $test->summary->studentsCount = $testData->filled_per_students;
                $test->summary->studentsCountTotal = $testData->filled_totaly;
                $test->summary->studentsPercent = $testData->average_percent;
            } else {
                if(isset($myStats[$test->setup->id])) {
                    $test->summary->filledCount = $myStats[$test->setup->id]->filled_count;
                    $test->summary->percent = $myStats[$test->setup->id]->percent_avg;
                }
                $test->summary->grade = $testData->grade;
            }
            $tests[] = $test;
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
        $tests = $this->db->fetchAll("SELECT * FROM test WHERE user_id=? ORDER BY created_at DESC", $user->id);
        $return = [];
        foreach($tests as $test) {
            $return[] = new Test($test);
        }
        return $return;
    }
    
    public function getTestForOwner($id, $userId, $questions = null) : ?Test {
        $testData = $this->db->fetch("SELECT * FROM test WHERE id=? AND user_id=?", $id, $userId);
        if(empty($testData)) {
            return null;
        }
        return $this->getTest(new Test($testData));
    }
    
    public function getTestForUser($id, $questions = null) : ?Test {
        $testData = $this->db->fetch("SELECT * FROM test WHERE id=?", $id);
        if(empty($testData)) {
            return null;
        }
        return $this->getTest(new Test($testData));
    }
    
    private function getTest($test, $questions = null) : ?Test {
        if($questions === false) {
            return $test;
        }
        
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
            $questionsData = $this->db->fetchAll($sql, $test->id);
        } else {
            $sql .= " AND T1.id IN (?)";
            $questionsData = $this->db->fetchAll($sql, $test->id, $questions);
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
                $optionObject->isCorrect = $question->option_is_correct == 1;
                $test->questions[$question->id]->options[] = $optionObject;
            }
        }
        return $test;
    }
    
    public function createFilling(Filling $filling) : int
    {
        $this->db->query("INSERT INTO test_filling", [        
            'setup_id' => $filling->setupId,
            'user_id' => $filling->userId,   
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
        $filling->setup = $this->getTestSetup($filling->setupId);
        return $filling;
    }
    
    public function getAnswers(int $fillingId) : array {
        $answersData =  $this->db->fetchAll("SELECT * FROM test_filling_answer WHERE test_filling_id=?", $fillingId);
        $answers = [];
        foreach($answersData as $answer)
        {
            $answerObject = new Answer($answer);
            $answerObject->isCorrect = $answer->is_correct == 1;
            $answerObject->answer = json_decode($answer->answer);
            $answers[$answerObject->questionId] = $answerObject;
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
    
    public function getTestSetup(int $id)
    {
        $setup = $this->db->fetch("SELECT * FROM test_setup WHERE id=?", $id);
        $testSetup = new Entities\Test\TestSetup($setup);
        return $testSetup;
    }
    
    public function deleteGroupTest(int $setupId)
    {
        $this->db->query("DELETE FROM test_setup WHERE id=?", $setupId);
    }
    
    public function deleteTest(int $testId)
    {
        $this->db->query("DELETE FROM test WHERE id=?", $testId);
    }
    
}
