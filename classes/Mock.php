<?php

namespace malkusch\phpmock;

/**
 * Mocking framework for built-in PHP functions.
 * 
 * Mocking a build-in PHP function is achieved by using
 * PHP's namespace fallback policy. A mock will provide the namespaced function.
 * I.e. only unqualified functions in a non-global namespace can be mocked.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see MockBuilder
 */
class Mock
{
    
    /**
     * @var string namespace for the mock function.
     */
    private $namespace;
    
    /**
     * @var string function name of the mocked function.
     */
    private $name;
    
    /**
     * @var callable The function mock.
     */
    private $function;
    
    /**
     * @var Recorder Call recorder.
     */
    private $recorder;
    
    /**
     * Set the namespace, function name and the mock function.
     * 
     * @param string   $namespace  The namespace for the mock function.
     * @param string   $name       The function name of the mocked function.
     * @param callable $function   The mock function.
     */
    public function __construct($namespace, $name, callable $function)
    {
        $this->namespace = $namespace;
        $this->name = $name;
        $this->function = $function;
        $this->recorder = new Recorder();
    }
    
    /**
     * Returns the call recorder.
     * 
     * Every call to the mocked function was recorded to this call recorder.
     * 
     * @return Recorder The call recorder.
     */
    public function getRecorder()
    {
        return $this->recorder;
    }
    
    /**
     * Enables this mock.
     * 
     * @throws MockEnabledException If the function has already an enabled mock.
     */
    public function enable()
    {
        $registry = MockRegistry::getInstance();
        if ($registry->isRegistered($this)) {
            throw new MockEnabledException(
                "$this->name is already enabled."
                . "Call disable() on the existing mock."
            );
            
        }
        $this->defineMockFunction();
        $registry->register($this);
    }

    /**
     * Disable this mock.
     */
    public function disable()
    {
        MockRegistry::getInstance()->unregister($this);
    }
    
    /**
     * Calls the mocked function.
     * 
     * This method is called from the namespaced function.
     * It also records the call in the call recorder.
     * 
     * @param array $arguments the call arguments.
     * @return mixed
     * @internal
     */
    public function call(array $arguments)
    {
        $this->recorder->record($arguments);
        return call_user_func_array($this->function, $arguments);
    }
    
    /**
     * Returns the function name with its namespace.
     * 
     * @return String The function name with its namespace.
     * @internal
     */
    public function getCanonicalFunctionName()
    {
        return strtolower("$this->namespace\\$this->name");
    }

    /**
     * Defines the mocked function in the given namespace.
     * 
     * If the function was already defined this method does nothing.
     */
    private function defineMockFunction()
    {
        $canonicalFunctionName = $this->getCanonicalFunctionName();
        if (function_exists($canonicalFunctionName)) {
            return;
            
        }
        
        $definition = "
            namespace $this->namespace {
                
                use \malkusch\phpmock\MockRegistry;

                function $this->name()
                {
                    \$registry = MockRegistry::getInstance();
                    \$mock = \$registry->getMock('$canonicalFunctionName');
                    
                    // call the built-in function if the mock was not enabled.
                    if (empty(\$mock)) {
                        return call_user_func_array(
                            '$this->name', func_get_args()
                        );
                    }
                    
                    // call the mock function.
                    return \$mock->call(func_get_args());
                }
            }";
                
        eval($definition);
    }
}
