<?php

namespace Plonk\Runtime\PubSub\Push;

class Application extends \Plonk\Runtime\PubSub\Application {

    public $port = null;
    public $ip = '0.0.0.0';

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
            $errorStr .= '” while running pubsub handler "' . get_class($this->handler) . '" on topic "' . $this->handler->getTopicName();
        }

        $this['logger'] && $this['logger']->emergency($errorStr, [
            // @TODO: Include pubsub message?
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $stacktrace,
        ]);
    }

	/**
	 * Pull in a message from given subscription on a given topic and handle it by the given handler
	 *
	 * @return	void
	 */
    public function run() {

        // Create converters to convert between PSR
        // $httpFoundationFactory = new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();
        $psr7Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrHttpFactory = new \Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory($psr7Factory, $psr7Factory, $psr7Factory, $psr7Factory);

        // Create Handler
        $handler = new $this->handlerClassName($this);
        $this->handler = $handler;

        // Closure
        $application = $this;

        // Create Server
        $loop = \React\EventLoop\Factory::create();
        $server = new \React\Http\Server([
            function (\Psr\Http\Message\ServerRequestInterface $request) use ($application, $handler, $psrHttpFactory) {
                try {

                    // Only allow POST requests
                    if ($request->getMethod() !== 'POST') {
                        return new \React\Http\Response(
                            405,
                            ['Content-Type' => 'text/plain'],
                            'Invalid Request Method'
                        );
                    }

                    // $symfonyRequest =  $httpFoundationFactory->createRequest($request);
                    // $body = $symfonyRequest->getContent();
                    $body = (string) $request->getBody();

                    // Make sure we have JSON
                    try {
                        $json = \Concis\Util\Json::decode($body);
                    } catch (\Exception $e) {
                        return new \React\Http\Response(
                            400,
                            ['Content-Type' => 'text/plain'],
                            'Invalid JSON'
                        );
                    }

                    // Convert JSON from request into a PubSubEvent
                    try {
                        $event = \Concis\Util\ObjectFactory::fromString(
                            $body,
                            \Concis\Provider\GCPCloud\Pubsub\Datatype\PubsubEvent::class
                        );
                    } catch (\Exception $e) {
                        return new \React\Http\Response(
                            400,
                            ['Content-Type' => 'text/plain'],
                            'Invalid PubSub Event'
                        );
                    }

                    // Process the PubSubEvent using our Application
                    try {
                        $clonedHandler = clone $handler;
                        $response = $clonedHandler
                            ->with('message', $event->getMessage())
                            ->with('topicName', $event->getSubscription())
                            ->run();
                    } catch (\Exception $e) {
                        $application->handleException($e);
                        return new \React\Http\Response(
                            500,
                            ['Content-Type' => 'text/plain'],
                            'Could not process message: ' . $e->getMessage()
                        );
                    }

                    // Got a \Symfony\Component\HttpFoundation\Response as response?
                    // ~> Cast to \React\Http\Response and return that
                    if (is_a($response, \Symfony\Component\HttpFoundation\Response::class)) {
                        return $psrHttpFactory->createResponse($response);
                    }

                    // Got a \React\Http\Response? Return it
                    if (is_a($response, \React\Http\Response::class)) {
                        return $response;
                    }

                    // Other types?
                    return new \React\Http\Response(
                        200,
                        ['Content-Type' => 'text/plain'],
                        $response
                    );
                } catch (\Exception $e) {
                    $application->handleException($e);

                    return new \React\Http\Response(
                        500,
                        ['Content-Type' => 'text/plain'],
                        'Internal Server Error: ' . $e->getMessage()
                    );
                }
            },
        ]);

        // Listen on PORT
        $socket = new \React\Socket\Server("{$this->ip}:{$this->port}", $loop);
        $server->listen($socket);

        // Start Server
        $this['logger'] && $this['logger']->debug("Server running at http://{$this->ip}:{$this->port}\n");
        $loop->run();

    }
}