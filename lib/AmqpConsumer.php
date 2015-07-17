<?php namespace andriell\amqp\lib;

/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 13.07.2015
 * Time: 15:49
 */

use yii\helpers\Json;

class AmqpConsumer
{
    /** @var \AMQPChannel */
    protected $chanel = null;
    /** @var  \AMQPQueue */
    protected $srvQueue = null;

    /** @var  Amqp */
    public $amqp;
    public $qosSize = 0;
    public $qosCount = 1;
    public $queueName = null;

    function __construct($amqp)
    {
        $this->amqp = $amqp;
    }

    /**
     * @return Amqp
     */
    public function getAmqp()
    {
        return $this->amqp;
    }

    /**
     * @return \AMQPChannel
     */
    public function getChanel()
    {
        if ($this->chanel === null) {
            $connection = $this->amqp->getConnection();
            $this->chanel = new \AMQPChannel($connection);
        }
        return $this->chanel;
    }

    /**
     * @return \AMQPQueue
     */
    public function getSrvQueue()
    {
        if ($this->srvQueue === null) {
            $chanel = $this->getChanel();
            /* create a queue object */
            $this->srvQueue = new \AMQPQueue($chanel);
            $this->srvQueue->setName($this->queueName);
            $this->srvQueue->setFlags(AMQP_DURABLE);
            $this->srvQueue->declareQueue();
        }
        return $this->srvQueue;
    }

    /**
     * @param \AMQPEnvelope $envelope
     * @param \AMQPQueue $queue
     * @param string $m
     */
    public function publish($envelope, $queue, $m)
    {
        if ($m instanceof AmqpResp) {
            $m = Json::encode($m->getData());
        } elseif (is_object($m) || is_array($m)) {
            $m = Json::encode($m);
        }
        $chanel = $this->getChanel();
        // Точка доступа
        // Точка обмена
        $exchange = new \AMQPExchange($chanel);
        $exchange->setFlags(AMQP_AUTODELETE | AMQP_DURABLE);
        $attributes = array(
            'correlation_id' => $envelope->getCorrelationId(),
        );
        $routingKey = $envelope->getReplyTo();
        if ($exchange->publish($m, $routingKey, AMQP_NOPARAM, $attributes)) {
            $queue->ack($envelope->getDeliveryTag());
        }
    }

    /**
     * @param \AMQPEnvelope $envelope
     * @param \AMQPQueue $queue
     * @param \Exception $e
     * @param AmqpResp $resp
     */
    public function publishError($envelope, $queue, $e, AmqpResp $resp)
    {
        $resp->addException($e);
        $this->publish($envelope, $queue, $resp);
    }

    /**
     * @param callable $processMessage
     */
    public function consume($processMessage)
    {
        $this->getSrvQueue()->consume($processMessage);
    }
}