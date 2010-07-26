<?php
require_once 'Scisr_TestCase.php';

class FactoryTest extends PHPUnit_Framework_TestCase
{
    public function testInstantiatesClassesWithCollaboratorsInTheConstructor()
    {
        $factory = new Scisr_Operations_Factory(array(
            $stdClass = new stdClass,
            $splQueue = new SplQueue
        ));
        $operation = $factory->getOperation('Scisr_Operations_Dummy');
        $this->assertTrue($operation instanceof Scisr_Operations_Dummy);
    }

    public function testPassesAdditionalParametersInTheConstructor()
    {
        $factory = new Scisr_Operations_Factory(array(
            $stdClass = new stdClass
        ));
        $splQueue = new SplQueue;
        $operation = $factory->getOperation('Scisr_Operations_Dummy', $splQueue);
        $this->assertTrue($operation instanceof Scisr_Operations_Dummy);
    }
}

class Scisr_Operations_Dummy {
    public function __construct(stdClass $a, SplQueue $b) { }
}
