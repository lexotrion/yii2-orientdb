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

        $this->assertEquals($params['connection']['database'], $connection->name);
        $this->assertEquals($params['options'], $connection->options);
    }

    public function testOpenClose()
    {
        $connection = $this->getConnection(false, false);

        $this->assertFalse($connection->isActive);
        $this->assertEquals(null, $connection->client);

        $connection->open();
        $this->assertTrue($connection->isActive);
        $this->assertTrue(is_object($connection->client));

        $connection->close();
        $this->assertFalse($connection->isActive);
        $this->assertEquals(null, $connection->client);
    }

    public function testGetDatabase()
    {
        $connection = $this->getConnection();

        $database = $connection->getCluster();
        $this->assertTrue($database instanceof \PhpOrient\Protocols\Common\ClustersMap);

        $databaseRefreshed = $connection->getDb(true)->getCluster();
        $this->assertFalse($database === $databaseRefreshed);
    }

}
