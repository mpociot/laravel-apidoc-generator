<?php

namespace Mpociot\ApiDoc\Strategies\BodyParameters;


use Throwable;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Strategies\Strategy;
use Mpociot\ApiDoc\Tools\Traits\DocBlockParamHelpers;

class GetFromRequestClass extends Strategy{
    
    use DocBlockParamHelpers;
    
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        $parameters = [];
        foreach ($method->getParameters() as $param) {
            $paramType = $param->getType();
            if ($paramType === null) {
                continue;
            }

            $parameterClassName = version_compare(phpversion(), '7.1.0', '<')
                ? $paramType->__toString()
                : $paramType->getName();

            $parameters = array_merge($parameters , $this->getBodyParametersFromRequestClass($parameterClassName));
        }

        return $parameters;
    }

    private function getBodyParametersFromRequestClass($requestClass)
    {
        $arrayParams = [];
        try {
            $r = new ReflectionClass($requestClass);
            $instance = $r->newInstanceWithoutConstructor();
            $parameters = $instance->rules();
                foreach ($parameters as $key => $value) {
                    $arrayParams  = array_merge($arrayParams,$this->explodeParams($key , $value));
                }
        } catch (Throwable $e) {
            echo $e->getMessage();
        }

        return $arrayParams;
    }

    public function explodeParams($field , $paramsValue){
        $params = explode('|', $paramsValue);
        $required = false;
        $description = [];
        $type = '';
        foreach($params as $param){
            if($param === 'required'){
                $required = true;
            }
            elseif($param === 'string' || $param === 'integer' ||$param === 'array' ||$param === 'boolean' ||$param === 'float' ||$param === 'number'|| $param === 'object'){
                $type = $param;
            }
            else{
                $description [] = $param;
            }
        }

        return [
            $field=> [
                'type'=> $type,
                'description'=> implode(" ",$description),
                'required'=>$required,
                'value'=>$this->generateDummyValue($type)
            ]
        ];
    }

    
}