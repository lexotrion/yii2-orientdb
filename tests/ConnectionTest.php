<?php

namespace yiiunit\extensions\orientdb;

use phantomd\orientdb\Collection;
use phantomd\orientdb\file\Collection as FileCollection;
use phantomd\orientdb\Connection;
use phantomd\orientdb\Database;

/**
 * @group orientdb
 */
class ConnectionTest extends TestCase
{

    public function testConstruct()
    {
        $connection = $this->getConnection(false);
        $params     = $this->orientDbConfig;

        $connection->open();

        $this->assertEquals($params['connection']['database'], $connection->defaultDatabaseName);
        $this->assertEquals($params['options'], $connection->options);
    }

    public function testOpenClose()
    {
        $connection = $this->getConnection(false, false);

        $this->assertFalse($connection->isActive);
        $this->assertEquals(null, $connection->orientClient);

        $connection->open();
        $this->assertTrue($connection->isActive);
        $this->assertTrue(is_object($connection->orientClient));

        $connection->close();
        $this->assertFalse($connection->isActive);
        $this->assertEquals(null, $connection->orientClient);
    }

    public function testGetDatabase()
    {
        $connection = $this->getConnection();

        $database = $connection->getDatabase($connection->defaultDatabaseName);

        $this->assertTrue($database instanceof Database);
        $this->assertTrue($database->orientDb instanceof \PhpOrient\PhpOrient);

        $database2 = $connection->getDatabase($connection->defaultDatabaseName);
        $this->assertTrue($database === $database2);

        $databaseRefreshed = $connection->getDatabase($connection->defaultDatabaseName, true);
        $this->assertFalse($database === $databaseRefreshed);
    }

}
