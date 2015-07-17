<?php namespace andriell\amqp\lib;

/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 14.07.2015
 * Time: 9:32
 */

use yii\helpers\Json;

class AmqpProducer
{
    /* @var int */
    protected static $syncCallCorrelationId = 0;

    /** @var  Amqp */
    protected $amqp;

    /**
     * @param Amqp $amqp
     */
    function __construct($amqp)
    {
        $this->amqp = $amqp;
    }

    /**
     * Синхронный вызов
     * @param $service
     * @param $message
     * @return \AMQPEnvelope
     * @throws \Exception
     */
    public function syncCall($service, $message)
    {
        if (is_array($message) || is_object($message)) {
            $message = Json::encode($message);
        }
        // id этого запроса
        $correlationId = self::$syncCallCorrelationId++;

        $chanel = $this->amqp->newChannel();
        // Принимать неограниченное количество сообщений
        $chanel->qos(0, 0);

        //<editor-fold desc="Создаем временную очередь ответов">
        $queue = new \AMQPQueue($chanel);
        $queue->setName($this->amqp->getSyncCallTransactionId());
        // Очередь удалиться когда опустеет
        $queue->setFlags(AMQP_AUTODELETE | AMQP_EXCLUSIVE);
        $queue->declareQueue();
        //</editor-fold>

        //<editor-fold desc="Отправляем запрос на обработку">
        // Точка доступа
        $exchange = new \AMQPExchange($chanel);
        $attributes = array(
            'reply_to' => $this->amqp->getSyncCallTransactionId(),
            'correlation_id' => $correlationId,
        );
        if (!$exchange->publish($message, $service, AMQP_NOPARAM, $attributes)) {
            throw new \Exception('Exchange publish error');
        }
        //</editor-fold>

        //<editor-fold desc="Ожидаем ответ">
        /**
         * @param \AMQPEnvelope $envelope
         * @param \AMQPQueue $queue
         * @return bool
         */
        $processMessage = function ($envelope, $queue) use ($correlationId, &$r) {
            if ($envelope->getCorrelationId() == $correlationId) {
                $r = $envelope;
                return false;
            }
            return true;
        };
        $queue->consume($processMessage);
        //</editor-fold>
        return $r;
    }

    /**
     * Асинхронный вызов
     * @param $service
     * @param $message
     * @param bool $transactionId
     * @return bool|string
     * @throws \Exception
     */
    public function asyncCall($service, $message, $transactionId = false)
    {
        if (is_array($message) || is_object($message)) {
            $message = Json::encode($message);
        }
        if (empty($transactionId)) {
            $transactionId = $this->amqp->newTransactionId();
        }
        $chanel = $this->amqp->newChannel();

        //<editor-fold desc="Создаем временную очередь ответов">
        $queue = new \AMQPQueue($chanel);
        $queue->setName($transactionId);
        // Очередь удалиться когда опустеет
        $queue->setFlags(AMQP_AUTODELETE);
        $queue->declareQueue();
        //</editor-fold>

        //<editor-fold desc="Отправляем запрос на обработку">
        // Точка доступа
        $exchange = new \AMQPExchange($chanel);
        $attributes = array(
            'reply_to' => $transactionId,
        );
        if (!$exchange->publish($message, $service, AMQP_NOPARAM, $attributes)) {
            throw new \Exception('Exchange publish error');
        }
        //</editor-fold>
        return $transactionId;
    }

    /**
     * Получить все ответы и подтвердить их получение
     * @param $transactionId
     * @return \AMQPEnvelope[]
     * @throws \Exception
     */
    public function asyncReadAutoAck($transactionId)
    {
        $r = [];
        /**
         * @param \AMQPEnvelope $envelope
         * @param \AMQPQueue $queue
         * @return bool
         */
        $processMessage = function ($envelope, $queue) use (&$r) {
            $queue->ack($envelope->getDeliveryTag());
            $r[] = $envelope;
        };
        $this->asyncRead($transactionId, $processMessage);
        return $r;
    }

    /**
     * Асинхронное чтение ответов
     * @param string $transactionId
     * @param callable $callBack - Функция вызываемая для каждой строки.
     * Приимвет два параметра
     * \AMQPEnvelope $envelope
     * \AMQPQueue $queue
     * Чтобы подтвердить получение нужно вызвать метод $queue->ack($envelope->getDeliveryTag());
     * Чтобы перейти к следующему элементу без подтверждения получения нужно вызвать метод $queue->cancel($envelope->getDeliveryTag());
     * Если возвращает false, то передор возвращается.
     * @return \AMQPQueue - Возвращает очередь чтобы ее можно было удалить, если она уже не нужна
     * @throws \Exception
     */
    public function asyncRead($transactionId, $callBack)
    {
        if (!is_callable($callBack)) {
            throw new \Exception('Callback not callable');
        }
        $chanel = $this->amqp->newChannel();

        $queue = new \AMQPQueue($chanel);
        $queue->setName($transactionId);
        // Таким образом можно проверить существует ли очередь.
        // Если она не существует, то будет ошибка и нужно вернуть пустой ответ
        $queue->setFlags(AMQP_PASSIVE);
        try {
            $queue->declareQueue();
        } catch (\Exception $e) {
            return $queue;
        }
        /** @var \AMQPEnvelope $envelop */
        while ($envelop = $queue->get()) {
            if (call_user_func($callBack, $envelop, $queue) === false) {
                break;
            }
        }
        return $queue;
    }
}
