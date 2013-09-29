<?php

namespace Advocate\Classes\Parser;

class BasicParser implements \Advocate\Interfaces\Parser\ParserInterface
{
    protected $lexer;
    protected $parser;
    
    protected $methodVisitor;
    protected $classRewriteVisitor;
    
    protected $code;
    protected $syntaxTree;

    protected $methodMatches = array();
    
    /* */
        
    public function __construct()
    {
        $this->lexer = new \PHPParser_Lexer;
        
        $this->parser = new \PHPParser_Parser($this->lexer);
        
        $this->methodVisitor = new \Advocate\Classes\Parser\Visitors\MethodVisitor;
        
        $this->classRewriteVisitor = new \Advocate\Classes\Parser\Visitors\ClassRewriteVisitor;
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
        
        $this->syntaxTree = $this->parser->parse($code);
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
    
    /* */
    
    public function compileJoins($compilePath, \Advocate\Classes\Joins\JoinCollection $joinCollection)
    {
        // 

        $this->classRewriteVisitor->reset();

        $this->classRewriteVisitor->setJoinCollection($joinCollection);

        $traverser = new \PHPParser_NodeTraverser;

        $traverser->addVisitor($this->classRewriteVisitor);

        $traverser->traverse($this->syntaxTree);
        
        // 
        
        $printer = new \PHPParser_PrettyPrinter_Default;

        $compileLocation = dirname($compilePath);

        if(!file_exists($compileLocation.'/')) {
            mkdir($compileLocation, 0777, true);
        }
        
        file_put_contents($compilePath, '<?php '.$printer->prettyPrint($this->syntaxTree));

        return $compilePath;
    }
    
    /* */

    public function methodMatch($methodPattern)
    {
        $this->resetMethodMatches();

        $this->methodVisitor->setMethodRegistrar($this);

        $this->methodVisitor->setMethodPattern($methodPattern);
        
        // 

        $traverser = new \PHPParser_NodeTraverser;

        $traverser->addVisitor($this->methodVisitor);

        $traverser->traverse($this->syntaxTree);
        
        // 
        
        return $this->getMethodMatches();
    }
}