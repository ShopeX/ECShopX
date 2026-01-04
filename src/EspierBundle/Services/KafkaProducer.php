<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
