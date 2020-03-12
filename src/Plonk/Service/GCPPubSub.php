<?php

namespace Plonk\Service;

class GCPPubSub {

	private $config;
	private $client;

	public function __construct(array $config = []) {
		$this->config = $config;
        $this->client = new \Google\Cloud\PubSub\PubSubClient($this->config);
        
        if ($this->config['topic'] ?? null) {
            $this->validateTopic($this->config['topic']);
        }
        
        if ($this->config['subscription'] ?? null) {
            $this->validateSubscription($this->config['subscription']);
        }
	}

	public function __call($name, $args) {
		return call_user_func_array([$this->client, $name], $args);
	}

    public function publishMessage(string $messageType = null, array $messageData = null) {
        if (!$this->config['topic'] ?? null) {
            throw new \Exception('No topic is set');
		}

        return $this->topic($this->config['topic'])->publish([
			'data' => $messageType ? \base64_encode($messageType) : null,
			'attributes' => $messageData,
		]);
	}
	
	public function pullMessage($returnImmediately = true, $autoAcknowledge = true) {
		$messages = $this->pullMessages(1, $returnImmediately);
		$message = $messages[0] ?? null;

		if ($message) {
			if ($autoAcknowledge === true) {
				$this
					->topic($this->config['topic'])
					->subscription($this->config['subscription'])
					->acknowledge($message);
			}
			return $message;
		}

		return false;
	}

    public function pullMessages($maxMessages = 1, $returnImmediately = true) {
        if (!$this->config['topic'] ?? null) {
            throw new \Exception('No topic is set');
		}
        if (!$this->config['subscription'] ?? null) {
            throw new \Exception('No subscription is set');
		}

		$messages = $this
			->topic($this->config['topic'])
			->subscription($this->config['subscription'])
			->pull([
				'maxMessages' => $maxMessages,
				'returnImmediately' => $returnImmediately,
			]);

		return $messages;
    }

	public function getSubscription() {
		return $this->config['topic'] ?? null;
    }

	public function getTopic() {
		return $this->config['topic'] ?? null;
    }
    
    public function validateSubscription($subscriptionName) {
        try {
			if (!$this->client->topic($this->config['topic'])->subscription($subscriptionName)->exists()) {
				throw new \Exception('The subscription does no exist');
			}
		} catch (\Exception $e) {
			throw new \Exception('The subscription ' . $subscriptionName . ' (on the topic ' ($this->config['topic'] ?? 'NULL') . ') is inaccessible: ' . $e->getMessage());
		}
    }
    
    public function validateTopic($topicName) {
        try {
			if (!$this->client->topic($topicName)->exists()) {
				throw new \Exception('The topic does no exist');
			}
		} catch (\Exception $e) {
			throw new \Exception('The topic ' . $topicName . ' is inaccessible: ' . $e->getMessage());
		}
    }

	public function setTopic($topicName) {
		$this->validateTopic($topicName);
		$this->config['topic'] = $topicName;
		return $this;
	}

	public function setSubscription($subscriptionName) {
		$this->validateSubscription($subscriptionName);
		$this->config['subscription'] = $subscriptionName;
		return $this;
	}

}
