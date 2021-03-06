<?php

use Mockery as m;

class SessionStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSessionIsLoadedFromHandler()
	{
		$session = $this->getSession();
		$session->getHandler()->shouldReceive('read')->once()->with(1)->andReturn(serialize(array('foo' => 'bar', 'bagged' => array('name' => 'allan'))));
		$session->registerBag(new Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag('bagged'));
		$session->start();

		$this->assertEquals('bar', $session->get('foo'));
		$this->assertEquals('baz', $session->get('bar', 'baz'));
		$this->assertTrue($session->has('foo'));
		$this->assertFalse($session->has('bar'));
		$this->assertEquals('allan', $session->getBag('bagged')->get('name'));
		$this->assertInstanceOf('Symfony\Component\HttpFoundation\Session\Storage\MetadataBag', $session->getMetadataBag());
		$this->assertTrue($session->isStarted());

		$session->put('baz', 'boom');
		$this->assertTrue($session->has('baz'));
	}

	public function testSessionGetBagException()
	{
		$this->setExpectedException('InvalidArgumentException');
		$session = $this->getSession();
		$session->getBag('doesNotExist');
	}

	public function testSessionMigration()
	{
		$session = $this->getSession();
		$oldId = $session->getId();
		$session->getHandler()->shouldReceive('destroy')->never();
		$this->assertTrue($session->migrate());
		$this->assertFalse($oldId == $session->getId());


		$session = $this->getSession();
		$oldId = $session->getId();
		$session->getHandler()->shouldReceive('destroy')->once()->with($oldId);
		$this->assertTrue($session->migrate(true));
		$this->assertFalse($oldId == $session->getId());
	}

	public function testSessionRegeneration()
	{
		$session = $this->getSession();
		$oldId = $session->getId();
		$session->getHandler()->shouldReceive('destroy')->never();
		$this->assertTrue($session->regenerate());
		$this->assertFalse($oldId == $session->getId());
	}


	public function testSessionInvalidate()
	{
		$session = $this->getSession();
		$oldId = $session->getId();
		$session->set('foo','bar');
		$this->assertTrue(count($session->all()) > 0);
		$session->getHandler()->shouldReceive('destroy')->never();
		$this->assertTrue($session->invalidate());
		$this->assertFalse($oldId == $session->getId());
		$this->assertTrue(count($session->all()) == 0);
	}

	public function testSessionIsProperlySaved()
	{
		$session = $this->getSession();
		$session->getHandler()->shouldReceive('read')->once()->andReturn(serialize(array()));
		$session->start();
		$session->put('foo', 'bar');
		$session->flash('baz', 'boom');
		$session->getHandler()->shouldReceive('write')->once()->with(1, serialize(array(
			'_token' => $session->token(),
			'foo' => 'bar',
			'baz' => 'boom',
			'flash' => array(
				'new' => array(),
				'old' => array('baz'),
			),
			'_sf2_meta' => $session->getBagData('_sf2_meta'),
		)));
		$session->save();

		$this->assertFalse($session->isStarted());
	}


	public function testOldInputFlashing()
	{
		$session = $this->getSession();
		$session->put('boom', 'baz');
		$session->flashInput(array('foo' => 'bar', 'bar' => 0));

		$this->assertTrue($session->hasOldInput('foo'));
		$this->assertEquals('bar', $session->getOldInput('foo'));
		$this->assertEquals(0, $session->getOldInput('bar'));
		$this->assertFalse($session->hasOldInput('boom'));

		$session->ageFlashData();

		$this->assertTrue($session->hasOldInput('foo'));
		$this->assertEquals('bar', $session->getOldInput('foo'));
		$this->assertEquals(0, $session->getOldInput('bar'));
		$this->assertFalse($session->hasOldInput('boom'));
	}


	public function testDataFlashing()
	{
		$session = $this->getSession();
		$session->flash('foo', 'bar');
		$session->flash('bar', 0);

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('bar', $session->get('foo'));
		$this->assertEquals(0, $session->get('bar'));

		$session->ageFlashData();

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('bar', $session->get('foo'));
		$this->assertEquals(0, $session->get('bar'));

		$session->ageFlashData();

		$this->assertFalse($session->has('foo'));
		$this->assertEquals(null, $session->get('foo'));
	}


	public function testDataMergeNewFlashes()
	{
		$session = $this->getSession();
		$session->flash('foo', 'bar');
		$session->set('fu', 'baz');
		$session->set('flash.old', array('qu'));
		$this->assertTrue(array_search('foo', $session->get('flash.new')) !== false);
		$this->assertTrue(array_search('fu', $session->get('flash.new')) === false);
		$session->keep(array('fu','qu'));
		$this->assertTrue(array_search('foo', $session->get('flash.new')) !== false);
		$this->assertTrue(array_search('fu', $session->get('flash.new')) !== false);
		$this->assertTrue(array_search('qu', $session->get('flash.new')) !== false);
		$this->assertTrue(array_search('qu', $session->get('flash.old')) === false);
	}


	public function testReflash()
	{
		$session = $this->getSession();
		$session->flash('foo', 'bar');
		$session->set('flash.old', array('foo'));
		$session->reflash();
		$this->assertTrue(array_search('foo', $session->get('flash.new')) !== false);
		$this->assertTrue(array_search('foo', $session->get('flash.old')) === false);
	}


	public function testReplace()
	{
		$session = $this->getSession();
		$session->set('foo', 'bar');
		$session->set('qu', 'ux');
		$session->replace(array('foo'=>'baz'));
		$this->assertTrue($session->get('foo') == 'baz');
		$this->assertTrue($session->get('qu') == 'ux');
	}


	public function testRemove()
	{
		$session = $this->getSession();
		$session->set('foo', 'bar');
		$pulled = $session->remove('foo');
		$this->assertFalse($session->has('foo'));
		$this->assertTrue($pulled == 'bar');
	}


	public function testClear()
	{
		$session = $this->getSession();
		$session->set('foo', 'bar');

		$bag = new Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag('bagged');
		$bag->set('qu', 'ux');
		$session->registerBag($bag);

		$session->clear();
		$this->assertFalse($session->has('foo'));
		$this->assertFalse($session->getBag('bagged')->has('qu'));

		$session->set('foo', 'bar');
		$session->getBag('bagged')->set('qu', 'ux');

		$session->flush();
		$this->assertFalse($session->has('foo'));
		$this->assertFalse($session->getBag('bagged')->has('qu'));
	}


	public function testHasOldInputWithoutKey()
	{
		$session = $this->getSession();
		$session->flash('boom', 'baz');
		$this->assertFalse($session->hasOldInput());

		$session->flashInput(array('foo' => 'bar'));
		$this->assertTrue($session->hasOldInput());
	}

	public function testHandlerNeedsRequest()
	{
		$session = $this->getSession();
		$this->assertFalse($session->handlerNeedsRequest());
		$session->getHandler()->shouldReceive('setRequest')->never();

		$session = new \Fly\Session\Store('test', m::mock(new \Fly\Session\CookieSessionHandler(new \Fly\Cookie\CookieJar(), 60)));
		$this->assertTrue($session->handlerNeedsRequest());
		$session->getHandler()->shouldReceive('setRequest')->once();
		$request = new \Symfony\Component\HttpFoundation\Request();
		$session->setRequestOnHandler($request);
	}


	public function testToken()
	{
		$session = $this->getSession();
		$this->assertTrue($session->token() == $session->getToken());
	}


	public function testName()
	{
		$session = $this->getSession();
		$this->assertEquals($session->getName(), $this->getSessionName());
		$session->setName('foo');
		$this->assertEquals($session->getName(), 'foo');
	}

	public function getSession()
	{
		$reflection = new ReflectionClass('Fly\Session\Store');
		return $reflection->newInstanceArgs($this->getMocks());
	}


	public function getMocks()
	{
		return array(
			$this->getSessionName(),
			m::mock('SessionHandlerInterface'),
			'1'
		);
	}

	public function getSessionName()
	{
		return 'name';
	}

}