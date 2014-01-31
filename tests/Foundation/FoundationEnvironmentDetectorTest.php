<?php

use Mockery as m;

class FoundationEnvironmentDetectorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testEnvironmentDetection()
	{
		$env = m::mock('Fly\Foundation\EnvironmentDetector')->makePartial();
		$env->shouldReceive('isMachine')->once()->with('localhost')->andReturn(false);
		$result = $env->detect(array(
			'local'   => array('localhost')
		));
		$this->assertEquals('production', $result);


		$env = m::mock('Fly\Foundation\EnvironmentDetector')->makePartial();
		$env->shouldReceive('isMachine')->once()->with('localhost')->andReturn(true);
		$result = $env->detect(array(
			'local'   => array('localhost')
		));
		$this->assertEquals('local', $result);
	}


	public function testClosureCanBeUsedForCustomEnvironmentDetection()
	{
		$env = new Fly\Foundation\EnvironmentDetector;

		$result = $env->detect(function() { return 'foobar'; });
		$this->assertEquals('foobar', $result);
	}


	public function testConsoleEnvironmentDetection()
	{
		$env = new Fly\Foundation\EnvironmentDetector;

		$result = $env->detect(array(
			'local'   => array('foobar')
		), array('--env=local'));
		$this->assertEquals('local', $result);
	}

}