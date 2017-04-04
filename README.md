# SimpleMemcached



A simple implementation of psr-16 simple-cache using the memcached extension.

For now the you can look at the tests for examples.


This is my first open source project, I will gladly accept any advice you can give me :).


## TODO

* Test ttl
* ~~Test multiple key actions on failure because invalid keys~~
* Find out why keys using "\n\t\f" in double quoted php strings work and don't throw exception


### Install

```bash
composer require davidonium/simple-memcached
```
### Use

Create a test that looks like this: 

```php
$memcached = new \Memcached();
$memcached->addServer('127.0.0.1', '11211');
$cache = new \SimpleMemcached\SimpleMemcached($memcached);
$cache->set('lorem', 'ipsum');
$value = $cache->get('lorem'); // ipsum
```