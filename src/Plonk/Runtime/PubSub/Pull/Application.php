<?php

namespace Plonk\Runtime\PubSub\Pull;

class Application extends \Plonk\Runtime\PubSub\Application {

    public $subscriptionName = null;
    private $eventLoop = null;
    public $shutdownRequested = false;

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

        // PubSub Topics
        if ($config['conf.pubsub.topics'] ?? null) {
            foreach ($config['conf.pubsub.topics'] as $identifier => $topic) {
                $this['pubsub.' . $identifier] = $this->share(function () use ($pubsubConfig, $topic) {
                    $instance = new \Plonk\Service\GCPPubSub($pubsubConfig);
                    $instance->setTopic($topic);
                    return $instance;
                });
            }
        }

    }

	/**
	 * Pull in a message from given subscription on a given topic and handle it by the given handler
	 *
	 * @return	void
	 */
    public function run() {

        // Graceful Shutdown
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, [$this, 'shutdown']);
        pcntl_signal(SIGTERM, [$this, 'shutdown']);
        // pcntl_signal(SIGKILL, [$this, 'shutdown']); // “Fatal error: Error installing signal handler for 9”

        // Start Server
        $this['logger'] && $this['logger']->debug("Starting with processing of PubSub topic {$this->topicName} using Pull Subscription {$this->subscriptionName}\n");

        // Configure PubSub connection
        $this['pubsub']
            ->setTopic($this->topicName)
            ->setSubscription($this->subscriptionName);

        // Create Handler
        $this->handler = new $this->handlerClassName($this);

        // Create Event Loop
        $this->eventLoop = \React\EventLoop\Factory::create();

        // Create callback to execute
        $app = $this;
        $callback = function() use ($app, &$callback) {

            // Get Required shorthands
            $pullConfig = $app->handler->getPullConfig();
            $eventLoop = $app->eventLoop;

            if (!$eventLoop || $app->shutdownRequested) {
                return;
            }

            // Pull messages from queue
            // $app['logger'] && $app['logger']->debug("Pulling {$pullConfig['maxMessages']} message(s) from Subscription");
            $messages = $app['pubsub']->pullMessages(...array_values($pullConfig));
            $app['logger'] && $app['logger']->debug("Pulled " . sizeof($messages) . " messages from Subscription");

            // Run handler with proper settings
            foreach ($messages as $message) {

                $app['logger'] && $app['logger']->debug("Passing message " . $message->id() . " to handler");

                // @TODO: Convert $message to a \Concis\Provider\GCPCloud\Pubsub\Datatype\PubsubMessage instance
                // @TODO bis: Adjust Concis class so that it doesn't base64_decode …
                // $message = $message->toArray();
                // $message['data'] = \base64_encode($message['data']);
                // $message = \Concis\Util\ObjectFactory::fromArray(
                //     $message,
                //     \Concis\Provider\GCPCloud\Pubsub\Datatype\PubsubMessage::class
                // );
                
                // Process the message
                try {
                    $processed = $app->handler
                        ->with('topicName', $app->topicName)
                        ->with('subscriptionName', $app->subscriptionName)
                        ->with('message', $message)
                        ->run();
                } catch (\Exception $e) {
                    $app['logger'] && $app['logger']->critical($e->getMessage());
                    $processed = false;
                }

                // ACK the message (if not auto-ACK'd)
                if ($pullConfig['autoAcknowledge'] === false) {
                    if ($processed) {
                        // $app['logger'] && $app['logger']->debug("Acknowledging message " . $message->id() . "");
                        $app['pubsub']->acknowledgeMessage($message);
                        $app['logger'] && $app['logger']->debug("Acknowledged message " . $message->id() . " (ACK ID " . $message->ackId() . ")");
                    } else {
                        $app['logger'] && $app['logger']->warn("Did not acknowledge message " . $message->id() . " because it was not processed.");
                    }
                }

            }

            // Re-start, if needed
            if (!$app->shutdownRequested && $app->handler->shouldLoopAndPull()) {
                $eventLoop->futureTick($callback);
            }

        };

        // Schedule the callback
        $this->eventLoop->futureTick($callback);

        // Start!
        $this->eventLoop->run();

        // @TODO: Catch scenarios where shouldLoopAndPull() is true but the loop has stopped

        $app['logger'] && $app['logger']->info("EXITED");

    }

    public function shutdown($signal) {   
        switch($signal) {
            case SIGTERM:
                $this['logger'] && $this['logger']->info("Shutting down due to received SIGTERM");
            case SIGINT:
                $this['logger'] && $this['logger']->info("Shutting down due to received SIGINT");
        }

        if ($this->eventLoop) {
            $this->eventLoop->stop();
        }

        $this->eventLoop = null;
        $this->shutdownRequested = true;
    }

}