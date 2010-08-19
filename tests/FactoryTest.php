<?php
require_once 'Scisr_TestCase.php';

class FactoryTest extends PHPUnit_Framework_TestCase
{
    public function testInstantiatesClassesWithCollaboratorsInTheConstructor()
    {
        $factory = new Scisr_Operations_Factory(array(
            $stdClass = new stdClass,
            $arrayObject = new ArrayObject
        ));
        $operation = $factory->getOperation('Scisr_Operations_Dummy');
        $this->assertTrue($operation instanceof Scisr_Operations_Dummy);
    }

    public function testPassesAdditionalParametersInTheConstructor()
    {
        $factory = new Scisr_Operations_Factory(array(
            $stdClass = new stdClass
        ));
        $arrayObject = new ArrayObject;
        $operation = $factory->getOperation('Scisr_Operations_Dummy', $arrayObject);
        $this->assertTrue($operation instanceof Scisr_Operations_Dummy);
    }

    public function testReturnsCollaboratorsForManualWiring()
    {
        $factory = new Scisr_Operations_Factory(array(
            $stdClass = new stdClass
        ));
        $this->assertSame($stdClass, $factory->getCollaborator('stdClass'));
    }
}

class Scisr_Operations_Dummy {
    public function __construct(stdClass $a, ArrayObject $b) { }
}
