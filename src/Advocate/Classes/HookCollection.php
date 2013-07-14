<?php

namespace Advocate\Classes;

class HookCollection
{
    protected $targetNamespace;
    protected $targetClass;
    protected $hooks = array();
    
    public function setTargetNamespace($namespace)
    {
        $this->targetNamespace = $namespace;
    }
        
    public function getTargetNamespace()
    {
        return $this->targetNamespace;
    }
    
    public function setTargetClass($class)
    {
        $this->targetClass = $class;
    }
    
    public function getTargetClass()
    {
        return $this->targetClass;
    }
    
    public function setHook($target_method, $hook_class_namespace, $hook_class, $hook_method)
    {
        if(!isset($this->hooks[$target_method])) {
            $this->hooks[$target_method] = array();
        }
        
        $this->hooks[$target_method][] = new HookItem($hook_class_namespace, $hook_class, $hook_method);
    }
    
    public function getHooks()
    {
        return $this->hooks;
    }
    
    public function hasHooks()
    {
        return (count($this->hooks) > 0);
    }
    
    /* */
    
    public function getClassAsFilename()
    {
        $class_path = $this->targetNamespace.'\\'.$this->targetClass;

        $class_path = str_replace('\\', '/', $class_path);

        return $class_path.'.php';
    }
}




