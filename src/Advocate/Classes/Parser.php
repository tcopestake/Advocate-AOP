<?php

namespace Advocate\Classes;

class Parser
{
    protected $lexer;
    protected $parser;
    
    protected $methodVisitor;
    protected $classRewriteVisitor;
    
    protected $code;
    protected $syntaxTree;
    
    protected $hookCode;
    protected $hookSyntaxTree;
    
    protected $methodMatches = array();
    
    /* */
        
    public function __construct()
    {
        $this->lexer = new \PHPParser_Lexer;
        
        $this->parser = new \PHPParser_Parser($this->lexer);

        $this->methodVisitor = new MethodVisitor;
        
        $this->classRewriteVisitor = new ClassRewriteVisitor;
    }
    
    /* */

    public function registerMethodMatch($method)
    {
        $this->methodMatches[] = $method;
    }
    
    /* */

    public function setCode($code)
    {
        $this->code = $code;
        
        $this->syntaxTree = $this->parser->parse($this->code);
    }
    
    /* */

    protected function resetMethodMatches()
    {
        $this->methodMatches = array();
    }
    
    /* */

    protected function getMethodMatches()
    {
        return $this->methodMatches;
    }
    
    /* */
    
    public function parseHooks(HookCollection $hook_collection)
    {
        // 
    }
    
    /* */
    
    public function compileHooks($compile_path, HookCollection $hook_collection)
    {
        // 

        $this->classRewriteVisitor->setHookCollection($hook_collection);

        $traverser = new \PHPParser_NodeTraverser;

        $traverser->addVisitor($this->classRewriteVisitor);

        $traverser->traverse($this->syntaxTree);
        
        // 
        
        $printer = new \PHPParser_PrettyPrinter_Default;

        $compile_location = dirname($compile_path);
        
        if(!file_exists($compile_location.'/')) {
            mkdir($compile_location, 0777, true);
        }
        
        file_put_contents($compile_path, '<?php '.$printer->prettyPrint($this->syntaxTree));

        return $compile_path;
    }
    
    /* */

    public function methodMatch($method_pattern)
    {
        $this->resetMethodMatches();

        $this->methodVisitor->setMethodRegistrar($this);

        $this->methodVisitor->setMethodPattern($method_pattern);
        
        // 

        $traverser = new \PHPParser_NodeTraverser;

        $traverser->addVisitor($this->methodVisitor);

        $traverser->traverse($this->syntaxTree);
        
        // 
        
        return $this->getMethodMatches();
    }
}