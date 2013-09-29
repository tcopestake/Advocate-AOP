<?php

namespace Advocate\Classes\Joins;

class JoinItem
{
    protected $joinNamespace;
    protected $joinClass;
    protected $joinMethod;
    protected $joinStatementNodes;
    
    protected $joinIsBefore;
    protected $joinIsAfter;
    
    /* */

    public function __construct(
        $joinClassNamespace,
        $joinClass,
        $joinMethod,
        $isBefore,
        $isAfter
    ) {
        $this->joinNamespace = $joinClassNamespace;
        $this->joinClass = $joinClass;
        $this->joinMethod = $joinMethod;
        
        $this->joinIsBefore = $isBefore;
        $this->joinIsAfter = $isAfter;
    }
    
    /* */
    
    public function isBefore()
    {
        return $this->joinIsBefore ? true : false;
    }
    
    public function isAfter()
    {
        return $this->joinIsAfter ? true : false;
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