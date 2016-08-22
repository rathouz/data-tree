<?php

/**
 * This file is part of the Pehape libraries (http://pehape.cz)
 * Copyright (c) 2016 Tomas Rathouz <trathouz at gmail.com>
 */

namespace Pehape\DataTree\Tests\Unit;

use Codeception\Util\Fixtures;
use Nette\Database\Context;
use Nette\DI\Container;
use Pehape\DataTree\Sources\DatabaseSource;


/**
 * Test Pehape\DataTree\Sources\DatabaseSource class.
 *
 * @author Tomas Rathouz <trathouz at gmail.com>
 */
class DatabaseSourceTest extends \Codeception\Test\Unit
{

    /** @var \UnitTester */
    protected $tester;

    /** @var Container */
    private $container;

    /** @var Context */
    private $db;

    /** @var DatabaseSource */
    private $source;


    /** Before. */
    protected function _before()
    {
        $this->container = Fixtures::get('container');
        $this->db = $this->container->getByType('Nette\Database\Context');
        $this->source = new DatabaseSource($this->db);
    }


    /** DatabaseSource must implement \Pehape\DataTree\Sources\IDataSource. */
    public function testImplementsInterface()
    {
        $this->tester->assertInstanceOf('Pehape\DataTree\Sources\IDataSource', $this->source);
    }


    /** Test default table names. */
    public function testDefaultTablesNames()
    {
        $this->tester->assertEquals(DatabaseSource::DEF_BASE_TABLE_NAME, $this->source->getBaseTableName());
        $this->tester->assertEquals(DatabaseSource::DEF_CLOSURE_TABLE_NAME, $this->source->getClosureTableName());
    }


    /** Test custom table names. */
    public function testCustomTableNames()
    {
        $baseTableName = 'data';
        $closureTableName = 'data_closure';
        $this->source->setBaseTableName($baseTableName);
        $this->source->setClosureTableName($closureTableName);
        $this->tester->assertEquals($baseTableName, $this->source->getBaseTableName());
        $this->tester->assertEquals($closureTableName, $this->source->getClosureTableName());
    }


    /** Test get tables selection. */
    public function testGetTablesSelection()
    {
        $this->tester->assertInstanceOf('Nette\Database\Table\Selection', $this->source->getBaseTable());
        $this->tester->assertInstanceOf('Nette\Database\Table\Selection', $this->source->getClosureTable());
    }


    /** Test that exception is thrown when setting unknow table. */
    public function testSetUnknownTable()
    {
        $this->tester->expectException('Pehape\DataTree\Exceptions\UnknownSourceTableException', function() {
            $this->source->setBaseTableName('unknown');
        });
        $this->tester->expectException('Pehape\DataTree\Exceptions\UnknownSourceTableException', function() {
            $this->source->setBaseTableName('unknown_closure');
        });
    }


    /** Test that method getNodes() always returns an array. */
    public function testGetNodes()
    {
        $this->tester->assertTrue(is_array($this->source->getNodes()));
        $this->db->table('data')->delete();
        $this->tester->assertTrue(is_array($this->source->getNodes()));
        $this->tester->assertEmpty($this->source->getNodes());
    }


    /** Test get existing node. */
    public function testGetExistingNode()
    {
        $this->tester->assertInstanceOf('Nette\Database\Table\IRow', $this->source->getNode(1));
    }


    /** Test get unknown node. */
    public function testGetUnknownNode()
    {
        $this->tester->expectException('Pehape\DataTree\Exceptions\DatabaseSourceException', function() {
            $this->source->getNode(-1);
        });
    }


    /** Test create node. */
    public function testCreateNode()
    {
        $insertNode = [
            'name' => 'Test group',
            'type' => 'group',
        ];
        $this->tester->assertEquals(6, count($this->source->getNodes()));
        $this->tester->assertEquals(17, count($this->source->getClosureTable()->fetchAll()));
        $this->source->createNode(0, $insertNode);
        $this->tester->assertEquals(7, count($this->source->getNodes()));
        $this->tester->assertEquals(19, count($this->source->getClosureTable()->fetchAll()));
    }


    /** Test create node with unvalid data. */
    public function testCreateNodeWithUnvalidData()
    {
        $insertNode = [
            'name' => 'Test group',
            'type' => 'group',
            'unvalid_column' => 'unvalid_column',
        ];
        $this->tester->expectException('Pehape\DataTree\Exceptions\DatabaseSourceException', function() use ($insertNode) {
            $this->source->createNode(0, $insertNode);
        });
    }


    /** Test update node. */
    public function testUpdateNode()
    {
        $updateNode = [
            'name' => 'My first group',
            'type' => 'child',
        ];
        $this->source->updateNode(1, $updateNode);
        $this->tester->assertEquals('My first group', $this->source->getNode(1)->name);
        $this->tester->assertEquals('child', $this->source->getNode(1)->type);
    }


    /** Test update node with unvalid data. */
    public function testUpdateNodeWithUnvalidData()
    {
        $updateNode = [
            'unvalid_column' => 'unvalid_data',
        ];
        $this->tester->expectException('Pehape\DataTree\Exceptions\DatabaseSourceException', function() use ($updateNode) {
            $this->source->updateNode(1, $updateNode);
        });
    }

    /** Test move node. */
    public function testMoveNode()
    {
        $this->source->moveNode(6, 1);
        $childrenOne = $this->source->getClosureTable()->where([
            'ancestor' => 1,
            'descendant' => 6,
        ])->fetchAll();
        $childrenSix = $this->source->getClosureTable()->where([
            'ancestor' => 2,
            'descendant' => 6,
        ])->fetchAll();
        $this->tester->assertEquals(1, count($childrenOne));
        $this->tester->assertEquals(0, count($childrenSix));
    }
    

    /** Test create node. */
    public function testRemoveNode()
    {
        $this->tester->assertEquals(6, count($this->source->getNodes()));
        $this->tester->assertEquals(17, count($this->source->getClosureTable()->fetchAll()));
        $this->source->removeNode(6);
        $this->tester->assertEquals(5, count($this->source->getNodes()));
        $this->tester->assertEquals(14, count($this->source->getClosureTable()->fetchAll()));
        $this->source->removeNode(4);
        $this->tester->assertEquals(3, count($this->source->getNodes()));
        $this->tester->assertEquals(9, count($this->source->getClosureTable()->fetchAll()));
    }


    /** Test remove unknown node. */
    public function testRemoveUnknownNode()
    {
        $this->source->removeNode(-1);
    }


}
