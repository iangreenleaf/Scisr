<?php

abstract class Scisr_Operations_AbstractVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{
    protected $_variableTypes;

    public function __construct(Scisr_Operations_VariableTypes $variableTypes)
    {
        $this->_variableTypes = $variableTypes;
    }
}
