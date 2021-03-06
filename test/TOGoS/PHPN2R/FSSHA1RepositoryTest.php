<?php

class TOGoS_PHPN2R_FSSHA1RepositoryTest extends TOGoS_SimplerTest_TestCase
{
	public function setUp() {
		$this->repo = new TOGoS_PHPN2R_FSSHA1Repository("/tmp/test-repo");
		$this->helloWorldText = "Hello, world!\n";
		$this->helloWorldUrn = 'urn:sha1:BH5MRW75E66ZWTJDUAHLMSFKOULYSU3N';
		$this->wrongUrn = 'urn:sha1:ZZZMRW75E66ZWTJDUAHLMSFKOULYSZZZ';
		$this->invalidUrn = 'Hi there!';
	}
	
	protected function newHelloWorldTempFile() {
		$tempFile = $this->repo->newTempFile();
		file_put_contents($tempFile, $this->helloWorldText);
		return $tempFile;
	}
	
	protected function newHelloWorldStream() {
		return fopen($this->newHelloWorldTempFile(), "rb");
	}

	public function testTempFileWithOptions() {
		$fiel = $this->repo->newTempFile(array(
			TOGoS_PHPN2R_Repository::OPT_SECTOR => 'rigged',
			'postfix' => '.orange',
		));
		$this->assertTrue( is_dir(dirname($fiel)), "Directory containing hypothetical temp file should exist" );
		$this->assertTrue( preg_match('#/rigged/#', $fiel), "Temp file should be in a directory corresponding to the requested sector" );
		$this->assertTrue( preg_match('#\.orange$#', $fiel), "Temp file name should end with the requested postfix" );
	}
	
	public function testPutString() {
		$this->assertEquals(
			$this->helloWorldUrn,
			$this->repo->putString($this->helloWorldText, 'blah')
		);
	}
	
	public function testPutTempFileBlob() {
		$tempFile = tempnam(sys_get_temp_dir(), 'tempfileblobtest');
		file_put_contents($tempFile, $this->helloWorldText);
		$this->assertEquals(
			$this->helloWorldUrn,
			$this->repo->putBlob(new Nife_FileBlob($tempFile), array(
				TOGoS_PHPN2R_Repository::OPT_SECTOR => 'blah',
				TOGoS_PHPN2R_Repository::OPT_ALLOW_SOURCE_REMOVAL => true,
			))
		);
		$this->assertFalse( file_exists($tempFile), "Temp file should have been removed!");
	}
	
	public function testPutNotSoTempFileBlob() {
		$tempFile = tempnam(sys_get_temp_dir(), 'tempfileblobtest');
		file_put_contents($tempFile, $this->helloWorldText);
		$this->assertEquals(
			$this->helloWorldUrn,
			$this->repo->putBlob(new Nife_FileBlob($tempFile), array(
				TOGoS_PHPN2R_Repository::OPT_SECTOR => 'blah',
			))
		);
		$this->assertTrue( file_exists($tempFile), "Temp file should not have been removed!");
	}

	public function testPutStringBlob() {
		$this->assertEquals(
			$this->helloWorldUrn,
			$this->repo->putBlob(Nife_Util::blob($this->helloWorldText), array(
				TOGoS_PHPN2R_Repository::OPT_SECTOR => 'blah',
			))
		);
	}
	
	public function testPutTempFile() {
		$this->assertEquals(
			$this->helloWorldUrn,
			$this->repo->putTempFile($this->newHelloWorldTempFile(), 'blah')
		);
	}
	
	public function testPutTempFileWithExpectedUrn() {
		$this->assertEquals(
			$this->helloWorldUrn,
			$this->repo->putTempFile($this->newHelloWorldTempFile(), 'blah',
				$this->helloWorldUrn)
		);
	}
	
	protected function _testPutTempFileWithBadUrn($urn, $expectedExceptionClass) {
		$caught = null;
		try {
			$this->repo->putTempFile($this->newHelloWorldTempFile(), 'blah', $urn);
			$this->fail("Should've thrown a hash-mismatch exception.");
		} catch( Exception $e ) {
			$caught = get_class($e);
		}
		$this->assertEquals($expectedExceptionClass, $caught);
	}
	
	public function testPutTempFileWithInvalidUrn() {
		$this->_testPutTempFileWithBadUrn($this->invalidUrn, 'TOGoS_PHPN2R_IdentifierFormatException');
	}
	
	public function testPutTempFileWithWrongUrn() {
		$this->_testPutTempFileWithBadUrn($this->wrongUrn, 'TOGoS_PHPN2R_HashMismatchException');
	}
	
	public function testPutStream() {
		$this->assertEquals(
			$this->helloWorldUrn,
			$this->repo->putStream( $this->newHelloWorldStream(), 'blah')
		);
	}

	public function testPutStreamWithExpectedUrn() {
		$this->assertEquals(
			$this->helloWorldUrn,
			$this->repo->putStream( $this->newHelloWorldStream(), 'blah', $this->helloWorldUrn )
		);
	}

	protected function _testPutStreamWithBadUrn($urn, $expectedExceptionClass) {
		$caught = null;
		try {
			$this->repo->putStream( $this->newHelloWorldStream(), 'blah', $urn );
			$this->fail("Should've thrown a hash-mismatch exception.");
		} catch( Exception $e ) {
			$caught = get_class($e);
		}
		$this->assertEquals($expectedExceptionClass, $caught);
	}
	
	public function testPutStreamWithInvalidUrn() {
		$this->_testPutStreamWithBadUrn($this->invalidUrn, 'TOGoS_PHPN2R_IdentifierFormatException');
	}
	
	public function testPutStreamWithWrongUrn() {
		$this->_testPutStreamWithBadUrn($this->wrongUrn, 'TOGoS_PHPN2R_HashMismatchException');
	}
}
