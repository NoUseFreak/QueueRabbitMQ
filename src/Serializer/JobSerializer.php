<?php

/**
 * This file is part of the Queue package.
 *
 * (c) Dries De Peuter <dries@nousefreak.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Queue\Serializer;

use PhpAmqpLib\Message\AMQPMessage;
use Queue\Job\Job;
use Queue\Job\JobInterface;

class JobSerializer
{
    public function serialize(JobInterface $job)
    {
        return new AMQPMessage(json_encode([
            'name' => $job->getName(),
            'data' => $job->getData(),
        ]), [
            'delivery_mode' => 2, // persisted storage
        ]);
    }

    public function unserialize(AMQPMessage $AMQPMessage)
    {
        $data = json_decode($AMQPMessage->body);
        $data->data = (array) $data->data;
        if (isset($AMQPMessage->delivery_info['delivery_tag'])) {
            $data->data['_delivery_tag'] = $AMQPMessage->delivery_info['delivery_tag'];
        }

        return new Job($data->name, $data->data);
    }
}
