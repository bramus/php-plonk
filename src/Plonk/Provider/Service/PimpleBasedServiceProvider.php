<?php

namespace Plonk\Provider\Service;

use Silex\ServiceProviderInterface;
use Silex\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PimpleBasedServiceProvider
 * 
 * Allows one to register/boot service providers in a \Pimple-based instance, 
 * instead of in a \Silex\Application instance (which is a \Pimple-based instance itself)
 */
abstract class PimpleBasedServiceProvider implements ServiceProviderInterface
{

    protected $config;

    /**
     * Constructor
     * 
     * Store the config for future use.
     */
	public function __construct($config = null) {
		$this->config = $config;
	}

    // @note: we proxy boot()and register() to registerInPimple()/bootInPimple().
    // We do this because that way we can also use this ServiceProvider within
    // classes that extend \Pimple (just like \Silex\Application does)
    public function register(Application $app)
    {
        $this->registerInPimple($app);
    }

    public function boot(Application $app)
    {
        $this->bootInPimple($app);
    }

    abstract public function registerInPimple(\Pimple $app);
    abstract public function bootInPimple(\Pimple $app);
}