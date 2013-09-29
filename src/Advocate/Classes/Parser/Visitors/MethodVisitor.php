<?php

namespace Advocate\Classes\Parser\Visitors;

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
    
    public function setMethodPattern($methodPattern)
    {
        $this->methodPattern = $methodPattern;
        
        return $this;
    }

    // 

    public function leaveNode(\PHPParser_Node $node)
    {
        if($node instanceof \PHPParser_Node_Stmt_ClassMethod) {
            $methodName = $node->name;
            
            // Match method pattern.
            
            if(
                $methodName == $this->methodPattern
                || $this->patternMatch($this->methodPattern, $methodName)
            ) {
                $this->methodRegistrar->registerMethodMatch($methodName);
            }
        }
    }
    
    /* */
    
    protected function patternMatch($pattern, $subject)
    {
        $parts = explode('*', $pattern);
        
        $new_parts = array();

        foreach ($parts as $part) {
            $new_parts[] = preg_quote($part);
        }

        $pattern = implode('(.*)', $new_parts);
        
        return ($pattern)
                ? preg_match("/^{$pattern}$/ism", $subject)
                : false;
    }
}