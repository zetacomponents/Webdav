<?php
/**
 * Basic test cases for the path factory class.
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
 * @subpackage Tests
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Reqiuire base test
 */

/**
 * Custom path factpory
 */
require_once 'classes/test_path_factory.php';

/**
 * Tests for ezcWebdavBasicPathFactory class.
 * 
 * @package Webdav
 * @subpackage Tests
 */
class ezcWebdavBasicPathFactoryTest extends ezcTestCase
{
	public static function suite()
	{
		return new PHPUnit_Framework_TestSuite( 'ezcWebdavBasicPathFactoryTest' );
	}

    public function testPathDispatchingWithoutBasePath()
    {
        $uri = '/collection/ressource';
        $fakePath = $uri;

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri )
        );
    }

    public function testPathDispatchingWithBasePath()
    {
        $uri = '/my/webdav/base/collection/ressource';
        $fakePath = '/collection/ressource';

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com/my/webdav/base' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri )
        );
    }

    public function testPathDispatchingCollectionWithoutBasePath()
    {
        $uri = '/collection/another_coll/';
        $fakePath = '/collection/another_coll';

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri )
        );
    }

    public function testPathDispatchingCollectionWithBasePath()
    {
        $uri = '/my/webdav/base/collection/another_coll';
        $fakePath = '/collection/another_coll';

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com/my/webdav/base' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri )
        );
    }
    
    public function testUriDispatchingWithoutBasePath()
    {
        $uri = 'http://example.com/collection/ressource';
        $fakePath = '/collection/ressource';

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri )
        );
    }

    public function testUriDispatchingWithBasePath()
    {
        $uri = 'http://example.com/my/webdav/base/collection/ressource';
        $fakePath = '/collection/ressource';

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com/my/webdav/base' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri )
        );
    }

    public function testUriDispatchingCollectionWithoutBasePath()
    {
        $uri = 'http://example.com/collection/another_coll/';
        $fakePath = '/collection/another_coll';

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri )
        );
    }

    public function testUriDispatchingCollectionWithBasePath()
    {
        $uri = 'http://example.com/my/webdav/base/collection/another_coll';
        $fakePath = '/collection/another_coll';

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com/my/webdav/base' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri )
        );
    }

    public function testUriDispatchingCollectionWithoutBasePathRestore()
    {
        $uri      = 'http://example.com/collection/another_coll/';
        $fakePath = '/collection/another_coll';
        $fakeUri  = $uri;

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri ),
            'Parsing of URI failed.'
        );
        $this->assertEquals(
            $fakeUri,
            $factory->generateUriFromPath( $fakePath ),
            'Restoring of URI failed'
        );
    }

    public function testUriDispatchingCollectionWithBaseUriRestore()
    {
        $uri      = 'http://example.com/my/webdav/base/collection/another_coll/';
        $fakePath = '/collection/another_coll';
        $fakeUri  = $uri;

        $factory = new ezcWebdavBasicPathFactory( 'http://example.com/my/webdav/base' );

        $this->assertEquals(
            $fakePath,
            $factory->parseUriToPath( $uri ),
            'Parsing of URI failed.'
        );
        $this->assertEquals(
            $fakeUri,
            $factory->generateUriFromPath( $fakePath ),
            'Restoring of URI failed'
        );
    }

    // Issue #13389
    public function testCtorFailureUriWithoutScheme()
    {   
        try
        {
            $pathFactory = new ezcWebdavBasicPathFactory(
                '/some/path/without/uri'
            );
            $this->fail(
                'Exception not thrown on creation of basic path factory with invalid URI.'
            );
        }
        catch ( ezcWebdavBrokenBaseUriException $e ) {}
    }

    // Issue #13389
    public function testCtorFailureUriWithoutHost()
    {   
        try
        {
            $pathFactory = new ezcWebdavBasicPathFactory(
                'http:///some'
            );
            $this->fail(
                'Exception not thrown on creation of basic path factory with invalid URI.'
            );
        }
        catch ( ezcWebdavBrokenBaseUriException $e ) {}
    }

    // Issue #13389
    public function testCtorFailureBrokenUri()
    {   
        try
        {
            $pathFactory = new ezcWebdavBasicPathFactory(
                'http//some/foo/bar'
            );
            $this->fail(
                'Exception not thrown on creation of basic path factory with invalid URI.'
            );
        }
        catch ( ezcWebdavBrokenBaseUriException $e ) {}
    }

    public function testDispatchingWithBaseUriSlash()
    {
        $pathFactory = new ezcWebdavBasicPathFactory(
            'http://example.com/some/dir/'
        );

        $this->assertEquals(
            '/foo/bar',
            $pathFactory->parseUriToPath(
                'http://example.com/some/dir/foo/bar'
            )
        );
    }
    public function testDotSegmentsAreRemoved()
    {
        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        $this->assertSame( '/collection/other', $factory->parseUriToPath( '/collection/./resource/../other' ) );
        $this->assertSame( '/var/www/shell.php', $factory->parseUriToPath( 'http://example.com/../../../var/www/shell.php' ) );
        $this->assertSame( '/', $factory->parseUriToPath( '/collection/../..' ) );
    }

    public function testDotSegmentsCannotLeaveBasePath()
    {
        $factory = new ezcWebdavBasicPathFactory( 'http://example.com/my/webdav/base' );

        $this->assertSame( '/etc/passwd', $factory->parseUriToPath( '/my/webdav/base/../../../etc/passwd' ) );
        $this->assertSame( '/etc/passwd', $factory->parseUriToPath( 'http://example.com/my/webdav/base/collection/../../../../etc/passwd' ) );
    }

    public function testDotSegmentsInCollectionPath()
    {
        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        $this->assertSame( '/collection', $factory->parseUriToPath( '/collection/sub/../' ) );
        $this->assertSame( 'http://example.com/collection/', $factory->generateUriFromPath( '/collection' ) );
    }

    public function testFragmentCannotLeaveRoot()
    {
        $factory = new ezcWebdavBasicPathFactory( 'http://example.com/dav' );

        $this->assertSame( '/y', $factory->parseUriToPath( 'http://example.com/dav/x#/../../y' ) );
        $this->assertSame( '/x#frag', $factory->parseUriToPath( 'http://example.com/dav/x#frag' ) );
    }

    public function testPercentEncodedDotsAreNotDecoded()
    {
        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        // Paths are never decoded, so "%2e%2e" is an ordinary name here.
        $this->assertSame( '/a/%2e%2e/b', $factory->parseUriToPath( '/a/%2e%2e/b' ) );
        $this->assertSame( '/a/%2e%2e/b', ezcWebdavPathTools::removeDotSegments( '/a/%2e%2e/b' ) );

        // A backend that decodes paths would see "..", so the check used by
        // the file backend looks at the decoded form, too.
        $this->assertTrue( ezcWebdavPathTools::hasDotSegments( '/a/%2e%2e/b' ) );
        $this->assertTrue( ezcWebdavPathTools::hasDotSegments( '/a/%2E./b' ) );
        $this->assertTrue( ezcWebdavPathTools::hasDotSegments( '/a/..%2f..%2fb' ) );
        $this->assertFalse( ezcWebdavPathTools::hasDotSegments( '/a/%2e%2ex/b' ) );
    }

    public function testNamesContainingDotsAreKept()
    {
        $factory = new ezcWebdavBasicPathFactory( 'http://example.com' );

        $this->assertSame( '/collection/..resource../.hidden/file.', $factory->parseUriToPath( '/collection/..resource../.hidden/file.' ) );
    }
}
?>
