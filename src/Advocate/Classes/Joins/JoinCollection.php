<?php

namespace Advocate\Classes\Joins;

class JoinCollection
{
    protected $targetNamespace;
    protected $targetClass;
    protected $targetMethod;
    protected $joins = array();
    
    public function setTargetNamespace($namespace)
    {
        $this->targetNamespace = $namespace;
        
        return $this;
    }
        
    public function getTargetNamespace()
    {
        return $this->targetNamespace;
    }
    
    public function setTargetClass($class)
    {
        $this->targetClass = $class;
        
        return $this;
    }
    
    public function getTargetClass()
    {
        return $this->targetClass;
    }
    
    public function setTargetMethod($method)
    {
        $this->targetMethod = $method;
        
        return $this;
    }
    
    public function getTargetMethod()
    {
        return $this->targetMethod;
    }
    
    public function setJoin(
        $targetMethod,
        $joinClassNamespace,
        $joinClass,
        $joinMethod,
        $isBefore,
        $isAfter
    ) {
        if(!isset($this->joins[$targetMethod])) {
            $this->joins[$targetMethod] = array();
        }
        
        $this->joins[$targetMethod][] = new JoinItem(
            $joinClassNamespace,
            $joinClass,
            $joinMethod,
            $isBefore,
            $isAfter
        );
    }
    
    public function getJoins()
    {
        return $this->joins;
    }
    
    public function hasJoins()
    {
        return (count($this->joins) > 0);
    }
    
    /* */
    
    public function getClassAsFilename()
    {
        $class_path = $this->targetNamespace.'\\'.$this->targetClass;

        $class_path = str_replace('\\', '/', $class_path);

        return $class_path.'.php';
    }
}




