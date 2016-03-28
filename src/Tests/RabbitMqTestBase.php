<?php

/**
 * @file
 * Contains RabbitMqTestBase.
 */

namespace Drupal\rabbitmq\Tests;

use Drupal\Core\Site\Settings;
use Drupal\rabbitmq\Queue\QueueFactory;
use Drupal\rabbitmq\Connection;
use Drupal\KernelTests\KernelTestBase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * Class RabbitMqTestBase is a base class for RabbitMqTest tests.
 */
abstract class RabbitMqTestBase extends KernelTestBase {

  public static $modules = ['rabbitmq'];

  /**
   * Server factory.
   *
   * @var \Drupal\rabbitmq\Connection
   */
  protected $connectionFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Override the database queue to ensure all requests to it come to the rabbitmq.
    $this->container->setAlias('queue.database', 'queue.rabbitmq');

    // Mock our connection object
    $this->connectionFactory = $this->prepareConnection();
  }

  /**
   * Initialize a server and free channel.
   *
   * @return \AMQPChannel
   *   A channel to the default queue.
   */
  protected function initChannel() {
    $connection = $this->connectionFactory->getConnection();
    $this->assertTrue($connection instanceof AMQPStreamConnection, 'Default connections is an AMQP Connection');
    $channel = $connection->channel();
    $this->assertTrue($channel instanceof AMQPChannel, 'Default connection provides channels');
    $name = QueueFactory::DEFAULT_QUEUE_NAME;
    $passive = FALSE;
    $durable = TRUE;
    $exclusive = FALSE;
    $auto_delete = FALSE;

    // There is no point in declaring queues since we'd do this on a mock object
    // list($actual_name,,) = $channel->queue_declare($name, $passive, $durable, $exclusive, $auto_delete);
    // $this->assertEquals($name, $actual_name, 'Queue declaration succeeded');

    return $channel;
  }

  /**
   * Mock the connection
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  protected function prepareConnection()
  {
    $amqpConnection = $this->prepareAMQPConnection();
    $amqpChannel = $this->prepareAMQPChannel();

    $amqpConnection->expects($this->any())
        ->method('channel')
        ->will($this->returnValue($amqpChannel));

    $connection = $this->getMockBuilder('\Drupal\rabbitmq\Connection')
        ->disableOriginalConstructor()
        ->getMock();

    $connection->expects($this->any())
        ->method('getConnection')
        ->will($this->returnValue($amqpConnection));

    return $connection;
  }

  /**
   * Mock AMQPConnection
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  protected function prepareAMQPConnection() {
    return $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
        ->disableOriginalConstructor()
        ->getMock();
  }

  /**
   * Mock AMQPChannel
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  protected function prepareAMQPChannel() {
    return $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
        ->disableOriginalConstructor()
        ->getMock();
  }

}
