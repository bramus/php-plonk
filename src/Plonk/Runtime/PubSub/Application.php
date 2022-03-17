<?php

namespace Plonk\Runtime\PubSub;

abstract class Application extends \Plonk\Runtime\Application
{
	/* string */ public $handlerClassName = null;
	/* string */ public $topicName = null;
	/* Handler */ protected $handler = null;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct(array $config, string $env)
	{
		set_exception_handler([$this, 'handleException']);

		parent::__construct($config, $env);
	}

	/**
	 * Load in all required dependencies
	 * @param  array $config The Configuration Array
	 * @return void
	 */
	protected function loadDependencies($config)
	{
		parent::loadDependencies($config);

		// Load in HanderClass' dependencies
		$this->handlerClassName::loadDependencies($this, $config);
	}

	/**
	 * Log exceptions using logger
	 *
	 * @return void
	 */
	abstract public function handleException($e);
}
