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

        // Create Handler
        $this->handler = new $this->handlerClassName($this);

        // Create Event Loop
        $eventLoop = \React\EventLoop\Factory::create();

        // Create callback to execute
        $app = $this;
        $callback = function() use ($app, $eventLoop, &$callback) {

            // Get PullConfig
            $pullConfig = $app->handler->getPullConfig();

            // Pull messages from queue
            $app['logger'] && $app['logger']->debug("Pulling {$pullConfig['maxMessages']} message(s) from Subscription");
            $messages = $app['pubsub']->pullMessages(...array_values($pullConfig));
            $app['logger'] && $app['logger']->debug("Pulled " . sizeof($messages) . " messages");

            // Run handler with proper settings
            foreach ($messages as $message) {

                $app['logger'] && $app['logger']->debug("Sending message " . $message->id() . " to handler");
                
                // Process the message
                $app->handler
                    ->with('topicName', $app->topicName)
                    ->with('subscriptionName', $app->subscriptionName)
                    ->with('message', $message)
                    ->run();

                // ACK the message (if not auto-ACK'd)
                if ($pullConfig['autoAcknowledge'] === false) {
                    $app['logger'] && $app['logger']->debug("Acknowledging message " . $message->id() . "");
                    $app['pubsub']->acknowledgeMessage($message);
                    $app['logger'] && $app['logger']->debug("Acknowledged message " . $message->id() . "");
                }

            }

            // Re-start, if needed
            if ($app->handler->shouldLoopAndPull()) {
                $eventLoop->futureTick($callback);
            }

        };

        // Schedule the callback
        $eventLoop->futureTick($callback);

        // Start!
        $eventLoop->run();

        // @TODO: Catch scenarios where shouldLoopAndPull() is true but the loop has stopped

        $app['logger'] && $app['logger']->debug("Done");

    }

}