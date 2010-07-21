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
    public function __construct(Scisr_ChangeRegistry $changeRegistry)
    {
        $this->_changeRegistry = $changeRegistry;
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
        if ($rc->getConstructor() !== null) {
            array_unshift($parameters, $this->_changeRegistry);
            return $rc->newInstanceArgs($parameters);
        } else {
            return $rc->newInstance();
        }
    }
}

