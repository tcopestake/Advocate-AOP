<?php

namespace Advocate\Classes\Parser\Visitors;

class ClassRewriteVisitor extends \PHPParser_NodeVisitorAbstract
{
    protected $joinCollection;
    protected $joins;
    
    protected $classNode;
    protected $constructorNode;
    
    /* */
    
    public function reset()
    {
        $this->constructorNode = null;
    }
    
    /* */

    public function enterNode(\PHPParser_Node $node)
    {
        if($node instanceof \PHPParser_Node_Stmt_Class) {
            $this->classNode = $node;
        } elseif($node instanceof \PHPParser_Node_Stmt_ClassMethod) {
            if($node->name == '__construct') {
                $this->constructorNode = $node;
            }
            
            if(isset($this->joins[$node->name]))
            {
                $closureUseParams = array();
                
                foreach($node->params as $param) {
                    $closureUseParams[] = new \PHPParser_Node_Expr_ClosureUse($param->name, $param->byRef);
                }

                // 
                
                $callStatementsBefore = array();
                $callStatementsAfter = array();
                
                foreach($this->joins[$node->name] as $join) {
                    $newCallStatement = new \PHPParser_Node_Expr_MethodCall(
                        new \PHPParser_Node_Expr_PropertyFetch(
                            new \PHPParser_Node_Expr_Variable('this'),
                            $join->getNameAsProperty()
                        ),
                        $join->getMethod(),
                        array(
                            new \PHPParser_Node_Expr_Variable('aspectInstance')
                        )
                    );
                    
                    if ($join->isBefore()) {
                        $callStatementsBefore[] = $newCallStatement;
                    }
                    
                    if ($join->isAfter()) {
                        $callStatementsAfter[] = $newCallStatement;
                    }
                }

                /*
                 * Move method functionality to
                 * within closure and inject new statements.
                 * 
                 */

                $returnVariable = new \PHPParser_Node_Expr_Variable('return');
                
                if (!empty($callStatementsAfter)) {
                    $prepareReturn = array(
                        new \PHPParser_Node_Expr_Assign(
                            $returnVariable,
                            new \PHPParser_Node_Expr_ConstFetch(
                                new \PHPParser_Node_Name('null')
                            )
                        )
                    );
                    
                    $enclosureCode = array_merge(
                        $this->createTryCatch(
                            array(
                                $this->wrapInEnclosure($node->stmts, array(), $closureUseParams),
                                new \PHPParser_Node_Expr_Assign(
                                    $returnVariable,
                                    new \PHPParser_Node_Expr_FuncCall(
                                        new \PHPParser_Node_Expr_Variable('enclosure')
                                    )
                                )
                            ),
                            array_merge(
                                array(
                                    new \PHPParser_Node_Expr_MethodCall(
                                        new \PHPParser_Node_Expr_Variable('aspectInstance'),
                                        'setReturnValue',
                                        array(
                                            $returnVariable
                                        )
                                    ),
                                    new \PHPParser_Node_Expr_MethodCall(
                                        new \PHPParser_Node_Expr_Variable('aspectInstance'),
                                        'setException',
                                        array(
                                            new \PHPParser_Node_Expr_Variable('exception'),
                                        )
                                    )
                                ),
                                $callStatementsAfter,
                                array(
                                    new \PHPParser_Node_Stmt_Throw(
                                        new \PHPParser_Node_Expr_Variable('exception')
                                    ),
                                )
                            )
                        ),
                        array(
                            new \PHPParser_Node_Expr_MethodCall(
                                new \PHPParser_Node_Expr_Variable('aspectInstance'),
                                'setReturnValue',
                                array(
                                    $returnVariable
                                )
                            ),
                        ),
                        $callStatementsAfter
                    );
                } else {
                    $prepareReturn = array();
                    
                    $enclosureCode = array(
                        $this->wrapInEnclosure($node->stmts, array(), $closureUseParams),
                        new \PHPParser_Node_Expr_Assign(
                            $returnVariable,
                            new \PHPParser_Node_Expr_FuncCall(
                                new \PHPParser_Node_Expr_Variable('enclosure')
                            )
                        )
                    );
                }
                
                $node->stmts = array_merge(
                    $prepareReturn,
                    $this->createAspectInstance($node->name),
                    $callStatementsBefore,
                    $enclosureCode,
                    array($this->makeReturn($returnVariable))
                );
            }
        }
    }
    
    
    //
    
