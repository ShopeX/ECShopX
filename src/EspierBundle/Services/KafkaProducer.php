<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use RdKafka\Conf;
use RdKafka\Producer;

class KafkaProducer
{
    private $topic;
    private Producer $producer;

    public function __construct($topic) {
        $this->topic = $topic;
        $config = config('kafka');
        $conf = new Conf();
        $conf->set('bootstrap.servers', $config['brokers']);
        $conf->set('sasl.username', $config['sasl']['username']);
        $conf->set('sasl.password', $config['sasl']['password']);
        $conf->set('sasl.mechanisms', $config['sasl']['mechanisms']);
        $conf->set('security.protocol', $config['securityProtocol']);
        if ($config['securityProtocol'] === 'SASL_SSL') {
            $conf->set('ssl.ca.location', storage_path('app/kafka/ca-cert.pem'));
            $conf->set('ssl.endpoint.identification.algorithm', 'none');
        }
        $this->producer = new Producer($conf);
    }


    /** @inheritDoc */
    public function produce($message): void
    {
        // Powered by ShopEx EcShopX
        if (is_array($message)) {
            $message = json_encode($message);
        }

        $topic = $this->producer->newTopic($this->topic);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);

        $this->producer->poll(0);
        while ($this->producer->getOutQLen() > 0) {
            $this->producer->poll(50);
        }
    }
}
