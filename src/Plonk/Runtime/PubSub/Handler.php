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

	public function with($name, $value) {
		if (in_array($name, ['config', 'env'])) {
			throw new \Exception('You cannot set config or env on an Handler, as they are reserved');
		}
		$this->$name = $value;
		return $this;
	}

    abstract public function run();
}