php Cross Connector / xconn
============
Simply handles multiple connections accross multiple user adapters.

Features
----------
- Connects only when necessary and only once during runtime
- No dependencies - write any adapter for any extension or just proxy the interface itself
- Different instances for one adapter (e.g. several different memcached instances)
- Supports closure approach
- Easy to configure

Required
----------
- PHP 5.4+

Usage 
----------
```php
use xconn\Connector;
```

#### Option 1. Closure
```php
/* Touch connection and pass closure. You may pass data scope inside through 'use' */
$foo = Connector::touch('memcached', function($m) {
    return $m->get('bar');
}));

/* More logic inside */
$users = Connector::touch('production_database', function ($db) {
    $list = [];
    $result = $db->query("SELECT user_name FROM users");
    while ($data = $db->fetch($result)) {
        $list[] = $data['user_name'];
    }
    return $list;
});
```

#### Option 2. Get adapter instance
```php
/* Simply take an instance */
$db = Connector::take('local_database');

/* And use it */
$result = $db->query('SELECT foo FROM bar');
```

#### Option 3. Proxy original interface
```php
/* Proxy adapted connection */
$m = Connector::proxy('memcached');

/* Use original interface. ! Adapters' aliases will not work */
print_r($m->getVersion());
```


Adapters
----------

Each adapter should extend common Adapter class and define abstract methods. The $config will be passed from the configuration file. Use $this->connection as instance inside your adapter methods.

```php
    protected $connection = null;
    abstract protected function openConnection($config);
    abstract protected function closeConnection();
```

#### Memcached example
```php
<?php namespace xconn\adapters;

final class Memcached extends \xconn\Adapter {

    /* @return Instance|bool */
    protected function openConnection($config) {
    
        $instance = new \Memcached();
        
        if (!$instance->addServer($config['host'], $config['port'])) {
            return false;
        }

        return $instance;
    }

    /* Correctly close the connection */
    protected function closeConnection() {

        if (!$this->connection) {
            return false;
        }

        $this->connection->quit();
    }
    
    /* Put your aliases here */
    public function get($key) {
        return $this->connection->get($key, null, $this->cas_token);
    }
    
    /* .... */
    
```

Configuration example
----------
All variables will be passed as $config to Adapter\openConnection;

    [production_database]
    adapter = MySQL
    host = null
    user = root
    port = 0
    pass = pass
    name = dbname
    socket = /tmp/mysql.sock
    
    [memcached]
    adapter = memcached
    prefix = myprefix
    host = localhost
    port = 11211
