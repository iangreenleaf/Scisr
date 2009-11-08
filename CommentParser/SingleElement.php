<?php

class Scisr_CommentParser_SingleElement extends PHP_CodeSniffer_CommentParser_SingleElement
{
    protected function processSubElement($name, $content, $whitespace) {
        parent::processSubElement($name, $content, $whitespace);
    }

    /**
     * Get all subelement values
     *
     * @return array a list of subelement names => contents
     */
    public function getSubElementValues()
    {
        return array('content' => $this->content);

    }//end getSubElements()
}
