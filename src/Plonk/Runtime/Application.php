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

	private $booted = false;

	public $loggerIdentifier = false;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct($config, $env) {
		$this->validateEnv($env, $config['conf.environments'] ?? []);

		$this->storeEnv($env);
		$this->storeConfig($config);
	}

	protected function validateEnv($env, array $envs = []) {
		if (sizeof($envs) && !in_array($env, $envs)) {
			throw new \Exception('INVALID APP_ENV "' . $env . '"');
		}
	}

	/**
	 * Stores the config as a datamember and as parameters onto itself
	 * @param  [type] $config [description]
	 * @return [type]         [description]
	 */
	protected function storeConfig($config) {

		// Envify Settings
		if (isset($config['conf.environments']) && sizeof($config['conf.environments'])) {
            $config = \Plonk\Util\Config::envify($config, $this->env, $config['conf.environments']);
        }

		// Store it
		$this->config = $config;

		// Inject
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
	}

	/**
	 * Load in all required dependencies
	 * @param  array $config The Configuration Array
	 * @return void
	 */
	protected function loadDependencies($config) {

		// Store debug flag
		$this['debug'] = $config['conf.debug'];

		// Monolog
        // @TODO: loggerIdentifier override
		// if ($this->loggerIdentifier) {
		// 	$config['conf.logger']['name'] = $this->loggerIdentifier;
		// }
		$this->register(new \Plonk\Provider\Service\LoggerServiceProvider($config['conf.logger'] ?? null));

		// @TODO: Other default dependencies?
		// - Twig
		// - …



        // Database Connection(s)
        $dbConfig = $config['conf.db'] ?? null;
        $dbsConfig = $config['conf.dbs'] ?? null;
        if (!$dbsConfig && $dbConfig) {
            $dbsConfig = [
                'default' => $dbConfig,
            ];
        }

        if (!$dbsConfig) {
            $app['dbs.config'] = null;
            $app['dbs'] = null;
        } else {
            $app['dbs.config'] = $dbsConfig;
            $app['dbs'] = $app->share(function($app) {
                $dbs = new \Pimple();
                foreach ($app['dbs.config'] as $name => $dbConfig) {
                    $dbs[$name] = $app->share(function() use ($dbConfig) {
                        return \Doctrine\DBAL\DriverManager::getConnection($dbConfig, new \Doctrine\DBAL\Configuration());
                    });
                    // $app['db']->connect(); // Test connection upfront, to detect failures on boot …
                }

                return $dbs;
            });

            // Add Shortcuts to default DB
            $app['db'] = $app->share(function() use ($app) {
                return $app['dbs']['default'];
            });
            $app['db.config'] = function() use ($app) {
                return $app['dbs.config']['default'];
            };
        }

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

	public function isBooted() {
		return $this->booted;
	}

	/**
	 * You know boot, right before run …
	 * @return [type] [description]
	 */
	public function boot() {
        if (!$this->isBooted()) {
            $this->loadDependencies($this->config, $this->env);
            $this->bootProviders();
			$this->booted = true;
        }

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