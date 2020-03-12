<?php

namespace Plonk\Runtime\PubSub\Push;

abstract class Handler extends \Plonk\Runtime\PubSub\Handler
{
    protected $app;
    protected $topicName;
    protected $message;

    /**
     * Class constructor.
     *
     * @return void
     */
    public function __construct(\Pimple $app, string $topicName, PubsubMessage $message)
    {
        $this->app = $app;
        $this->topicName = $topicName;
        $this->message = $message;
    }

    public function getTopicName() {
        return $this->topicName;
    }

    public function getMessage() {
        return $this->message;
    }
}