<?php

namespace Plonk\Runtime\PubSub\Pull;

use Concis\Provider\GCPCloud\Pubsub\Datatype\PubsubMessage;

abstract class Handler extends \Plonk\Runtime\PubSub\Handler
{
	protected $app;
	protected $topicName;
	protected $subscriptionName;
	protected $message;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct(\Pimple $app, string $topicName = null, string $subscriptionName = null, PubsubMessage $message = null)
	{
		$this->app = $app;
		$this->topicName = $topicName;
		$this->subscriptionName = $subscriptionName;
		$this->message = $message;
	}

	/**
	 * Should this handler loop pull messages?
	 */
	public function shouldLoopAndPull()
	{
		return true;
	}

	/**
	 * The config to use when pulling from GCS PubSub
	 */
	public function getPullConfig()
	{
		return [
			'maxMessages' => 1,
			'returnImmediately' => false,
			'autoAcknowledge' => false,
		];
	}

	public function getTopicName()
	{
		return $this->topicName;
	}

	public function getSubscriptionName()
	{
		return $this->subscriptionName;
	}

	public function getMessage()
	{
		return $this->message;
	}
}
