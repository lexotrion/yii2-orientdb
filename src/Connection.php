<?php

namespace phantomd\orientdb;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use PhpOrient\PhpOrient;

/**
 * Connection represents a connection to a database.
 *
 * @property boolean $isActive Whether the DB connection is established. This property is read-only.
 * @property \PhpOrient\PhpOrient $client OrientDB client instance. This property is read-only.
 *
 * @author Anton Ermolovich <anton.ermolovich@gmail.com>
 * @since 2.0
 */
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
     * @var \PhpOrient\PhpOrient Orient client instance.
     */
    private $orientdbClient;

    /**
     * @var string name of the Orient database to use by default.
     * If this field left blank, connection instance will attempt to determine it from
     * [[connection]] automatically, if needed.
     */
    private $databaseName;

    /**
     * Get database name
     * @return string
     */
    public function getName()
    {
        return $this->__toString();
    }

    /**
     * Current database name
     * @return string
     */
    public function __toString()
    {
        $return = null;
        if (is_object($this->client)) {
            $return = $this->client->getTransport()->databaseName;
        }
        return (string)$return;
    }

    /**
     * Returns the Orient cluster with the given name.
     * @param boolean $refresh whether to reestablish the database connection even if it is found in the cache.
     * @return \PhpOrient\Protocols\Common\ClustersMap cluster instance.
     */
    public function getDb($refresh = false)
    {
        if ($refresh || (is_object($this->client) && false === $this->client->getTransport()->databaseOpened)) {
            $this->selectDatabase($this->getDatabaseName());
        }

        return $this;
    }

    /**
     * Get ClasterMap object
     * @return \PhpOrient\Protocols\Common\ClustersMap
     */
    public function getCluster()
    {
        return $this->client->getTransport()->getClusterMap();
    }

    /**
     * Returns a value indicating whether the Orient connection is established.
     * @return boolean whether the Orient connection is established
     */
    public function getIsActive()
    {
        return is_object($this->client) && $this->client->getTransport()->getSocket()->connected;
    }

    /**
     * Establishes a Orient connection.
     * It does nothing if a Orient connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->client === null) {
            $this->hostname = $this->hostname ? : '127.0.0.1';
            $this->port     = $this->port ? : 2424;

            $this->connection['username'] = $this->connection['username'] ? : '';
            $this->connection['password'] = $this->connection['password'] ? : '';

            $token = 'Opening OrientDB connection: ' . $this->hostname . ':' . $this->port;
            try {
                Yii::trace($token, __METHOD__);
                Yii::beginProfile($token, __METHOD__);

                $this->client = new PhpOrient($this->hostname, $this->port, session_id());
                $this->getDb();

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
        if ($this->client !== null) {
            Yii::trace('Closing OrientDB connection: ' . $this->hostname . ':' . $this->port, __METHOD__);
            if ($this->client->getTransport()->databaseOpened) {
                $this->client->dbClose();
            }
            $this->client = null;
        }
    }

    /**
     * Get client object
     * @return \PhpOrient\PhpOrient OrientDB client instance.
     */
    public function getClient()
    {
        return $this->orientdbClient;
    }

    /**
     * Set client object
     * @param \PhpOrient\PhpOrient|null $value OrientDB client instance.
     */
    protected function setClient($value)
    {
        $this->orientdbClient = $value;
    }

    /**
     * Returns [[defaultDatabaseName]] value, if it is not set,
     * attempts to determine it from [[connection]] value.
     * @return string default database name
     * @throws \yii\base\InvalidConfigException if unable to determine default database name.
     */
    protected function getDatabaseName()
    {
        if ($this->databaseName === null) {
            if (isset($this->connection['database'])) {
                $this->databaseName = $this->connection['database'];
            } else {
                throw new InvalidConfigException("Unable to determine database name connection parameters.");
            }
        }

        return $this->databaseName;
    }

    /**
     * Selects the database with given name.
     * @param string $name database name.
     * @return Database database instance.
     */
    protected function selectDatabase($name)
    {
        $this->open();

        try {
            $this->client->dbOpen(
                $name, // Database name
                $this->connection['username'], // User name
                $this->connection['password'], // User password
                $this->options // Database parameters
            );
        } catch (Exception $e) {
            echo $e->getMessage();
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

    /**
     * Calls the named method which is not a class method.
     *
     * @param string $name the method name
     * @param array $params method parameters
     * @return mixed the method return value
     * @throws UnknownMethodException when calling unknown method
     */
    public function __call($name, $params)
    {
        if (method_exists($this->client, $name)) {
            return call_user_func_array([$this->client, $name], $params);
        }

        return parent::__call($name, $params);
    }

}
