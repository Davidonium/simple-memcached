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
        $memcached->addServer(getenv('MEMCACHED_HOST'), getenv('MEMCACHED_PORT'));
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

    public function dataWithInvalidKeysProvider()
    {
        $invalidLongKey = str_repeat("abcdefghij", 26);
        return [
            [[
                'lorem
                ' => 'ipsum',
                 'dolor ' => 'sit',
                 'amet' => 'consectetur'
            ]],
            [[
                $invalidLongKey => 'ipsum',
                'dolor' => 'sit',
                'am et' => 'consectetur'
            ]],
            [[
                $invalidLongKey => 'ipsum',
                'dolo r' => 'sit',
                'am
                et' => 'consectetur'
            ]],
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

    /**
     * @param $invalidKey
     * @dataProvider invalidKeyProvider
     */
    public function testInvalidKeyGetSingle($invalidKey)
    {
        $this->expectException(InvalidKeyException::class);

        $res = $this->cache->get($invalidKey, 'ipsum');
        $this->assertFalse($res, sprintf("The key %s should fail to be used", $invalidKey));

    }

    public function testSuccessHas()
    {
        $this->cache->set('lorem', 'ipsum');
        $this->assertTrue($this->cache->has('lorem'));
        $this->assertFalse($this->cache->has('notfound'));
    }

   /**
    * @param $invalidKey
    * @dataProvider invalidKeyProvider
    */
    public function testInvalidKeyHas($invalidKey)
    {
        $this->expectException(InvalidKeyException::class);

        $res = $this->cache->has($invalidKey);
    }

    public function testSuccessDeleteSingle()
    {
        $this->cache->set('lorem', 'ipsum');
        $res = $this->cache->delete('lorem');

        $this->assertTrue($res);

        $this->assertNull($this->cache->get('lorem'));
    }

    /**
     * @param $invalidKey
     * @dataProvider invalidKeyProvider
     */
    public function testInvalidKeyDeleteSingle($invalidKey)
    {
        $this->expectException(InvalidKeyException::class);

        $this->cache->delete($invalidKey);
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

    /**
     * @param $invalidData
     * @dataProvider dataWithInvalidKeysProvider
     */
    public function testInvalidKeySetMultiple($invalidData)
    {
        $this->expectException(InvalidKeyException::class);
        $this->cache->setMultiple($invalidData);
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

    /**
     * @param $invalidData
     * @dataProvider dataWithInvalidKeysProvider
     */
    public function testInvalidKeyGetMultiple($invalidData)
    {
        $this->expectException(InvalidKeyException::class);
        $this->cache->getMultiple(array_keys($invalidData));
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

    /**
     * @param $invalidData
     * @dataProvider dataWithInvalidKeysProvider
     */
    public function testInvalidKeyDeleteMultiple($invalidData)
    {
        $this->expectException(InvalidKeyException::class);
        $this->cache->deleteMultiple(array_keys($invalidData));
    }
}