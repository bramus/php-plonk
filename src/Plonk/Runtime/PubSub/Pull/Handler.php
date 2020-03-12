<?php

namespace Plonk\Runtime\PubSub\Pull;

abstract class Handler extends \Plonk\Runtime\PubSub\Handler
{
    protected $app;
    protected $topicName;
    protected $subscriptionName;

    /**
     * Class constructor.
     *
     * @return void
     */
    public function __construct(\Pimple $app, string $topicName, string $subscriptionName)
    {
        $this->app = $app;
        $this->topicName = $topicName;
        $this->subscriptionName = $subscriptionName;

        $this->app['pubsub']->setTopic($topicName)->setSubscription($subscriptionName);
    }

    public function getTopicName() {
        return $this->topicName;
    }

    public function getSubscriptionName() {
        return $this->subscriptionName;
    }
}