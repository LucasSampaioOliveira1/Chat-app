<?php
namespace App\Services;

class RedisService
{
    private $redis;
    
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/redis.php';
        
        $this->redis = new \Redis();
        $this->redis->connect($config['host'], $config['port']);
    }
    
    public function set($key, $value, $ttl = null)
    {
        if ($ttl) {
            return $this->redis->setex($key, $ttl, $value);
        }
        return $this->redis->set($key, $value);
    }
    
    public function get($key)
    {
        return $this->redis->get($key);
    }
    
    public function delete($key)
    {
        return $this->redis->del($key);
    }
    
    public function exists($key)
    {
        return $this->redis->exists($key);
    }
}
