<?php

namespace malkusch\phpmock;

/**
 * Helper which builds the mock function.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @internal
 */
class MockFunctionHelper
{
    
    /**
     * @var string The internal name for optional parameters.
     */
    const DEFAULT_ARGUMENT = "optionalParameter";
 
    /**
     * @var Mock The mock.
     */
    private $mock;
    
    /**
     * @var \Text_Template The function template.
     */
    private $template;
    
    /**
     * Sets the mock.
     *
     * @param Mock $mock The mock.
     */
    public function __construct(Mock $mock)
    {
        $this->mock     = $mock;
        $this->template = new \Text_Template(__DIR__ . "/function.tpl");
    }
    
    /**
     * Defines the mock function.
     */
    public function defineFunction()
    {
        $name = $this->mock->getName();

        $parameterBuilder = new ParameterBuilder();
        $parameterBuilder->build($name);

        $data = [
            "namespace" => $this->mock->getNamespace(),
            "name"      => $name,
            "signatureParameters"   => $parameterBuilder->getSignatureParameters(),
            "bodyParameters"        => $parameterBuilder->getBodyParameters(),
            "canonicalFunctionName" => $this->mock->getCanonicalFunctionName()
        ];
        $this->template->setVar($data, false);
        $definition = $this->template->render();

        eval($definition);
    }
    
    /**
     * Calls the enabled mock, or the built-in function otherwise.
     *
     * @param string $functionName          The function name.
     * @param string $canonicalFunctionName The canonical function name.
     * @param array  $arguments             The arguments.
     *
     * @return mixed The result of the called function.
     * @see Mock::define()
     */
    public static function call($functionName, $canonicalFunctionName, &$arguments)
    {
        $registry = MockRegistry::getInstance();
        $mock     = $registry->getMock($canonicalFunctionName);

        foreach ($arguments as $key => $argument) {
            if ($argument === self::DEFAULT_ARGUMENT) {
                unset($arguments[$key]);
            }
        }
        if (empty($mock)) {
            // call the built-in function if the mock was not enabled.
            return call_user_func_array($functionName, $arguments);
        
        } else {
            // call the mock function.
            return $mock->call($arguments);
        }
    }
}
