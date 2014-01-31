<?php

use Mockery as m;

class ProviderRepositoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testServicesAreRegisteredWhenManifestIsNotRecompiled()
	{
		$repo = m::mock('Fly\Foundation\ProviderRepository[createProvider,loadManifest,shouldRecompile]', array(m::mock('Fly\Filesystem\Filesystem'), array(__DIR__)));
		$repo->shouldReceive('loadManifest')->once()->andReturn(array('when' => array(), 'eager' => array('foo'), 'deferred' => array('deferred'), 'providers' => array('providers')));
		$repo->shouldReceive('shouldRecompile')->once()->andReturn(false);
		$app = m::mock('Fly\Foundation\Application')->makePartial();
		$provider = m::mock('Fly\Support\ServiceProvider');
		$repo->shouldReceive('createProvider')->once()->with($app, 'foo')->andReturn($provider);
		$app->shouldReceive('register')->once()->with($provider);
		$app->shouldReceive('runningInConsole')->andReturn(false);
		$app->shouldReceive('setDeferredServices')->once()->with(array('deferred'));

		$repo->load($app, array());
	}


	public function testServicesAreNeverLazyLoadedWhenRunningInConsole()
	{
		$repo = m::mock('Fly\Foundation\ProviderRepository[createProvider,loadManifest,shouldRecompile]', array(m::mock('Fly\Filesystem\Filesystem'), array(__DIR__)));
		$repo->shouldReceive('loadManifest')->once()->andReturn(array('when' => array(), 'eager' => array('foo'), 'deferred' => array('deferred'), 'providers' => array('providers')));
		$repo->shouldReceive('shouldRecompile')->once()->andReturn(false);
		$app = m::mock('Fly\Foundation\Application')->makePartial();
		$provider = m::mock('Fly\Support\ServiceProvider');
		$repo->shouldReceive('createProvider')->once()->with($app, 'providers')->andReturn($provider);
		$app->shouldReceive('register')->once()->with($provider);
		$app->shouldReceive('runningInConsole')->andReturn(true);
		$app->shouldReceive('setDeferredServices')->once()->with(array('deferred'));

		$repo->load($app, array());
	}


	public function testManifestIsProperlyRecompiled()
	{
		$repo = m::mock('Fly\Foundation\ProviderRepository[createProvider,loadManifest,writeManifest,shouldRecompile]', array(m::mock('Fly\Filesystem\Filesystem'), array(__DIR__)));
		$app = m::mock('Fly\Foundation\Application');

		$repo->shouldReceive('loadManifest')->once()->andReturn(array('eager' => array(), 'deferred' => array('deferred')));
		$repo->shouldReceive('shouldRecompile')->once()->andReturn(true);

		// foo mock is just a deferred provider
		$repo->shouldReceive('createProvider')->once()->with($app, 'foo')->andReturn($fooMock = m::mock('StdClass'));
		$fooMock->shouldReceive('isDeferred')->once()->andReturn(true);
		$fooMock->shouldReceive('provides')->once()->andReturn(array('foo.provides1', 'foo.provides2'));
		$fooMock->shouldReceive('when')->once()->andReturn(array('when-event'));

		// bar mock is added to eagers since it's not reserved
		$repo->shouldReceive('createProvider')->once()->with($app, 'bar')->andReturn($barMock = m::mock('Fly\Support\ServiceProvider'));
		$barMock->shouldReceive('isDeferred')->once()->andReturn(false);
		$barMock->shouldReceive('when')->never();
		$repo->shouldReceive('writeManifest')->once()->andReturnUsing(function($manifest) { return $manifest; });

		// registers the when events
		$app->shouldReceive('make')->with('events')->andReturn($events = m::mock('StdClass'));
		$events->shouldReceive('listen')->once()->with(array('when-event'), m::type('Closure'));

		// bar mock should be registered with the application since it's eager
		$repo->shouldReceive('createProvider')->once()->with($app, 'bar')->andReturn($barMock);
		$app->shouldReceive('register')->once()->with($barMock);

		$app->shouldReceive('runningInConsole')->andReturn(false);

		// the deferred should be set on the application
		$app->shouldReceive('setDeferredServices')->once()->with(array('foo.provides1' => 'foo', 'foo.provides2' => 'foo'));

		$manifest = $repo->load($app, array('foo', 'bar'));
	}


	public function testShouldRecompileReturnsCorrectValue()
	{
		$repo = new Fly\Foundation\ProviderRepository(new Fly\Filesystem\Filesystem, __DIR__);
		$this->assertTrue($repo->shouldRecompile(null, array()));
		$this->assertTrue($repo->shouldRecompile(array('providers' => array('foo')), array('foo', 'bar')));
		$this->assertFalse($repo->shouldRecompile(array('providers' => array('foo')), array('foo')));
	}


	public function testLoadManifestReturnsParsedJSON()
	{
		$repo = new Fly\Foundation\ProviderRepository($files = m::mock('Fly\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/services.json')->andReturn(true);
		$files->shouldReceive('get')->once()->with(__DIR__.'/services.json')->andReturn(json_encode($array = array('users' => array('dayle' => true))));
		$app = new Fly\Foundation\Application;
		$array['when'] = array();

		$this->assertEquals($array, $repo->loadManifest());
	}


	public function testWriteManifestStoresToProperLocation()
	{
		$repo = new Fly\Foundation\ProviderRepository($files = m::mock('Fly\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('put')->once()->with(__DIR__.'/services.json', json_encode(array('foo')));
		$app = new Fly\Foundation\Application;

		$result = $repo->writeManifest(array('foo'));

		$this->assertEquals(array('foo'), $result);
	}

}
