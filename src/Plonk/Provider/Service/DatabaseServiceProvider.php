<?php

namespace Plonk\Provider\Service;

class DatabaseServiceProvider extends PimpleBasedServiceProvider
{
	public function registerInPimple(\Pimple $app)
	{
		// We require a default connection. It is so.
		if (!$this->config['default'] ?? null) {
			$app['dbs.config'] = null;
			$app['dbs'] = null;
			$app['db.config'] = null;
			$app['db'] = null;

			return;
		}

		// Store Config
		$app['dbs.config'] = $this->config;

		// Store Connections
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

	public function bootInPimple(\Pimple $app)
	{
		// Nothing!
	}
}
