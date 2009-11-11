<?php

/**
 * Tracks variable types specified in PHPDoc tags
 */
class Scisr_Operations_TrackCommentVariableTypes
    extends Scisr_Operations_AbstractVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{

    protected $_phpcsFile;
    protected $_lastParsed = 0;

    public function register()
    {
        return array(
            T_COMMENT,
            T_DOC_COMMENT,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // If we've already parsed this comment, pass
        if ($phpcsFile == $this->_phpcsFile && $stackPtr < $this->_lastParsed) {
            return;
        }

        $this->_phpcsFile = $phpcsFile;
        $tokens = $phpcsFile->getTokens();
        // Find the end of the comment block
        $endPtr = $phpcsFile->findNext($tokens[$stackPtr]['code'], $stackPtr + 1, null, true);
        $this->_lastParsed = $endPtr;
        // Get the whole comment text
        $comment = $phpcsFile->getTokensAsString($stackPtr, $endPtr - $stackPtr + 1);
        $this->parser = new Scisr_CommentParser_ChangeTagValues($comment, $phpcsFile);
        $this->parser->parse();

        $elements = $this->parser->getTagElements();

        foreach ($elements as $tagName => $tagArray) {
            $method = array($this, 'process' . ucfirst($tagName));
            if (is_callable($method)) {
                foreach ($tagArray as $i => $tag) {
                    call_user_func($method, $tag, $stackPtr, $phpcsFile);
                }
            }
        }
    }

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
        Scisr_VariableTypes::registerVariableType($param->getVarName(), $param->getType(), $phpcsFile->getFileName(), $funcPtr);
    }

    protected function processReturn($param, $commentToken, $columns)
    {
        //TODO
    }

}
