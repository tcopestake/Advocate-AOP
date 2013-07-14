<?php

namespace Advocate\Classes;

class HookItem
{
    protected $hookNamespace;
    protected $hookClass;
    protected $hookMethod;
    protected $hookStatementNodes;
    
    protected $uniqid;

    /* */

    public function __construct($hook_class_namespace, $hook_class, $hook_method)
    {
        $this->uniqid = str_replace('.', '', uniqid('', true));
        
        $this->hookNamespace = $hook_class_namespace;
        $this->hookClass = $hook_class;
        $this->hookMethod = $hook_method;
    }
    
    /* */
    
    public function getNameAsProperty()
    {
        return 'aspect_'.$this->uniqid.strtolower(preg_replace('/[^0-9a-z]/i', '_', $this->hookNamespace.'\\'.$this->hookClass));
    }
    
    public function getClassNamespaced()
    {
        return $this->hookNamespace.'\\'.$this->hookClass;
    }
    
    /* */
    
    public function setStatements($statements)
    {
        $this->hookStatementNodes = $statements;
    }
    
    /* */
    
    public function getNamespace()
    {
        return $this->hookNamespace;
    }
    
    public function getClass()
    {
        return $this->hookClass;
    }
    
    public function getMethod()
    {
        return $this->hookMethod;
    }
    
    public function getStatements()
    {
        return $this->hookStatementNodes;
    }
}