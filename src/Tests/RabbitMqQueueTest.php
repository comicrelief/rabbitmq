<?php

/**
 * @file
 * Contains RabbitMqQueueTest.
 */

namespace Drupal\rabbitmq\Tests;

use Drupal\rabbitmq\Queue\Queue;
use Drupal\rabbitmq\Queue\QueueFactory;

/**
 * Class RabbitMqQueueTest.
 *
 * @group RabbitMQ
 */
class RabbitMqQueueTest extends RabbitMqTestBase {

  /**
   * The default queue, handled by Beanstalkd.
   *
   * @var \Drupal\rabbitmq\Queue\Queue
   */
  protected $queue;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Mock the queue factory so we can pass in our mock connection
    $queue_factory = $this->getMockBuilder('\Drupal\rabbitmq\Queue\QueueFactory')
        ->setConstructorArgs(array(
            $this->connectionFactory,
            $this->container->get('module_handler'),
            $this->container->get('logger.channel.rabbitmq')
        ))
        ->setMethods(null) // Make all methods mocks so they still run
        ->getMock();

    $this->queueFactory = $queue_factory;
    $this->queue = $this->queueFactory->get(QueueFactory::DEFAULT_QUEUE_NAME);
    $this->assertTrue($this->queue instanceof \Drupal\rabbitmq\Queue\Queue, 'Queue API settings point to RabbitMQ');
  }

  /**
   * Test queue registration.
   */
  public function testQueueCycle() {
    $channel = $this->initChannel();

    $data = 'foo';
    $this->assertTrue($this->queue->createItem($data), 'Queue item created');

    $this->queue->deleteQueue();

    // We cannot get the number of items in the queue since we mocked the AMQPChannel
    // and have no way to retrieve the items sent to the queue
    // $actual = $this->queue->numberOfItems();
    // $expected = 0;
    // $this->assertEquals($expected, $actual, 'Queue no longer contains anything after deletion');
  }

  /**
   * Test the queue item lifecycle.
   */
  public function ZtestItemCycle() {
    list($server, $name, $count) = $this->initChannel();

    $data = 'foo';
    $this->queue->createItem($data);

    $actual = $this->queue->numberOfItems();
    $expected = $count + 1;
    $this->assertEquals($expected, $actual, 'Creating an item increases the item count.');

    $item = $this->queue->claimItem();
    $this->assertTrue(is_object($item), 'Claiming returns an item');
    $this->assertTrue($item instanceof BeanstalkdQueueItem, 'Claiming returns a correctly typed item');

    $expected = $data;
    $actual = $item->data;
    $this->assertEquals($expected, $actual, 'Item content matches submission.');

    $actual = $this->queue->numberOfItems();
    $expected = $count;
    $this->assertEquals($expected, $actual, 'Claiming an item reduces the item count.');

    $this->queue->releaseItem($item);
    $actual = $this->queue->numberOfItems();
    $expected = $count + 1;
    $this->assertEquals($expected, $actual, 'Releasing an item increases the item count.');

    $this->queue->deleteItem($item);
    $actual = $this->queue->numberOfItems();
    $expected = $count;
    $this->assertEquals($expected, $actual, 'Deleting an item reduces the item count.');

    $this->cleanUp($server, $name);
  }

}
