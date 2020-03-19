<?php

namespace Plonk\Runtime;

/**
 * Base Plonk Application class that accepts a config and an env.
 * Dependencies will be loaded at construction time.
 * 
 * Usage:
 * $app = new Application();
 * $app->boot();
 * $app->run();
 * 
 * Override loadDependencies($config, $env) to load extra dependencies
 */
abstract class Application extends \Pimple {

	protected $env;
	protected $config;

	public $loggerIdentifier = false;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct($config, $env) {
		$this->storeConfig($config);
		$this->storeEnv($env);
	}

	/**
	 * Stores the config as a datamember and as parameters onto itself
	 * @param  [type] $config [description]
	 * @return [type]         [description]
	 */
	protected function storeConfig($config) {
		$this->config = $config;

        // @TODO: envify?

		foreach ($config as $configKey => $configValue) {
			$this[$configKey] = $configValue;
		}
	}

	/**
	 * Stores the env as datamaber and as a parameter onto itself
	 * @param  [type] $env [description]
	 * @return [type]      [description]
	 */
	protected function storeEnv($env) {
		$this->env = $env;
		$this['env'] = $env;

		if (!in_array($env, $this->config['conf.environments'])) {
			exit('INVALID APP_ENV "' . $env . '"' . PHP_EOL);
		}
	}

	/**
	 * Load in all required dependencies
	 * @param  array $config The Configuration Array
	 * @return void
	 */
	protected function loadDependencies($config, $env) {

		// Store debug flag
		$this['debug'] = $config['conf.debug'][$env];

		// Monolog
        // @TODO: loggerIdentifier override
		// if ($this->loggerIdentifier) {
		// 	$config['conf.logger'][$env]['name'] = $this->loggerIdentifier;
		// }
		$this->register(new \Plonk\Provider\Service\LoggerServiceProvider($config['conf.logger'][$env] ?? null));

		// @TODO: Other default dependencies?
		// - Database
		// - Twig
		// - …

		// Database Repositories
		$this->register(new \Plonk\Provider\Service\RepositoryServiceProvider($config['conf.repositories'] ?? []));

	}

	/**
	 * Borrowed from the Silex Core: Registers a service provider.
	 *
	 * @param ServiceProviderInterface $provider A \Silex\ServiceProviderInterface instance
	 * @param array                    $values   An array of values that customizes the provider
	 *
	 * @return Cronjob
	 */
	public function register(\Silex\ServiceProviderInterface $provider, array $values = array()) {
		$this->providers[] = $provider;

		$provider->registerInPimple($this);

		foreach ($values as $key => $value) {
			$this[$key] = $value;
		}

		return $this;
	}

	/**
	 * Boots the service Providers
	 * @return [type] [description]
	 */
	protected function bootProviders() {
		if (sizeof($this->providers) > 0) {
			foreach ($this->providers as $provider) {
				$provider->bootInPimple($this);
			}
		}
	}

	/**
	 * You know boot, right before run …
	 * @return [type] [description]
	 */
	public function boot() {
		$this->loadDependencies($this->config, $this->env);
		$this->bootProviders();

		return $this;
	}

	public function with($name, $value) {
		if (in_array($name, ['config', 'env'])) {
			throw new \Exception('You cannot set config or env on an Application, as they are reserved');
		}
		$this->$name = $value;
		return $this;
	}

	abstract public function run();

}