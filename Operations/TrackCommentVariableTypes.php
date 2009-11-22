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

    protected function processReturn($return, $commentPtr, $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();
        $funcPtr = $phpcsFile->findNext(T_FUNCTION, $commentPtr);
        $funcNamePtr = $phpcsFile->findNext(T_STRING, $funcPtr);
        // We identify this as a function type by the () on the end
        $funcName = $tokens[$funcNamePtr]['content'] . '()';
        // If we're a class method, qualify the function name
        if ($classDefPtr = array_search(T_CLASS, $tokens[$funcNamePtr]['conditions'])) {
            $classPtr = $phpcsFile->findNext(T_STRING, $classDefPtr);
        }
        $this->setVariableType($funcNamePtr, $return->getValue(), $phpcsFile, $funcName);
    }

}
