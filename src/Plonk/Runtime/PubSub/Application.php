<?php

namespace Plonk\Runtime\PubSub;

abstract class Application extends \Plonk\Runtime\Application {

    public /* string */ $handlerClassName = null;
    public /* string */ $topicName = null;
    protected /* Handler */ $handler = null;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct(array $config, string $env) {
        set_exception_handler([$this, 'handleException']);

        parent::__construct($config, $env);
	}

	/**
	 * Load in all required dependencies
	 * @param  array $config The Configuration Array
	 * @return void
	 */
	protected function loadDependencies($config, $env) {
        parent::loadDependencies($config, $env);

        // Load in HanderClass' dependencies
        $this->handlerClassName::loadDependencies($this, $config, $env);
    }

    /**
     * Log exceptions using logger
     * 
     * @return void
     */
	abstract public function handleException($e);

}