<?php

use Fly\Foundation\AliasLoader;

class FoundationAliasLoaderTest extends PHPUnit_Framework_TestCase {

	public function testLoaderCanBeCreatedAndRegisteredOnce()
	{
		$loader = $this->getMock('Fly\Foundation\AliasLoader', array('prependToLoaderStack'), array(array('foo' => 'bar')));
		$loader->expects($this->once())->method('prependToLoaderStack');

		$this->assertEquals(array('foo' => 'bar'), $loader->getAliases());
		$this->assertFalse($loader->isRegistered());
		$loader->register();
		$loader->register();
		$this->assertTrue($loader->isRegistered());
	}


	public function testGetInstanceCreatesOneInstance()
	{
		$loader = AliasLoader::getInstance(array('foo' => 'bar'));
		$this->assertEquals($loader, AliasLoader::getInstance());
	}

}