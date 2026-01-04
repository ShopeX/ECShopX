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

namespace EspierBundle\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class KafkaConsumerCommand extends Command
{
    /* @var string $signature */
    protected $signature = 'kafka:consume 
            {--topics= : The topics to listen for messages (topic1,topic2,...,topicN)} 
            {--groupId= : The consumer group id} 
            {--batchSize=1 : The size of a batch to handle}';

    /* @var string $description */
    protected $description = 'A Kafka Consumer';

    private KafkaConsumer $consumer;
    private Collection $batch;

    private $batchStartTime;
    private $batchMaxTime = 5;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        if (empty($this->option('topics'))) {
            $this->error('The [--topics option is required.');
            exit;
        }

        if (empty($this->option('groupId'))) {
            $this->error('The [--groupId option is required.');
            exit;
        }

        $this->consume();
    }


    public function consume(): void
    {
        $config = config('kafka');
        $conf = new Conf();
        $conf->set('metadata.broker.list', $config['brokers']);
        $conf->set('auto.offset.reset', ($config['offset_reset'] ?: 'latest'));
        $conf->set('enable.auto.commit', $config['auto_commit'] === true ? 'true' : 'false');
        $conf->set('group.id', $this->option('groupId'));
        $conf->set('sasl.mechanisms', $config['sasl']['mechanisms']);
        $conf->set('sasl.username', $config['sasl']['username']);
        $conf->set('sasl.password', $config['sasl']['password']);
        $conf->set('security.protocol', $config['securityProtocol']);
        if ($config['securityProtocol'] === 'SASL_SSL') {
            $conf->set('ssl.ca.location', storage_path('app/kafka/ca-cert.pem'));
            $conf->set('ssl.endpoint.identification.algorithm', 'none');
        }

        $this->consumer = new KafkaConsumer($conf);
        $this->consumer->subscribe(explode(',', $this->option('topics')));

        if ($this->option('batchSize') > 1) {
            $this->batchStartTime = time();
        }

        do {
            $message = $this->consumer->consume($config['consumer_timeout_ms'] ?: 2000);
            $this->handleMessage($message);
        } while (true);
    }

    private function handleMessage(Message $message): void
    {
        if (RD_KAFKA_RESP_ERR_NO_ERROR === $message->err) {
            if ($this->option('batchSize') > 1) {
                $this->batch->add($message);
                if (time() - $this->batchStartTime > $this->batchMaxTime || $this->batch->count() >= $this->option('batchSize')) {
                    $this->handleBatch();
                }
            } else {
                $this->executeMessage($message);
            }
            return;
        }

        //处理抛错之前未处理的消息
        if ($this->option('batchSize') > 1) {
            $this->handleBatch();
        }        

        if (!in_array($message->err, [
            RD_KAFKA_RESP_ERR__PARTITION_EOF, //No more messages
            RD_KAFKA_RESP_ERR__TIMED_OUT //Timeout
        ], true)) {
            throw new \Exception($message->errstr(), $message->err);
            sleep(config('kafka.sleep_on_error'));
        }
    }

    private function handleBatch(): void
    {
        if ($this->batch->count() === 0) {
            $this->batchStartTime = time();
            return;
        }

        $messages = [];
        foreach ($this->batch as $message) {
            if (!isset($messages[$message->topic_name])) {
                $messages[$message->topic_name] = [];
            }
            $messages[$message->topic_name][] = json_decode($message->payload, true);
        }

        $handlers = config('kafka.consumers');
        foreach ($messages as $topic => $topicMessages) {
            if (!isset($handlers[$topic])) {
                continue;
            }

            try {
                list($class, $method) = explode('@', $handlers[$topic]);
                $method = $method ?? 'handle';
                app($class)->$method($topicMessages);
            } catch (\Exception $e) {
                app('log')->debug(__CLASS__.':'.__FUNCTION__.':'.__LINE__.':msg->'.$e->getMessage().':data->'.json_encode($topicMessages));
            }
        }

        $this->batch->each(function (Message $message) {
            $this->commit($message);
        });

        $this->batchStartTime = time();
    }

    private function executeMessage(Message $message): void
    {
        $topic = $message->topic_name;
        $handlers = config('kafka.consumers');
        if (!isset($handlers[$topic])) {
            return;
        }

        try {
            list($class, $method) = explode('@', $handlers[$topic]);
            $method = $method ?? 'handle';
            app($class)->$method(json_decode($message->payload, true));
        } catch (\Exception $e) {
            app('log')->debug(__CLASS__.':'.__FUNCTION__.':'.__LINE__.':msg->'.$e->getMessage().':data->'.$message->payload);
        }

        $this->commit($message);
    }

    private function commit(Message $message): void
    {
        //开启自动提交消息处理完成后立即提交，非自动提交就一直不提交
        if (!config('kafka.auto_commit')) {
            return;
        }

        try {
            $this->consumer->commit($message);
        } catch (\Exception $e) {
            if ($e->getCode() !== RD_KAFKA_RESP_ERR__NO_OFFSET) {
                app('log')->debug(__CLASS__.':'.__FUNCTION__.':'.__LINE__.':msg->'.$e->getMessage());
                throw $e;
            }
        }
    }
}