    public function afterTraverse(array $nodes)
    {
        // New constructor code.
        
        $classesLoaded = array();
        
        $propertyStatements = array();        
        $propertyAssignments = array();

        foreach($this->joinCollection->getJoins() as $joinList) {
            foreach($joinList as $join) {
                $joinClass = $join->getClass();
                
                if(isset($classesLoaded[$joinClass])) {
                    continue;
                }
                
                // 

                $aspectProperty = $join->getNameAsProperty();
                
                // Create property.
                
                $propertyStatements[] = new \PHPParser_Node_Stmt_Property(
                    \PHPParser_Node_Stmt_Class::MODIFIER_PROTECTED,
                    array(new \PHPParser_Node_Stmt_PropertyProperty($aspectProperty))
                );

                // Create assignment for constructor.
                
                $propertyAssignments[] = new \PHPParser_Node_Expr_Assign(
                    new \PHPParser_Node_Expr_PropertyFetch(
                        new \PHPParser_Node_Expr_Variable('this'),
                        $aspectProperty
                    ),
                    new \PHPParser_Node_Expr_New(
                        new \PHPParser_Node_Name($join->getClassNamespaced())
                    )
                );
                
                $classesLoaded[$joinClass] = true;
            }
        }
        
        /*
         * Add new constructor
         * or append code to existing constructor.
         * 
         */
        
        if (is_null($this->constructorNode)) {
            $this->constructorNode = new \PHPParser_Node_Stmt_ClassMethod('__construct');
            
            $this->classNode->stmts[] = $this->constructorNode;
            
            /*
             * Add parent::__construct safety measures 
             * to the newly created constructor.
             * 
             */
            
            $parentCall = array(
                new \PHPParser_Node_Expr_Assign(
                    new \PHPParser_Node_Expr_Variable('parent'),
                    new \PHPParser_Node_Expr_FuncCall(
                        new \PHPParser_Node_Name('get_parent_class'),
                        array(
                            new \PHPParser_Node_Arg(
                                new \PHPParser_Node_Expr_Variable('this')
                            ),
                        )
                    )
                ),
                
                new \PHPParser_Node_Stmt_If(
                    new \PHPParser_Node_Expr_FuncCall(
                        new \PHPParser_Node_Name('method_exists'),
                        array(
                            new \PHPParser_Node_Arg(
                                new \PHPParser_Node_Expr_Variable('parent')
                            ),
                            new \PHPParser_Node_Scalar_String('__construct')
                        )
                    ),
                        
                    array(
                            'stmts' =>  array(
                                new \PHPParser_Node_Expr_FuncCall(
                                    new \PHPParser_Node_Name('call_user_func_array'),
                                    array(
                                        new \PHPParser_Node_Arg(
                                            new \PHPParser_Node_Expr_Array(
                                                array(
                                                    new \PHPParser_Node_Expr_ArrayItem(
                                                        new \PHPParser_Node_Expr_Variable('parent')
                                                    ),
                                                    new \PHPParser_Node_Expr_ArrayItem(
                                                        new \PHPParser_Node_Scalar_String('__construct')
                                                    ),
                                                )
                                            )
                                        ), 
                                        new \PHPParser_Node_Arg(
                                            new \PHPParser_Node_Expr_FuncCall(
                                                new \PHPParser_Node_Name('func_get_args')
                                            )
                                        ),
                                    )
                                )
                             )
                        )
                    
                ),
            );

            $this->constructorNode->stmts = $parentCall;
            
        } else {
            $closureUseParams = array();

            foreach($this->constructorNode->params as $param) {
                $closureUseParams[] = new \PHPParser_Node_Expr_ClosureUse($param->name, $param->byRef);
            }
            
            // 
            
            $this->constructorNode->stmts = array(
                $this->wrapInEnclosure($this->constructorNode->stmts, array(), $closureUseParams),
                    new \PHPParser_Node_Expr_FuncCall(
                        new \PHPParser_Node_Expr_Variable('enclosure')
                    )
             );
        }
        
        // Insert statements.

        if(!empty($propertyStatements)) {
            $this->classNode->stmts = array_merge($propertyStatements, $this->classNode->stmts);
        }
        
        if(!empty($propertyAssignments)) {
            $this->constructorNode->stmts = array_merge($propertyAssignments, $this->constructorNode->stmts);
        }
    }
    
    // 
    
    public function setJoinCollection($joinCollection)
    {
        $this->joinCollection = $joinCollection;
        
        $this->joins = $this->joinCollection->getJoins();
    }

    // 

    public function leaveNode(\PHPParser_Node $node)
    {
        
    }
    
    //
    
    protected function wrapInEnclosure($statements, $parameters = array(), $uses = array())
    {
        $enclosureVariable = new \PHPParser_Node_Expr_Variable('enclosure');

        $assign = new \PHPParser_Node_Expr_Assign(
            $enclosureVariable,
            new \PHPParser_Node_Expr_Closure(
                array(
                    'stmts'     => $statements,
                    'params'    => $parameters,
                    'uses'      => $uses,
                )
            )
        );

        // Overwrite existing nodes;

        return $assign;
    }
    
    protected function makeReturn($returnVariable)
    {
        return new \PHPParser_Node_Stmt_Return(
            $returnVariable
        );
    }
    
    protected function createAspectInstance($method)
    {
        return array(
            new \PHPParser_Node_Expr_Assign(
                new \PHPParser_Node_Expr_Variable('aspectInstance'),
                new \PHPParser_Node_Expr_New(
                    new \PHPParser_Node_Name('\\Advocate\\Aspects\\AspectInstance'),
                    array(
                        new \PHPParser_Node_Scalar_String(
                            '\\'.implode(
                                '\\',
                                array(
                                    $this->joinCollection->getTargetNamespace(),
                                    $this->joinCollection->getTargetClass()
                                )
                            )
                        ),
                        new \PHPParser_Node_Scalar_String($method),
                    )
                )
            ),
            new \PHPParser_Node_Expr_MethodCall(
                new \PHPParser_Node_Expr_Variable('aspectInstance'),
                'setArguments',
                array(
                    new \PHPParser_Node_Expr_FuncCall(
                        new \PHPParser_Node_Name('func_get_args')
                    ),
                )
            )
        );
    }
    
    protected function createTryCatch($try, $catch)
    {
        return array(
            new \PHPParser_Node_Stmt_TryCatch(
                $try,
                array(
                    new \PHPParser_Node_Stmt_Catch(
                        new \PHPParser_Node_Name('\\Exception'),
                        'exception',
                        $catch
                    ),
                )
            )
        );
    }
}