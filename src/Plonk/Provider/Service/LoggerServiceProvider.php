<?php

namespace Plonk\Provider\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoggerServiceProvider extends PimpleBasedServiceProvider {

	public function registerInPimple(\Pimple $app) {
		// Logger not enabled?
		// ~> Quit while you're ahead
		if (!$this->config || !$this->config['enabled']) {
			$app['monolog'] = false;
			$app['logger'] = false;
			return;
		}

		// Create logger
		$loggerConfig = $this->config;
		$app['monolog'] = $app->share(function() use ($app, $loggerConfig) {
			$logger = new \Monolog\Logger($loggerConfig['name']);

			switch ($loggerConfig['path']) {
				case 'php://stdout':
					$handler = new \Monolog\Handler\StreamHandler($loggerConfig['path'], $loggerConfig['level']);
					$handler->setFormatter(new \Bramus\Monolog\Formatter\ColoredLineFormatter());
					break;

				case 'gcs://stackdriver':
					if (!isset($app['conf.stackdriver']) || !isset($app['conf.stackdriver'][$app['env']])) {
						throw new \Exception("LoggerServiceProvider: `gcs://stackdriver` is defined as the logger, but no `conf.stackdriver` is present in the config", 1);
					}
					$handler = new \CodeInternetApplications\MonologStackdriver\StackdriverHandler(
						$app['conf.stackdriver'][$app['env']]['projectId'],
						[
							'keyFilePath' => $app['conf.stackdriver'][$app['env']]['keyFilePath'],
						],
						[
							'labels' => [
								'logger' => $loggerConfig['name'],
							],
						],
						'stackdriver',
						'%message%',
						$loggerConfig['level']
					);
					break;

				default:
					$handler = new \Monolog\Handler\RotatingFileHandler($loggerConfig['path'], $loggerConfig['level']);
					$handler->setFormatter(new \Bramus\Monolog\Formatter\ColoredLineFormatter());
					break;
			}

			$logger->pushHandler($handler);
			return $logger;
		});

		// Create logger alias
		$app['logger'] = function() use ($app) {
            return $app['monolog'];
        };
	}

	public function bootInPimple(\Pimple $app) {
		// Logger not enabled?
		// ~> Quit while you're ahead
		if (!$this->config || !$this->config['enabled']) {
			return;
		}

		// Inject error handler to log exception messages (in case of a Silex Application)
		if (get_class($app) === '\Silex\Application') {
			$app->error(function (\Exception $e, $code) use ($app) {
				if (in_array($code, [500, 501])) {
					$app['monolog']->addCritical($e->getMessage());
				}
			}, 100);
		}
	}

}