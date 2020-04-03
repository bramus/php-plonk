<?php

namespace Plonk\Runtime\PubSub\Pull;

class Application extends \Plonk\Runtime\PubSub\Application {

    public $subscriptionName = null;

    /**
     * Log exceptions using logger
     * 
     * @return void
     */
	public function handleException($e) {
		// Obfuscate stacktrace
		$stacktrace = $e->getTrace();
		if (sizeof($stacktrace)) {
			foreach ($stacktrace as &$entry) {
				if (isset($entry['args'])) {
					$entry['args'] = 'XXXXXXXX';
				}
			}
		}

        // Log it
        $errorStr = '✗ Uncaught Exception “' . $e->getMessage();
        if ($this->handler) {
            $errorStr .= '” while running pubsub handler "' . get_class($this->handler) . '" on topic "' . $this->handler->getTopicName() . ' using subscription "' . $this->handler->getSubscriptionName() . '"';
        }

        $this['logger'] && $this['logger']->emergency($errorStr, [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $stacktrace,
        ]);
    }

	/**
	 * Load in all required dependencies
	 * @param  array $config The Configuration Array
	 * @return void
	 */
	protected function loadDependencies($config) {
        parent::loadDependencies($config);

        // GCPPubSub
        $pubsubConfig = $config['conf.pubsub'];
        $this['pubsub'] = $this->share(function () use ($pubsubConfig) {
            return new \Plonk\Service\GCPPubSub($pubsubConfig);
        });

    }

	/**
	 * Pull in a message from given subscription on a given topic and handle it by the given handler
	 *
	 * @return	void
	 */
    public function run() {
        // @TODO: validate params being present
        $this->handler = new $this->handlerClassName($this, $this->topicName, $this->subscriptionName);
        return $this->handler->run();
    }

}