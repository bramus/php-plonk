<?php

namespace Plonk\Util;

class Config {

	public static function envify($config, $activeEnv, $allowedEnvs) {
		foreach ($config as $key => $value) {
			if (in_array($key, $allowedEnvs)) {
				if ($key === $activeEnv) {
					$config += $value;
				}
				unset($config[$key]);
			} elseif (is_array($value)) {
				$config[$key] = self::envify($value, $activeEnv, $allowedEnvs);
			}
		}
		return $config;
	}

}