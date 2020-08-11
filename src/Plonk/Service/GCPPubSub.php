<?php

namespace Plonk\Service;

class GCPPubSub {

	private $config;
	private $client;

	public function __construct(array $config = []) {
		$this->config = $config;
        $this->client = new \Google\Cloud\PubSub\PubSubClient($this->config);
        
		// Store Topic and Subscription
        $this->setTopic($this->config['topic'] ?? null);
        $this->setSubscription($this->config['subscription'] ?? null);
	}

	public function __call($name, $args) {
		return call_user_func_array([$this->client, $name], $args);
	}

    public function publishMessage(string $messageType = null, array $messageData = null) {
        if (!$this->getTopic()) {
            throw new \Exception('No topic is set');
		}

        return $this
			->topic($this->getTopic())
			->publish([
				'data' => $messageType ? \base64_encode($messageType) : null,
				'attributes' => $messageData,
			]);
	}
	
	public function pullMessage($returnImmediately = true, $autoAcknowledge = true) {
		$messages = $this->pullMessages(1, $returnImmediately, $autoAcknowledge);
		return $messages[0] ?? false;
	}

    public function pullMessages($maxMessages = 1, $returnImmediately = true, $autoAcknowledge = true) {
        if (!$this->getTopic()) {
            throw new \Exception('No topic is set');
		}
        if (!$this->getSubscription()) {
            throw new \Exception('No subscription is set');
		}

		$messages = $this
			->client
			->topic($this->getTopic())
			->subscription($this->getSubscription())
			->pull([
				'maxMessages' => $maxMessages,
				'returnImmediately' => $returnImmediately,
			]);

        if ((sizeof($messages)) > 0 && ($autoAcknowledge === true)) {
			$this->acknowledgeMessages($messages);
        }

		return $messages;
    }

	public function acknowledgeMessage($message) {
		return $this
			->topic($this->getTopic())
			->subscription($this->getSubscription())
			->acknowledge($message);
	}

	public function acknowledgeMessages($messages) {
		return $this
			->topic($this->getTopic())
			->subscription($this->getSubscription())
			->acknowledgeBatch($messages);
	}

	public function getSubscription() {
		return $this->config['subscription'] ?? null;
    }

	public function getTopic() {
		return $this->config['topic'] ?? null;
    }
    
    public function validateSubscription($subscriptionName) {
        try {
			if (!$this->client->topic($this->getTopic())->subscription($subscriptionName)->exists()) {
				throw new \Exception('The subscription does no exist');
			}
		} catch (\Exception $e) {
			throw new \Exception('The subscription ' . $subscriptionName . ' (on the topic ' ($this->getTopic() ?? 'NULL') . ') is inaccessible: ' . $e->getMessage());
		}

		// Chaining, yo!
		return $this;
    }
    
    public function validateTopic($topicName) {
        try {
			if (!$this->client->topic($topicName)->exists()) {
				throw new \Exception('The topic does no exist');
			}
		} catch (\Exception $e) {
			throw new \Exception('The topic ' . $topicName . ' is inaccessible: ' . $e->getMessage());
		}

		// Chaining, yo!
		return $this;
    }

	public function setTopic($topicName = null) {
		// Unset variable on config
		$this->config['topic'] = null;

		// Quite while you're ahead
		if ($topicName === null) return;

		// Validate variable
		$this->validateTopic($topicName);

		// Store variable
		$this->config['topic'] = $topicName;

		// Chaining, yo!
		return $this;
	}

	public function setSubscription($subscriptionName = null) {
		// Unset variable on config
		$this->config['subscription'] = null;

		// Quite while you're ahead
		if ($subscriptionName === null) return;

		// Validate variable
		$this->validateSubscription($subscriptionName);

		// Store variable
		$this->config['subscription'] = $subscriptionName;

		// Chaining, yo!
		return $this;
	}

}
