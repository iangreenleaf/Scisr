<?php
/*
 * Copyright (C) 2009, 2010 Giorgio Sironi
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * A Factory to create Operations
 */
class Scisr_Operations_Factory
{
    /**
     * @var array
     */
    private $_collaborators;

    public function __construct(array $collaborators)
    {
        foreach ($collaborators as $object) {
            $className = get_class($object);
            $this->_collaborators[$className] = $object;
        }
    }

    /**
     * @param string $className
     * Other parameters follow.
     * @return PHP_CodeSniffer_Sniff
     */
    public function getOperation() {
        $parameters = func_get_args();
        $className = array_shift($parameters);
        $rc = new ReflectionClass($className);
        $constructor = $rc->getConstructor();
        if ($constructor !== null) {
            $parameters = $this->_fillInParameters($parameters, $constructor);
            return $rc->newInstanceArgs($parameters);
        } else {
            return $rc->newInstance();
        }
    }

    /**
     * @param array $parameters
     * @param ReflectionMethod      method with type hints
     * @return array                parameters with collabotors filled in
     */
    protected function _fillInParameters(array $parameters, $method) {
        $signature = array_reverse($method->getParameters(), true);
        foreach ($signature as $parameter) {
            $typeHint = $parameter->getClass();
            if ($typeHint !== null) {
                $className = $typeHint->getName();
                if (isset($this->_collaborators[$className])) {
                    array_unshift($parameters, $this->_collaborators[$className]);
                }
            }
        }
        return $parameters;
    }
}

