<?php namespace SimpleMemcached\Exception;


use Psr\SimpleCache\InvalidArgumentException;

class InvalidKeyException extends \Exception implements InvalidArgumentException
{

}