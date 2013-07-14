<?php

namespace Advocate;

class AOP
{
    protected $interceptedAutoloaders = array();
    
    protected $hooks = array();
    
    protected $parser;
    
    protected $workingDirectory;
    
    // 
    
    public function getWorkingDirectory()
    {
        return $this->workingDirectory();
    }
    
    // 

    public function __construct($directory)
    {
        $this->parser = new Classes\Parser;
        
        $this->workingDirectory = $directory;
    }
    
    /* */
    
    public function init()
    {
        $this->loadMapping();
        
        // Load mapping.
        
        
        // 
        
        $this->interceptedAutoloaders = spl_autoload_functions();

        foreach($this->interceptedAutoloaders as $autoloader) {
            spl_autoload_unregister($autoloader);
        }
        
        // 
        
        $aop_container = $this;

        $aop_autoloader = function($class) use ($aop_container)
        {
            // Parse file location.

            $class_path = $this->findClassFile($class);
            
            if(file_exists(($class_location = $this->workingDirectory.'/'.$class_path))) {
                
                /*
                 * Load the compiled code if it's newer than
                 * both the target class and the aspect map.
                 * 
                 */
                
                if(
                    file_exists(($compiled_location = $this->workingDirectory.'/compiled/'.$class_path))
                    && filemtime($compiled_location) > filemtime($class_location)
                    && filemtime($compiled_location) > $this->mapLastModified
                ) {
                    $include_path = $compiled_location;
                } else {
                    $code = file_get_contents($class_location);

                    $this->parser->setCode($code);

                    // Look for method hook matches.

                    $hooks = $aop_container->matchHooks($class);
                }
            }

            // 

            if(isset($hooks) && $hooks instanceof Classes\HookCollection && $hooks->hasHooks()) {
                $include_path = $aop_container->compileHooks($compiled_location, $hooks);
            }
            
            if(isset($include_path) && file_exists($include_path)) {
                require($include_path);
            } else {
                foreach($aop_container->getAutoloaders() as $autoloader) {
                    call_user_func_array($autoloader, array($class));

                    if(class_exists($class, false)) {
                        break;
                    }
                }
            }
        };

        // 

        spl_autoload_register($aop_autoloader, true, true);
    }
    
    /* */
    
    
    
    protected function loadMapping()
    {
        $this->mapLocation = $this->workingDirectory.'/aop/mapping.php';
        
        if(!file_exists($this->mapLocation)) {
            return false;
        }
        
        // 
        
        $this->mapLastModified = filemtime($this->mapLocation);
        
        // 
        
        $map_array = require($this->mapLocation);
        
        foreach($map_array as $map) {
            $class_pattern = &$map[0];
            
            if($class_pattern{0} == '\\') {
                $class_pattern = substr($class_pattern, 1);
            }

            $this->hooks[] = $map;
        }
    }
    
    /* */
    
    public function getAutoloaders()
    {
        return $this->interceptedAutoloaders;
    }

    /* */
    
    public function compileHooks($compile_path, $hooks)
    {
        return $this->parser->compileHooks($compile_path, $hooks);
    }
    
    /* */
    
    /* */
    
    public function matchHooks($class)
    {
        list($class_namespace, $class_name) = $this->parseClassNamespace($class);

        // 

        $hook_collection = new Classes\HookCollection;
        
        $hook_collection->setTargetNamespace($class_namespace);
        $hook_collection->setTargetClass($class_name);

        foreach($this->hooks as $hook) {
            list($class_pattern, $method_pattern, $hook_class, $hook_method) = $hook;

            list($hook_class_namespace, $hook_class_name) = $this->parseClassNamespace($hook_class);

            // Class match.

            if($class == $class_pattern) {
                $matches = $this->parser->methodMatch($method_pattern);

                foreach($matches as $match) {
                    $hook_collection->setHook($match, $hook_class_namespace, $hook_class_name, $hook_method);
                }
            }
        }

        // 

        return $hook_collection;
    }
    
    // 
    
    protected function parseClassNamespace($class)
    {
        $last_separator = strrpos($class, '\\');

        if($last_separator !== false) {
            $namespace = substr($class, 0, $last_separator);
            $class = substr($class, $last_separator + 1);
        } else {
            $namespace = '';
        }
        
        return array($namespace, $class);
    }
    
    protected function findClassFile($class)
    {
        $class_path = str_replace('\\', '/', $class);

        $class_path = $class.'.php';
        
        /*
         * Try the /aop directory.
         * 
         */
        
        if(!file_exists($this->workingDirectory.'/'.$class_path)) {
            $class_path = 'aop/'.$class.'.php';
        }
        
        return $class_path;
    }
}