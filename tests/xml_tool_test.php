<?php
/**
 * Test case for the ezcWebdavInfrastructureBase class.
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
 * Tests for ezcWebdavXmlTool class.
 * 
 * @package Webdav
 * @subpackage Tests
 */
class ezcWebdavXmlToolTest extends ezcTestCase
{
	public static function suite()
	{
		return new PHPUnit_Framework_TestSuite( 'ezcWebdavXmlToolTest' );
	}

    public function testCreateDom()
    {
        $tool = new ezcWebdavXmlTool();
        $dom  = $tool->createDom( '<?xml version="1.0" encoding="utf-8"?><D:propfind xmlns:D="DAV:"><D:allprop/></D:propfind>' );

        $this->assertTrue( $dom instanceof DOMDocument );
        $this->assertSame( 'propfind', $dom->documentElement->localName );
    }

    public function testCreateDomRejectsDoctype()
    {
        $tool = new ezcWebdavXmlTool();

        // Internal entities: a small body expands to an arbitrary amount of
        // text as soon as the content is read.
        $xml = '<?xml version="1.0"?>'
             . '<!DOCTYPE D:propertyupdate [<!ENTITY a "' . str_repeat( 'A', 1000 ) . '">]>'
             . '<D:propertyupdate xmlns:D="DAV:"><D:set><D:prop><x xmlns="urn:x">' . str_repeat( '&a;', 1000 ) . '</x></D:prop></D:set></D:propertyupdate>';

        try
        {
            $tool->createDom( $xml );
            $this->fail( 'ezcWebdavInvalidXmlException expected for a document with a DTD.' );
        }
        catch ( ezcWebdavInvalidXmlException $e )
        {
            $this->assertTrue( strpos( $e->getMessage(), 'document type declaration' ) !== false );
        }

        $this->assertFalse( $tool->createDomDocument( $xml ) );
    }

    public function testCreateDomRejectsExternalEntity()
    {
        $tool = new ezcWebdavXmlTool();
        $xml  = '<?xml version="1.0"?>'
              . '<!DOCTYPE D:propfind SYSTEM "http://example.com/propfind.dtd">'
              . '<D:propfind xmlns:D="DAV:"><D:allprop/></D:propfind>';

        try
        {
            $tool->createDom( $xml );
            $this->fail( 'ezcWebdavInvalidXmlException expected for a document with a DTD.' );
        }
        catch ( ezcWebdavInvalidXmlException $e )
        {
            $this->assertTrue( strpos( $e->getMessage(), 'document type declaration' ) !== false );
        }
    }
}
?>
