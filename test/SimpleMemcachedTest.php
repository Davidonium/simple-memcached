<?php

use PHPUnit\Framework\TestCase;
use SimpleMemcached\SimpleMemcached;
use SimpleMemcached\Exception\InvalidKeyException;

class SimpleMemcachedTest extends TestCase
{
    /**
     * @var SimpleMemcached
     */
    private $cache;


    public function setUp()
    {
        $memcached = new Memcached();
        $memcached->addServer('localhost', '11211');
        $this->cache = new SimpleMemcached($memcached);
        $this->cache->clear();
    }

    public function invalidKeyProvider()
    {
        $invalidLongKey = str_repeat("abcdefghij", 26);
        return [
            ["lorem
            "],
            ['lorem '],
            ['l orem'],
            ['lo
            rem'],
            [$invalidLongKey]
        ];
    }

    public function testSuccessSetSingle()
    {
        $res = $this->cache->set('lorem', 'ipsum');
        $this->assertTrue($res);
    }

    /**
     * @param $invalidKey
     * @dataProvider invalidKeyProvider
     */
    public function testInvalidKeySetSingle($invalidKey)
    {
        $this->expectException(InvalidKeyException::class);

        $res = $this->cache->set($invalidKey, 'ipsum');
        $this->assertFalse($res, sprintf("The key %s should fail to be inserted", $invalidKey));

    }

    public function testSuccessGetSingle()
    {
        $this->cache->set('lorem', 'ipsum');

        $val = $this->cache->get('lorem');
        $this->assertEquals($val, 'ipsum');


        $val = $this->cache->get('notfound');
        $this->assertNull($val);


        $val = $this->cache->get('notfound', 'defaultvalue');
        $this->assertEquals($val, 'defaultvalue');

    }

    public function testSuccessHas()
    {
        $this->cache->set('lorem', 'ipsum');
        $this->assertTrue($this->cache->has('lorem'));
        $this->assertFalse($this->cache->has('notfound'));
    }

    public function testSuccessDeleteSingle()
    {
        $this->cache->set('lorem', 'ipsum');
        $res = $this->cache->delete('lorem');

        $this->assertTrue($res);

        $this->assertNull($this->cache->get('lorem'));
    }

    public function testSuccessClear()
    {

        $this->cache->set('lorem', 'ipsum');
        $this->cache->clear();

        $this->assertFalse($this->cache->has('lorem'));
    }

    public function testSuccessSetMultiple()
    {
        $testData = [
            'lorem' => 'ipsum',
            'dolor' => 'sit',
            'amet' => 'consectetur'
        ];


        $res = $this->cache->setMultiple($testData);

        $this->assertTrue($res);

        foreach ($testData as $key => $item) {
            $this->assertEquals($item, $this->cache->get($key));
        }

    }

    public function testSuccessGetMultiple()
    {
        $testData = [
            'lorem' => 'ipsum',
            'dolor' => 'sit',
            'amet' => 'consectetur'
        ];

        $this->cache->setMultiple($testData);

        $items = $this->cache->getMultiple(array_keys($testData));

        foreach ($items as $key => $item) {
            $this->assertEquals($item, $testData[$key]);
        }
    }

    public function testSuccessDeleteMultiple()
    {
        $testData = [
            'lorem' => 'ipsum',
            'dolor' => 'sit',
            'amet' => 'consectetur'
        ];

        $this->cache->setMultiple($testData);
        $res = $this->cache->deleteMultiple(array_keys($testData));
        $this->assertTrue($res);

        $items = $this->cache->getMultiple(array_keys($testData));

        foreach ($items as $key => $item) {
            $this->assertNull($item);
        }
    }
}