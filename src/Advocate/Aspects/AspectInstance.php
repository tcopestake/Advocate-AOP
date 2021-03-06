<?php

namespace Advocate\Aspects;

class AspectInstance
{
    protected $returnValue;
    protected $exception;
    protected $className;
    protected $methodName;
    protected $arguments = array();
    
    /* */
    
    public function __construct(
        $className,
        $methodName,
        $returnValue = null,
        $exception = null
    ) {
        $this->returnValue = $returnValue;
        $this->exception = $exception;
        $this->className = $className;
        $this->methodName = $methodName;
    }
    
    /* */
    
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
        
        return $this;
    }
    
    public function setReturnValue($value)
    {
        $this->returnValue = $value;
        
        return $this;
    }
    
    public function setException($exception)
    {
        $this->exception = $exception;
        
        return $this;
    }
    
    /* */
    
    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function getReturnValue()
    {
        return $this->returnValue;
    }
    
    public function getException()
    {
        return $this->exception;
    }
    
    public function hasException()
    {
        return ($this->exception instanceof \Exception);
    }
    
    public function getClassName()
    {
        return $this->className;
    }
    
    public function getMethodName()
    {
        return $this->methodName;
    }
}