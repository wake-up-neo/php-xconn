<?php namespace xconn\adapters;

/**
 * Class memcached
 * This is an EXAMPLE of memcached adapter
 *
 * @package xconn\adapters
 */
final class Memcached extends \xconn\Adapter {

    /**
     *
     * memcached compare-and-save token
     *
     * @var
     */
    public $cas_token;

    /**
     * @param $config
     *
     * @return \Memcached instance
     */
    protected function openConnection($config) {

        $instance = new \Memcached();

        /* Configure your connection */
        $instance->setOption(\Memcached::OPT_RECV_TIMEOUT, 0);
        $instance->setOption(\Memcached::OPT_SEND_TIMEOUT, 0);
        $instance->setOption(\Memcached::OPT_TCP_NODELAY, true);
        $instance->setOption(\Memcached::OPT_BINARY_PROTOCOL, false);
        $instance->setOption(\Memcached::OPT_BUFFER_WRITES, true);
        $instance->setOption(\Memcached::OPT_NO_BLOCK, true);
        $instance->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, false);
        $instance->setOption(\Memcached::OPT_COMPRESSION, true);
        $instance->setOption(\Memcached::OPT_PREFIX_KEY, $config['prefix']);

        if (!$instance->addServer($config['host'], $config['port'])) {
            return false;
        }

        return $instance;
    }

    /**
     * @return bool
     */
    protected function closeConnection() {

        if (!$this->connection) {
            return false;
        }

        $this->connection->quit();
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key) {
        return $this->connection->get($key, null, $this->cas_token);
    }

    /**
     * @param $key
     * @param $val
     * @param int $expire_time
     * @return bool
     */
    public function set($key, $val, $expire_time = 0) {
        $this->connection->set($key, $val, $expire_time);
        return ($this->connection->getResultCode() === \Memcached::RES_SUCCESS);
    }

    /**
     * @param $token
     * @param $key
     * @param $val
     * @param int $expire_time
     * @return mixed
     */
    public function cas($token, $key, $val, $expire_time = 0) {
        return $this->connection->cas($token, $key, $val, $expire_time);
    }

    /**
     * @param $key
     * @param $val
     * @param int $expire_time
     * @return mixed
     */
    public function add($key, $val, $expire_time = 0) {
        return $this->connection->add($key, $val, $expire_time);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function delete($key) {
        return $this->connection->delete($key, 0);
    }

    /**
     * @param $key
     * @param int $offset
     * @param int $initial_value
     * @param int $expire_time
     * @return mixed
     */
    public function increment($key, $offset = 1, $initial_value = 0, $expire_time = 0) {
        return $this->connection->increment($key, $offset, $initial_value, $expire_time);
    }

    /**
     * @param $key
     * @param int $offset
     * @param int $initial_value
     * @param int $expire_time
     * @return mixed
     */
    public function decrement($key, $offset = 1, $initial_value = 0, $expire_time = 0) {
        return $this->connection->increment($key, $offset, $initial_value, $expire_time);
    }

    /**
     * @return mixed
     */
    public function resultCode() {
        return $this->connection->getResultCode();
    }

}