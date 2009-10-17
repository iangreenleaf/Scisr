<?php

class Scisr_Operations_ChangeClassName implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(T_CLASS);
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $tokenInfo = $tokens[$stackPtr];
        Scisr_ChangeRegistry::setChange('TODO', $tokenInfo['line'], $tokenInfo['column'], strlen($tokenInfo['content']), 'TODO');
    }
}
