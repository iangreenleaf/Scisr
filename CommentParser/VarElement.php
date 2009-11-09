<?php

/**
 * A @var tag element
 *
 * Most of the time this acts like a normal old single element tag. However, we 
 * want to handle the method Zend Studio recommends for type hinting, which is 
 * "@var $name Class". So we catch that case during parsing and handle it 
 * specially.
 */
class Scisr_CommentParser_VarElement extends Scisr_CommentParser_SingleElement {

    /**
     * If true, this var element is in the special form used for Zend type hints
     * @var boolean
     */
    private $_specialForm;
    /**
     * The name of the variable, if given
     * @var string|null
     */
    private $_varName;

    protected function processSubElement($name, $content, $whitespaceBefore)
    {
        // Look for the special form
        if ($name == 'content'
            && $content{0} === '$'
            && preg_match('/(\$\w+)(\s+)(\w+)/', $content, $matches) !== false
        ) {
            $this->_specialForm = true;
            $this->_varName = $matches[1];
            $content = $matches[3];
        }
        parent::processSubElement($name, $content, $whitespaceBefore);
    }

    public function getSubElementValues() {
        // If we've encountered the special form, we have two elements instead of one
        if ($this->_specialForm === true) {
            return array(
                'varName' => $this->_varName,
                'content' => $this->content
            );
        } else {
            return parent::getSubElementValues();
        }
    }

}
