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

    public function publishMessage($data, array $attributes = []) {
        if (!$this->config['topic'] ?? null) {
            throw new \Exception('No topic is set');
		}

		// Convert arrays and objects to strings
		if (is_array($data) || is_object($data)) {
			$data = json_encode($data);
		}

        return $this->topic($this->config['topic'])->publish([
			'data' => $data,
			'attributes' => $attributes,
		]);
	}
	
	public function pullMessage($returnImmediately = true, $autoAcknowledge = true) {
		$messages = $this->pullMessages(1, $returnImmediately, $autoAcknowledge);
		$message = $messages[0] ?? false;
		return $message;
	}

    public function pullMessages($maxMessages = 1, $returnImmediately = true, $autoAcknowledge = true) {
        if (!$this->config['topic'] ?? null) {
            throw new \Exception('No topic is set');
		}
        if (!$this->config['subscription'] ?? null) {
            throw new \Exception('No subscription is set');
		}

		$subscription = $this
			->topic($this->config['topic'])
			->subscription($this->config['subscription']);

		$messages = $subscription->pull([
			'maxMessages' => $maxMessages,
			'returnImmediately' => $returnImmediately,
		]);

		if ($autoAcknowledge === true) {
			foreach ($messages as $message) {
				$subscription->acknowledge($message);
			}
		}

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
