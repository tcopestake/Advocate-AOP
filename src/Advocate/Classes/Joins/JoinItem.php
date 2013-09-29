<?php

namespace Advocate\Classes\Joins;

class JoinItem
{
    protected $joinNamespace;
    protected $joinClass;
    protected $joinMethod;
    protected $joinStatementNodes;
    
    /* */

    public function __construct($joinClassNamespace, $joinClass, $joinMethod)
    {
        $this->joinNamespace = $joinClassNamespace;
        $this->joinClass = $joinClass;
        $this->joinMethod = $joinMethod;
    }
    
    /* */
    
    public function getNameAsProperty()
    {
        return 'aspect_'.strtolower(preg_replace('/[^0-9a-z]/i', '_', $this->joinNamespace.'\\'.$this->joinClass));
    }
    
    public function getClassNamespaced()
    {
        return $this->joinNamespace.'\\'.$this->joinClass;
    }
    
    /* */
    
    public function setStatements($statements)
    {
        $this->joinStatementNodes = $statements;
    }
    
    /* */
    
    public function getNamespace()
    {
        return $this->joinNamespace;
    }
    
    public function getClass()
    {
        return $this->joinClass;
    }
    
    public function getMethod()
    {
        return $this->joinMethod;
    }
    
    public function getStatements()
    {
        return $this->joinStatementNodes;
    }
}