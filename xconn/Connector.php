<?php namespace xconn;

/**
 * Class Connector
 * @package xconn
 */
final class Connector
{

    /* You may change the path using relative path e.g. '../path/to/config.ini' */
    /**
     * @var string
     */
    protected static $config_path = 'connector.ini';

    /* Halt application on error */
    /**
     * @var bool
     */
    protected static $halt_on_error = false;

    /* If true - the at sign will be appended to closure */
    /**
     * @var bool
     */
    protected static $silent_mode = false;

    /**
     * @var null
     */
    protected static $instance = null;
    /**
     * @var array
     */
    protected $connections = [];
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param $reference
     * @param callable $action
     * @return bool
     * @throws \Exception
     */
    public static function touch($reference, \Closure $action) {

        $resource = self::connect($reference);

        if ($resource) {
            if (self::$silent_mode) {
                return @$action($resource);
            } else {
                return $action($resource);
            }
        } else {
            self::throwError('failed to connect: '. $reference);
        }

        return false;
    }

    /**
     * @param $reference
     * @return bool
     * @throws \Exception
     */
    public static function take($reference) {

        $resource = self::connect($reference);

        if ($resource) {
            return $resource;
        }

        return self::throwError('failed to connect: '. $reference);

    }

    /**
     * @param $reference
     * @return bool
     * @throws \Exception
     */
    public static function proxy($reference) {

        $resource = self::connect($reference);

        if ($resource) {
            return $resource->proxyConnection();
        }

        return self::throwError('failed to connect: '. $reference);
    }

    /**
     * @return null
     */
    protected static function getInstance() {
        $_ = get_called_class();
        return !is_null(self::$instance) ? self::$instance : (self::$instance = new $_);
    }

    /**
     * @param $reference
     * @return bool
     * @throws \Exception
     */
    protected static function getConfig($reference) {

        $file_path = __DIR__ .'/'. self::$config_path;

        if (!file_exists($file_path)) {
            return self::throwError ('config file not exists');
        }

        $config = parse_ini_file($file_path, true);

        if (!isset ($config[$reference])) {
            return self::throwError ('entity file not exists');
        }

        if (!isset ($config[$reference]['adapter']) || empty($config[$reference]['adapter'])) {
            return self::throwError ('adapter type not specified in ' . $reference);
        }

        return $config[$reference];
    }

    /**
     * @param $message
     * @return bool
     * @throws \Exception
     */
    protected static function throwError($message) {

        $output = __CLASS__ .' error: ' . $message;

        if (self::$halt_on_error) {
            throw new \Exception ($output);
        } else {
            print ($output."\n");
        }

        return false;
    }

    /**
     * @param $reference
     * @return bool
     * @throws \Exception
     */
    protected static function connect($reference) {


        if (is_null($reference)) {
            return self::throwError ('empty reference was passed');
        }

        $config = self::getConfig($reference);

        if (!$config) {
            return false;
        }

        $adapter = $config['adapter'];
        $instance = self::getInstance();

        if ($instance->connectionExists ($reference)) {
            return $instance->getConnection ($reference);
        }

        if (self::adapterExists ($adapter)) {

            $connection = self::loadAdapter($adapter, $config);

            if (!$connection->connected()) {
                return self::throwError('adapter loaded but connection failed');
            }

            $instance->setConnection ($reference, $connection);

            return $connection;
        }

        return self::throwError('adapter not found: '.$adapter.' ('.$reference.')');
    }

    /**
     * @param $reference
     * @return bool
     */
    protected function connectionExists($reference) {
        return (isset ($this->connections[$reference]) && is_object($this->connections[$reference]));
    }

    /**
     * @param $reference
     * @return bool
     */
    protected function getConnection($reference) {
        return (isset ($this->connections[$reference]) ? $this->connections[$reference] : false);
    }

    /**
     * @param $reference
     * @param $connection
     * @return mixed
     */
    protected function setConnection($reference, $connection) {
        return ($this->connections[$reference] = $connection);
    }

    /**
     * @param $adapter
     * @return bool
     */
    protected static function adapterExists($adapter) {
        return class_exists(__NAMESPACE__  . '\\adapters\\' . $adapter);
    }

    /**
     * @param $adapter
     * @param $config
     * @return mixed
     */
    protected static function loadAdapter($adapter, $config) {
        $reference = __NAMESPACE__  . '\\adapters\\' . $adapter;
        return new $reference ($config);
    }
}