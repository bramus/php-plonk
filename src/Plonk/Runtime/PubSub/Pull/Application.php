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

        // Start Server
        $this['logger'] && $this['logger']->debug("Starting with processing of PubSub topic {$this->topicName} using Pull Subscription {$this->subscriptionName}\n");

        // Configure PubSub connection 
        $this['pubsub']
            ->setTopic($this->topicName)
            ->setSubscription($this->subscriptionName);

        // Pull message from queue
        $message = $this['pubsub']->pullMessage();

        // @TODO: validate params being present

        // Create Handler
        $this->handler = new $this->handlerClassName($this);

        // Run handler with proper settings
        return $this->handler
            ->with('topicName', $this->topicName)
            ->with('subscriptionName', $this->subscriptionName)
            ->with('message', $message)
            ->run();

    }

}