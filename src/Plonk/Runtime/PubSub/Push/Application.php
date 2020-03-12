<?php

namespace Plonk\Runtime\PubSub\Push;

class Application extends \Plonk\Runtime\PubSub\Application {

    public $message = null;

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
        // @TODO: validate params being present
        $this->handler = new $this->handlerClassName($this, $this->topicName, $this->message);
        $this->handler->run();
    }

}