<?php

/**
 * Tracks type hints given in function parameters
 */
class Scisr_Operations_TrackVariableTypeHints
    extends Scisr_Operations_AbstractVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(
            T_FUNCTION,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $paramsInfo = $phpcsFile->getMethodParameters($stackPtr);
        // Check each param to see if it has a type hint we need to change
        foreach ($paramsInfo as $paramInfo) {
            if (isset($paramInfo['type_hint'])) {
                $classNamePtr = $paramInfo['type_hint'];
                $className = $tokens[$classNamePtr]['content'];
                $varPtr = $paramInfo['var'];
                $this->_variableTypes->setVariableType($varPtr, $className, $phpcsFile, null, $stackPtr);
            }
        }

    }

}
