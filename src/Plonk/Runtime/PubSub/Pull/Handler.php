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

    public function getTopicName() {
        return $this->topicName;
    }

    public function getSubscriptionName() {
        return $this->subscriptionName;
    }

    public function getMessage() {
        return $this->message;
    }
}