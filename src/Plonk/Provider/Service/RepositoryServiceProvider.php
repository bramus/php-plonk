<?php

namespace Plonk\Provider\Service;

/**
 * Repository Service Provider
 *
 * Registers all repositories onto $app with a `db.` prefix
 *
 * @REF https://raw.github.com/KnpLabs/RepositoryServiceProvider/master/Knp/Provider/RepositoryServiceProvider.php
 */
class RepositoryServiceProvider extends PimpleBasedServiceProvider
{
	public function registerInPimple(\Pimple $app)
	{
		// Nothing!
	}

	public function bootInPimple(\Pimple $app)
	{
		if (!$this->config || sizeof($this->config) === 0) {
			return;
		}

		foreach ($this->config as $label => $class) {
			$app['db.' . $label] = $app->share(function($app) use ($class) {
				return new $class($app);
			});
		}
	}
}
