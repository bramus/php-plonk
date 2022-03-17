<?php

namespace Plonk\Service;

class GCSBucket
{
	private $config;
	private $client;

	public function __construct($config)
	{
		$this->config = $config;
		$this->client = new \Google\Cloud\Storage\StorageClient($this->config);
	}

	public function __call($name, $args)
	{
		return call_user_func_array([$this->client->bucket($this->config['bucket']), $name], $args);
	}

	public function getBucketName()
	{
		return $this->config['bucket'];
	}

	public function setBucket($bucketName)
	{
		try {
			if (!$this->client->bucket($bucketName)->exists()) {
				throw new \Exception('The bucket does no exist');
			}
		} catch (\Exception $e) {
			throw new \Exception('The bucket ' . $bucketName . ' is inaccessible: ' . $e->getMessage());
		}

		$this->config['bucket'] = $bucketName;

		return $this;
	}
}
