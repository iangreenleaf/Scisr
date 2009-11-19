<?php

/**
 * Tracks variable types specified in PHPDoc tags
 */
class Scisr_Operations_TrackCommentVariableTypes
    extends Scisr_Operations_AbstractTrackVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{

    protected function processVar($var, $commentPtr, $phpcsFile)
    {
        if (($varName = $var->getVarName()) === null) {
            $varPtr = $phpcsFile->findNext(T_VARIABLE, $commentPtr);
            $this->setVariableType($varPtr, $var->getContent(), $phpcsFile);
        } else {
            $this->setVariableType($commentPtr, $var->getContent(), $phpcsFile, $varName);
        }
    }

    protected function processParam($param, $commentPtr, $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();
        $funcPtr = $phpcsFile->findNext(T_FUNCTION, $commentPtr);
        Scisr_VariableTypes::registerVariableType(
            $param->getVarName(),
            $param->getType(),
            $phpcsFile->getFileName(),
            $funcPtr
        );
    }

    protected function processReturn($param, $commentToken, $columns)
    {
        //TODO
    }

}
