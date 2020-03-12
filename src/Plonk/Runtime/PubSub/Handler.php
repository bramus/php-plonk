<?php

namespace Plonk\Runtime\PubSub;

abstract class Handler
{
	/**
	 * Load in extra required dependencies onto app, if need be
	 * @param  Application $app The Application
	 * @param  array $config The Configuration Array
	 * @param  string $env The environment
	 * @return void
	 */
	public static function loadDependencies($app, $config, $env)
    {
        // NOOP
    }

    abstract public function run();
}