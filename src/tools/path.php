<?php
/**
 * File containing the ezcWebdavPathTools class.
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Webdav
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Helper methods for working with local paths.
 *
 * @package Webdav
 * @version //autogen//
 *
 * @access private
 */
class ezcWebdavPathTools
{
    /**
     * Removes "." and ".." segments from $path.
     *
     * Works like remove_dot_segments from RFC 3986, section 5.2.4: a ".."
     * segment removes the segment before it, and ".." segments above the
     * root are dropped. The result therefore always stays below "/".
     *
     * A path without "." or ".." segments is returned unchanged. Otherwise
     * the result starts with a "/", has no trailing "/" and no empty
     * segments.
     *
     * The path is not percent-decoded: "%2e%2e" is an ordinary segment
     * here, as it is for the backends of this component, which do not
     * decode paths either.
     *
     * @param string $path
     * @return string
     */
    public static function removeDotSegments( $path )
    {
        if ( !self::matchDotSegments( $path, '/' ) )
        {
            return $path;
        }

        $segments = array();
        foreach ( explode( '/', $path ) as $segment )
        {
            if ( $segment === '..' )
            {
                array_pop( $segments );
            }
            else if ( $segment !== '.' && $segment !== '' )
            {
                $segments[] = $segment;
            }
        }
        return '/' . implode( '/', $segments );
    }

    /**
     * Returns if $path contains a "." or ".." segment.
     *
     * $separators is a list of characters, each of which separates segments.
     *
     * Paths are not percent-decoded anywhere in this component. A custom
     * backend that decodes them would turn "%2e%2e" into "..", so $path is
     * checked in its percent-decoded form as well.
     * 
     * @param string $path 
     * @param string $separators 
     * @return bool
     */
    public static function hasDotSegments( $path, $separators = '/' )
    {
        return self::matchDotSegments( $path, $separators ) ||
               self::matchDotSegments( rawurldecode( (string) $path ), $separators );
    }

    /**
     * Returns if $path, as it is, contains a "." or ".." segment.
     *
     * @param string $path 
     * @param string $separators 
     * @return bool
     */
    private static function matchDotSegments( $path, $separators )
    {
        $sep = preg_quote( $separators, '(' );
        return preg_match( "((?:^|[$sep])\\.\\.?(?:[$sep]|$))", (string) $path ) === 1;
    }
}

?>
