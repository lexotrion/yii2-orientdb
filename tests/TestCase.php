<?php

namespace yiiunit\extensions\orientdb;

use yii\helpers\ArrayHelper;
use phantomd\orientdb\Connection;
use Yii;
use phantomd\orientdb\Exception;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{

    public static $params;

    /**
     * @var array Orient connection configuration.
     */
    protected $orientDbConfig = [];

    /**
     * @var Connection Orient connection instance.
     */
    protected $orientdb;

    protected function setUp()
    {
        parent::setUp();
        $config = self::getParam('orientdb');
        if (!empty($config)) {
            $this->orientDbConfig = $config;
        }
        //$this->mockApplication();
    }

    protected function tearDown()
    {
        if ($this->orientdb) {
            $this->orientdb->close();
        }
        $this->destroyApplication();
    }

    /**
     * Returns a test configuration param from /data/config.php
     * @param  string $name params name
     * @param  mixed $default default value to use when param is not set.
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require(__DIR__ . '/data/config.php');
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
                'id'         => 'testapp',
                'basePath'   => __DIR__,
                'vendorPath' => $this->getVendorPath(),
                ], $config));
    }

    protected function getVendorPath()
    {
        $vendor = dirname(dirname(__DIR__)) . '/vendor';
        if (!is_dir($vendor)) {
            $vendor = dirname(dirname(dirname(dirname(__DIR__))));
        }
        return $vendor;
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        \Yii::$app = null;
    }

    /**
     * @param  boolean                 $reset whether to clean up the test database
     * @param  boolean                 $open  whether to open test database
     * @return \phantomd\orientdb\Connection
     */
    public function getConnection($reset = false, $open = true)
    {
        if (!$reset && $this->orientdb) {
            return $this->orientdb;
        }
        $db = new Connection($this->orientDbConfig);
        if ($open) {
            $db->open();
        }
        $this->orientdb = $db;

        return $db;
    }

    /**
     * Drops the specified collection.
     * @param string $name collection name.
     */
    protected function dropCollection($name)
    {
        if ($this->orientdb) {
            try {
                $this->orientdb->getCollection($name)->drop();
            } catch (Exception $e) {
                // shut down exception
            }
        }
    }

    /**
     * Drops the specified file collection.
     * @param string $name file collection name.
     */
    protected function dropFileCollection($name = 'fs')
    {
        if ($this->orientdb) {
            try {
                $this->orientdb->getFileCollection($name)->drop();
            } catch (Exception $e) {
                // shut down exception
            }
        }
    }

    /**
     * Finds all records in collection.
     * @param  \phantomd\orientdb\Collection $collection
     * @param  array                   $condition
     * @param  array                   $fields
     * @return array                   rows
     */
    protected function findAll($collection, $condition = [], $fields = [])
    {
        $cursor = $collection->find($condition, $fields);
        $result = [];
        foreach ($cursor as $data) {
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Returns the Orient server version.
     * @return string Orient server version.
     */
    protected function getServerVersion()
    {
        $connection = $this->getConnection();
        $buildInfo  = $connection->getDatabase()->executeCommand(['buildinfo' => true]);

        return $buildInfo['version'];
    }

}
