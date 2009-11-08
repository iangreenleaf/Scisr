<?php

class Scisr_CommentParser_ParameterElement extends PHP_CodeSniffer_CommentParser_ParameterElement
{

    /**
     * Get all subelement values
     *
     * @return array a list of subelement names => contents
     */
    public function getSubElementValues()
    {
        return array(
            'type' => $this->getType(),
            'varName' => $this->getVarName(),
            'comment' => $this->getComment(),
        );

    }//end getSubElements()
}
