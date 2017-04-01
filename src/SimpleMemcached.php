<?php namespace SimpleMemcached;


use Psr\SimpleCache\CacheInterface;
use SimpleMemcached\Exception\InvalidKeyException;
use Memcached;
use Traversable;
use DateTimeImmutable;
use DateInterval;

class SimpleMemcached implements CacheInterface
{

    /**
     * @var Memcached
     */
    private $memcached;

    /**
     * SimpleMemcached constructor.
     * @param Memcached $cache
     */
    public function __construct(Memcached $cache)
    {
        $this->memcached = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $item = $this->memcached->get($key);

        if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            $item = $default;
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $ttl = $this->formatTtl($ttl);
        $result = $this->memcached->set($key, $value, $ttl);
        $this->checkKey($key);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->memcached->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->memcached->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $keys = $this->checkAndFormatMultiKey($keys);

        $items = $this->memcached->getMulti($keys, $cas, Memcached::GET_PRESERVE_ORDER);

        $items = array_map(function($item) use ($default) {
            return $item === null ? $default : $item;
        }, $items);

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        $ttl = $this->formatTtl($ttl);
        return $this->memcached->setMulti($values, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        $keys = $this->checkAndFormatMultiKey($keys);
        $result = $this->memcached->deleteMulti($keys);

        // Memcached returns an array of key => success, must format it to match psr-16 interface
        if (is_array($result)) {
            foreach ($result as $success) {
                if (!$success) {
                    return false;
                }
            }

            return true;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        $this->memcached->get($key);
        $this->checkKey($key);
        return $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND;
    }

    /**
     * Formats ttl to be supported by Memcached, since psr-16 supports passing a DateInterval, int, or null
     * and Memcached only supports seconds
     * @param $ttl
     * @return int
     */
    private function formatTtl($ttl)
    {
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();
            $ttl = $now->add($ttl)->getTimestamp() - $now->getTimestamp();
        } elseif ($ttl === null) {
            $ttl = 0;
        }

        return $ttl;
    }

    /**
     * @param $keys
     * @return array
     * @throws InvalidKeyException
     */
    private function checkAndFormatMultiKey($keys)
    {

        if (!is_array($keys) && !($keys instanceof Traversable)) {
            throw new InvalidKeyException(sprintf("Invalid array of keys %s provided, expected array or \\Traversable", var_export($keys)));
        }


        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys, false);
        }

        foreach ($keys as $key) {
            $this->checkKeyBeforeSend($key);
        }

        return $keys;
    }

    /**
     * Checks at php level, the key must be less than 250 bytes long and must not have special characters
     * @param $key
     * @throws InvalidKeyException
     */
    private function checkKeyBeforeSend($key)
    {
        if (!is_string($key) || preg_match("#\s|\t|\n|\r|\x0B|\f#", $key)) {
            throw new InvalidKeyException(sprintf('Invalid key %s provided, expected a string without whitespace nor control characters', $key));
        } elseif (strlen($key) > 250) {
            throw new InvalidKeyException(sprintf("Invalid key %s provided, expected a string with less than 250 bytes of length"));
        }
    }

    /**
     * Checks the result of memcached for a bad key provided, this must be used after a memcached operation
     * @param string $key
     * @throws InvalidKeyException
     */
    private function checkKey($key)
    {
        if ($this->memcached->getResultCode() === Memcached::RES_BAD_KEY_PROVIDED) {
            throw new InvalidKeyException(sprintf('Invalid key %s provided, Message: %s', $key, $this->memcached->getResultMessage()));
        }
    }
}