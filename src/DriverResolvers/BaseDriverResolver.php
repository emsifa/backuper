<?php

namespace Emsifa\Backuper\DriverResolvers;

use Emsifa\Backuper\Exceptions\MissingRequiredParameterException;

abstract class BaseDriverResolver
{

    protected function requireParams($requiredParams, array $params)
    {
        $requiredParams = (array) $requiredParams;
        $missingParams = array_diff($requiredParams, array_keys($params));
        if (!empty($missingParams)) {
            $len = count($missingParams);
            $missingParams = array_map(function($param, $index) use ($len) {
                return ($len-1 == $index AND $len > 1)? "and '{$param}'" : "'{$param}'";
            }, $missingParams, array_keys($missingParams));
            throw new MissingRequiredParameterException("Missing required parameter ".implode(", ", $missingParams), 1);
        }
    }

    abstract public function makeFilesystem(array $params);

}
