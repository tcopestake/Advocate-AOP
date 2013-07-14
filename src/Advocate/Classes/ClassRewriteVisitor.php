<?php

namespace Advocate\Classes;

class ClassRewriteVisitor extends \PHPParser_NodeVisitorAbstract
{
    protected $hookCollection;
    protected $hooks;
    
    protected $classNode;
    protected $constructorNode;

    public function enterNode(\PHPParser_Node $node)
    {
        if($node instanceof \PHPParser_Node_Stmt_Class) {
            $this->classNode = $node;
        } elseif($node instanceof \PHPParser_Node_Stmt_ClassMethod) {
            if($node->name == '__construct') {
                $this->constructorNode = $node;
            }
            
            if(isset($this->hooks[$node->name]))
            {
                $closure_use_params = array();
                
                foreach($node->params as $param) {
                    $closure_use_params[] = new \PHPParser_Node_Expr_ClosureUse($param->name, $param->byRef);
                }

                // 
                
                $call_statements = array();
                
                foreach($this->hooks[$node->name] as $hook) {
                    $call_statements[] = new \PHPParser_Node_Expr_MethodCall(
                        new \PHPParser_Node_Expr_PropertyFetch(
                            new \PHPParser_Node_Expr_Variable('this'),
                            $hook->getNameAsProperty()
                        ),
                        $hook->getMethod()
                    );
                }

                /*
                 * Move method functionality to
                 * within closure and inject new statements.
                 * 
                 */

                $return_variable = new \PHPParser_Node_Expr_Variable('return');

                $node->stmts = array_merge(
                    array(
                        $this->wrapInEnclosure($node->stmts, array(), $closure_use_params),
                        new \PHPParser_Node_Expr_Assign(
                            $return_variable,
                            new \PHPParser_Node_Expr_FuncCall(
                                new \PHPParser_Node_Expr_Variable('enclosure')
                            )
                        )
                    ),
                    $call_statements,
                    array($this->makeReturn($return_variable))
                );
            }
        }
    }
    
    
    //
    
    public function afterTraverse(array $nodes)
    {
        // New constructor code.
        
        $classes_loaded = array();
        
        $property_statements = array();        
        $property_assignments = array();

        foreach($this->hookCollection->getHooks() as $hook_list) {
            foreach($hook_list as $hook) {
                $hook_class = $hook->getClass();
                
                if(isset($classes_loaded[$hook_class])) {
                    continue;
                }
                
                // 

                $aspect_property = $hook->getNameAsProperty();
                
                // Create property.
                
                $property_statements[] = new \PHPParser_Node_Stmt_Property(
                    \PHPParser_Node_Stmt_Class::MODIFIER_PROTECTED,
                    array(new \PHPParser_Node_Stmt_PropertyProperty($aspect_property))
                );

                // Create assignment for constructor.
                
                $property_assignments[] = new \PHPParser_Node_Expr_Assign(
                    new \PHPParser_Node_Expr_PropertyFetch(
                        new \PHPParser_Node_Expr_Variable('this'),
                        $aspect_property
                    ),
                    new \PHPParser_Node_Expr_New(
                        new \PHPParser_Node_Name($hook->getClassNamespaced())
                    )
                );
                
                $classes_loaded[$hook_class] = true;
            }
        }
        
        /*
         * Add new constructor
         * or append code to existing constructor.
         * 
         */
        
        if(is_null($this->constructorNode)) {
            $this->constructorNode = new \PHPParser_Node_Stmt_ClassMethod('__construct');
            
            $this->classNode->stmts[] = $this->constructorNode;
            
            /*
             * Add parent::__construct safety measures 
             * to the newly created constructor.
             * 
             */
            
            $parent_call = array(
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

            $this->constructorNode->stmts = $parent_call;
            
        } else {
            
            $closure_use_params = array();

            foreach($this->constructorNode->params as $param) {
                $closure_use_params[] = new \PHPParser_Node_Expr_ClosureUse($param->name, $param->byRef);
            }
            
            // 
            
            $this->constructorNode->stmts = array(
                $this->wrapInEnclosure($this->constructorNode->stmts, array(), $closure_use_params),
                    new \PHPParser_Node_Expr_FuncCall(
                        new \PHPParser_Node_Expr_Variable('enclosure')
                    )
             );
        }
        
        // Insert statements.

        if(!empty($property_statements)) {
            $this->classNode->stmts = array_merge($property_statements, $this->classNode->stmts);
        }
        
        if(!empty($property_assignments)) {
            $this->constructorNode->stmts = array_merge($property_assignments, $this->constructorNode->stmts);
        }
    }
    
    // 
    
    public function setHookCollection($hook_collection)
    {
        $this->hookCollection = $hook_collection;
        
        $this->hooks = $this->hookCollection->getHooks();
    }

    // 

    public function leaveNode(\PHPParser_Node $node)
    {
        
    }
    
    //
    
    protected function wrapInEnclosure($statements, $parameters = array(), $uses = array())
    {
        $enclosure_variable = new \PHPParser_Node_Expr_Variable('enclosure');

        $assign = new \PHPParser_Node_Expr_Assign(
            $enclosure_variable,
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
    
    protected function makeReturn($return_variable)
    {
        return new \PHPParser_Node_Stmt_Return(
            $return_variable
        );
    }
}