<?php

use Tester\Assert;
use App\Service\TestService;


require __DIR__ . '/../bootstrap.php';


$o = new TestService;

Assert::same('Hello Johs', $o->say('John'));
