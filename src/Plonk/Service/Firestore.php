<?php

/*

@ref https://iluoy.com/articles/233

```
$ pecl install grpc
```

> Build process completed successfully
> Installing '/usr/local/Cellar/php/7.2.7/pecl/20170718/grpc.so'
> install ok: channel://pecl.php.net/grpc-1.15.0
> Extension grpc enabled in php.ini

```
$ pecl list
```

> Installed packages, channel pecl.php.net:
> =========================================
> Package Version State
> grpc    1.15.0  stable

```
$ php -m | grep grpc
```

> grpc

```
$ cat /usr/local/etc/php/7.2/php.ini | grep grpc
```

> extension="grpc.so"

```
$ sudo brew services restart php
```

> Password:
> Stopping `php`... (might take a while)
> ==> Successfully stopped `php` (label: homebrew.mxcl.php)
> ==> Successfully started `php` (label: homebrew.mxcl.php)
*/

namespace Plonk\Service;

use Google\Cloud\Firestore\FirestoreClient;

class Firestore {

	private $db;

	public function __construct($config) {
		$this->client = new FirestoreClient($config);
	}

	public function __call($name, $args) {
		return call_user_func_array([$this->client, $name], $args);
	}

}
