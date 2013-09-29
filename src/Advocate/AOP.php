<?php

namespace Advocate;

class AOP
{
    protected $interceptedAutoloaders = array();
    
    protected $joins = array();
    
    protected $parser;
    
    protected $workingDirectory;
    protected $storageDirectory;
    
    protected $mapLocation;
    
    protected $classResolvers = array();
    
    // 
    
    public function setStorageDirectory($directory)
    {
        if (!is_dir($directory = realpath($directory))) {
            throw new \Advocate\Exceptions\Storage\InvalidStorageDirectory('The specified storage directory is invalid.');
        }
        
        $this->storageDirectory = $directory;
        
        return $this;
    }
    
    public function getStorageDirectory()
    {
        return $this->storageDirectory;
    }
    
    // 
    
    public function setWorkingDirectory($directory)
    {
        $this->workingDirectory = $directory;
        
        return $this;
    }
    
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }
    
    //
    
    public function setParser($parser)
    {
        $this->parser = $parser;

        return $this;
    }
    
    // 

    public function __construct(
        \Advocate\Interfaces\Parser\ParserInterface $parser = null,
        $directory = null
    ) {
        if (is_null($parser)) {
            $parser = new \Advocate\Classes\Parser\BasicParser;
        }

        $this->setParser($parser);
        
        // 
        
        if ($directory) {
            $this->setWorkingDirectory($directory);
        }
    }
    
    /* */
    
    public function setMapLocation($mappingFile)
    {
        $mappingFile = realpath($mappingFile);
        
        if (!is_file($mappingFile)) {
            throw new \Advocate\Exceptions\Mapping\InvalidMappingFile('Mapping file parameter does not point to a valid file.');
        }
        
        $this->mapLocation = $mappingFile;
        
        // 
        
        return $this;
    }
    
    /* */
    
    public function addClassResolver(
        \Advocate\Interfaces\ClassResolver\ClassResolverInterface $resolver
    ) {
        if ($resolver) {
            $this->classResolvers[] = $resolver;
        }
        
        return $this;
    }
    
    protected function resolveClassPath($class)
    {
        $classPath = null;
        
        /*
         * Iterate through resolvers, which will return the class
         * path if they're able to locate it.
         * 
         */
        
        foreach ($this->classResolvers as $resolver) {
            $classPath = $resolver->resolve($class);
            
            if ($classPath && is_file($classPath = realpath($classPath))) {
                break;
            }
        }
        
        return $classPath;
    }
    
    /* */
    
    protected function toCompiledPath($classLocation)
    {
        $classLocation = preg_replace('/[^0-9a-z\\.\\/\\\]/i', '_', $classLocation);

        return $this->storageDirectory.'/aop/compiled/'.$classLocation;
    }
    
    /* */
    
    public function startUp($mappingFile = null)
    {
        // Set mapping file, if applicable.
        
        if (is_string($mappingFile)) {
            $this->setMapLocation($mappingFile);
        }

        // Load mapping.

        $this->loadMapping();

        /*
         * Intercept autoloaders. This is necessary to catch
         * calls to methods within classes and join aspects if applicable.
         * 
         */

        $this->interceptedAutoloaders = spl_autoload_functions();

        foreach($this->interceptedAutoloaders as $autoloader) {
            spl_autoload_unregister($autoloader);
        }
        
        // 

        $aopAutoloader = function($class)
        {
            // Resolve class path.
            
            $classPath = $this->resolveClassPath($class);
            
            // 

            if ($classPath) {
                $compiledClassLocation = $this->toCompiledPath($classPath);

                /*
                 * Load the compiled code if it's newer than
                 * both the target class and the aspect map.
                 * 
                 */

                if(
                    is_file($compiledClassLocation)
                    && filemtime($compiledClassLocation) > filemtime($classPath)
                    && filemtime($compiledClassLocation) > $this->mapLastModified
                ) {
                    $includePath = $compiledClassLocation;
                } else {
                    $code = file_get_contents($classPath);

                    $this->parser->setCode($code);

                    // Match join points.

                    $joins = $this->matchJoins($class);
                }
            }

            // 
            
            if (isset($joins) && $joins instanceof \Advocate\Classes\Joins\JoinCollection && $joins->hasJoins()) {
                $includePath = $this->compileJoins($compiledClassLocation, $joins);
            }
            
            if (isset($includePath) && $includePath) {
                require $includePath;
            } else {
                foreach($this->getAutoloaders() as $autoloader) {
                    $autoloader($class);

                    if (class_exists($class, false)) {
                        break;
                    }
                }
            }
        };

        // 

        spl_autoload_register($aopAutoloader, true, true);
    }
    
    /* */
    
    
    
    protected function loadMapping()
    {
        if (empty($this->mapLocation)) {
            throw new \Advocate\Exceptions\Mapping\MappingNotConfigured('No valid mapping file has been set.');
        }
        
        /*
         * Get the last time the map was modified. This is needed to check
         * whether it's necessary to rebuild cached classes.
         * 
         */
        
        $this->mapLastModified = filemtime($this->mapLocation);
        
        // Load map data.
        
        $map_array = require($this->mapLocation);
        
        foreach($map_array as $map) {
            $class_pattern = &$map[0];
            
            if($class_pattern{0} == '\\') {
                $class_pattern = substr($class_pattern, 1);
            }

            $this->joins[] = $map;
        }
    }
    
    /* */
    
    public function getAutoloaders()
    {
        return $this->interceptedAutoloaders;
    }

    /* */
    
    public function compileJoins($compilePath, $joins)
    {
        return $this->parser->compileJoins($compilePath, $joins);
    }
    
    /* */
    
    /* */
    
    protected function matchJoins($class)
    {
        list($classNamespace, $className) = $this->parseClassNamespace($class);

        // 

        $joinCollection = (new \Advocate\Classes\Joins\JoinCollection)
            ->setTargetNamespace($classNamespace)
            ->setTargetClass($className);

        foreach($this->joins as $join) {
            list($classPattern, $methodPattern, $joinClass, $joinMethod) = $join;

            list($joinClassNamespace, $joinClassName) = $this->parseClassNamespace($joinClass);

            // Class match
            
            if(
                $class == $classPattern
                || $this->patternMatch($classPattern, $class)
            ) {
                $matches = $this->parser->methodMatch($methodPattern);

                foreach($matches as $match) {
                    $joinCollection->setJoin($match, $joinClassNamespace, $joinClassName, $joinMethod);
                }
            }
        }

        // 

        return $joinCollection;
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

    //
    
    protected function patternMatch($pattern, $subject)
    {
        $parts = explode('*', $pattern);
        
        $new_parts = array();

        foreach ($parts as $part) {
            $new_parts[] = preg_quote($part);
        }

        $pattern = implode('(.*)', $new_parts);
        
        return ($pattern)
                ? preg_match("/{$pattern}/", $subject)
                : false;
    }
}