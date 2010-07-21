<?php

/**
 * An operation to change the name of a class method
 */
class Scisr_Operations_RenameMethod
    extends Scisr_Operations_AbstractVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{

    public $oldName;
    public $newName;

    public function __construct(Scisr_ChangeRegistry $changeRegistry, $class, $oldName, $newName)
    {
        parent::__construct($changeRegistry);
        $this->class = $class;
        $this->oldName = $oldName;
        $this->newName = $newName;
    }

    public function register()
    {
        return array(
            T_OBJECT_OPERATOR,
            T_FUNCTION,
            T_PAAMAYIM_NEKUDOTAYIM,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $methodPtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $methodInfo = $tokens[$methodPtr];
        $methodName = $methodInfo['content'];

        // If the method name doesn't match, return early
        if ($methodName != $this->oldName) {
            return;
        }

        if ($tokens[$stackPtr]['code'] == T_PAAMAYIM_NEKUDOTAYIM) {
            $className = $this->resolveStaticSubject($phpcsFile, $stackPtr);
            // If it's the name we're looking for, register it
            if ($className == $this->class) {
                $this->_changeRegistry->addChange(
                    $phpcsFile->getFileName(),
                    $methodInfo['line'],
                    $methodInfo['column'],
                    strlen($methodName),
                    $this->newName
                );
            }
        } else if ($tokens[$stackPtr]['code'] == T_FUNCTION) {
            // If we're inside the correct class, continue
            if (($classDefPtr = array_search(T_CLASS, $methodInfo['conditions'])) !== false) {

                $classPtr = $phpcsFile->findNext(T_STRING, $classDefPtr);
                $classInfo = $tokens[$classPtr];
                if ($classInfo['content'] == $this->class) {
                    $this->_changeRegistry->addChange(
                        $phpcsFile->getFileName(),
                        $methodInfo['line'],
                        $methodInfo['column'],
                        strlen($methodName),
                        $this->newName
                    );
                }
            }
        } else if ($tokens[$stackPtr]['code'] == T_OBJECT_OPERATOR) {

            $varPtr = $phpcsFile->findPrevious(array(T_WHITESPACE), $stackPtr - 1, null, true);
            $type = $this->resolveFullVariableType($varPtr, $phpcsFile, false);

            if ($type == $this->class) {
                $this->_changeRegistry->addChange(
                    $phpcsFile->getFileName(),
                    $methodInfo['line'],
                    $methodInfo['column'],
                    strlen($methodName),
                    $this->newName
                );
            }
        }
    }

}
