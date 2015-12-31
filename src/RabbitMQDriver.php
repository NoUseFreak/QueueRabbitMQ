<?php

/**
 * This file is part of the Queue package.
 *
 * (c) Dries De Peuter <dries@nousefreak.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Queue\Driver\DriverInterface;
use Queue\Job\JobInterface;
use Queue\Serializer\JobSerializer;

class RabbitMQDriver implements DriverInterface
{
    /**
     * @var AMQPStreamConnection
     */
    private $amqpStreamConnection;

    /**
     * @var JobSerializer
     */
    private $serializer;

    /**
     * BeanstalkDriver constructor.
     *
     * @param AMQPStreamConnection $amqpStreamConnection
     */
    public function __construct(AMQPStreamConnection $amqpStreamConnection)
    {
        $this->amqpStreamConnection = $amqpStreamConnection;
        $this->serializer = new JobSerializer();
    }

    public function addJob($queueName, JobInterface $job)
    {
        $this->amqpStreamConnection
            ->channel()
            ->queue_declare($queueName);

        $this->amqpStreamConnection
            ->channel()
            ->basic_publish($this->serializer->serialize($job), '', $queueName);
    }

    public function resolveJob($queueName)
    {
        $this->amqpStreamConnection
            ->channel()
            ->queue_declare($queueName);

        $job = $this->amqpStreamConnection
            ->channel()
            ->basic_get($queueName, true);

        if (!$job) {
            return;
        }

        return $this->serializer->unserialize($job);
    }

    public function removeJob($queueName, JobInterface $job)
    {
        $this->amqpStreamConnection
            ->channel()
            ->basic_ack($job->getData()['_delivery_tag']);
    }

    public function buryJob($queueName, JobInterface $job)
    {
        $this->amqpStreamConnection
            ->channel()
            ->basic_nack($job->getData()['_delivery_tag']);
    }
}
