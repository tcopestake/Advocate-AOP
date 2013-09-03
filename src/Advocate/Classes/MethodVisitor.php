<?php

namespace Advocate\Classes;

class MethodVisitor extends \PHPParser_NodeVisitorAbstract
{
    protected $methodPattern;
    protected $methodRegistrar;

    public function enterNode(\PHPParser_Node $node)
    {
    }
    
    // 
    
    public function setMethodRegistrar($registrar)
    {
        $this->methodRegistrar = $registrar;
    }
    
    public function setMethodPattern($method_pattern)
    {
        $this->methodPattern = $method_pattern;
    }

    // 

    public function leaveNode(\PHPParser_Node $node)
    {
        if($node instanceof \PHPParser_Node_Stmt_ClassMethod) {
            $method_name = $node->name;
            
            // Match method pattern.
            
            if(
                $method_name == $this->methodPattern
                || preg_match('/'.str_replace('*', '(.*)', $this->methodPattern).'/', $method_name)
            ) {
                $this->methodRegistrar->registerMethodMatch($method_name);
            }
        }
    }
}