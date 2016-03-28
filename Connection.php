<?php

namespace phantomd\orientdb;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use PhpOrient\PhpOrient;

class Connection extends Component
{

    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * The server host.
     *
     * @var string
     */
    public $hostname;

    /**
     * The port for the server.
     *
     * @var string
     */
    public $port;

    /**
     * @var array database params
     * for example:
     *
     * ~~~
     * [
     *     'databaseType'      => 'graph',
     *     'serializationType' => 'ORecordDocument2csv',
     * ]
     * ~~~
     */
    public $options = [];

    /**
     * @var array connection options.
     * for example:
     *
     * ~~~
     * [
     *     'database' => 'GratefulDeadConcerts',
     *     'username' => 'reader',
     *     'password' => 'reader',
     * ]
     * ~~~
     */
    public $connection = [];

    /**
     * @var string name of the Orient database to use by default.
     * If this field left blank, connection instance will attempt to determine it from
     * [[options]] and [[dsn]] automatically, if needed.
     */
    public $defaultDatabaseName;

    /**
     * @var \PhpOrient\PhpOrient Orient client instance.
     */
    public $orientClient;

    /**
     * @var Database[] list of Orient databases
     */
    private $_databases = [];

    public function init()
    {
        $this->fetchDefaultDatabaseName();
    }

    /**
     * Returns the Orient collection with the given name.
     * @param string|null $name collection name, if null default one will be used.
     * @param boolean $refresh whether to reestablish the database connection even if it is found in the cache.
     * @return Database database instance.
     */
    public function getDatabase($name = null, $refresh = false)
    {
        if ($name === null) {
            $name = $this->fetchDefaultDatabaseName();
        }
        if ($refresh || !array_key_exists($name, $this->_databases)) {
            $this->_databases[$name] = $this->selectDatabase($name);
        }

        return $this->_databases[$name];
    }

    /**
     * Returns [[defaultDatabaseName]] value, if it is not set,
     * attempts to determine it from [[dsn]] value.
     * @return string default database name
     * @throws \yii\base\InvalidConfigException if unable to determine default database name.
     */
    protected function fetchDefaultDatabaseName()
    {
        if ($this->defaultDatabaseName === null) {
            if (isset($this->connection['database'])) {
                $this->defaultDatabaseName = $this->connection['database'];
            } else {
                throw new InvalidConfigException("Unable to determine default database name connection parameters.");
            }
        }

        return $this->defaultDatabaseName;
    }

    /**
     * Selects the database with given name.
     * @param string $name database name.
     * @return Database database instance.
     */
    protected function selectDatabase($name)
    {
        $this->open();

        $database = clone $this->orientClient;
        $database->dbOpen(
            $name, // Database name
            $this->connection['username'], // User name
            $this->connection['password'], // User password
            $this->options // Database parameters
        );
        return Yii::createObject([
                'class'    => 'phantomd\orientdb\Database',
                'database' => $name,
                'orientDb' => $database,
        ]);
    }

    /**
     * Returns a value indicating whether the Orient connection is established.
     * @return boolean whether the Orient connection is established
     */
    public function getIsActive()
    {
        return is_object($this->orientClient) && $this->orientClient->getTransport()->getSocket()->connected;
    }

    /**
     * Establishes a Orient connection.
     * It does nothing if a Orient connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->orientClient === null) {
            $this->hostname = $this->hostname ? : '127.0.0.1';
            $this->port     = $this->port ? : 2424;

            $this->connection['username'] = $this->connection['username'] ? : '';
            $this->connection['password'] = $this->connection['password'] ? : '';

            $token = 'Opening OrientDB connection: ' . $this->hostname . ':' . $this->port;
            try {
                Yii::trace($token, __METHOD__);
                Yii::beginProfile($token, __METHOD__);

                $this->orientClient = new PhpOrient($this->hostname, $this->port);

                $this->initConnection();
                Yii::endProfile($token, __METHOD__);
            } catch (\Exception $e) {
                Yii::endProfile($token, __METHOD__);
                throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
            }
        }
    }

    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->orientClient !== null) {
            Yii::trace('Closing OrientDB connection: ' . $this->hostname . ':' . $this->port, __METHOD__);
            if ($this->orientClient->getTransport()->databaseOpened) {
                $this->orientClient->dbClose();
            }
            $this->orientClient = null;
            $this->_databases   = [];
        }
    }

    /**
     * Initializes the DB connection.
     * This method is invoked right after the DB connection is established.
     * The default implementation triggers an [[EVENT_AFTER_OPEN]] event.
     */
    protected function initConnection()
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }

}
