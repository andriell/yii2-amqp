<?php namespace andriell\amqp\lib;
/**
 * User: Рыбалко A.M.
 * Date: 25.06.2015
 * Time: 14:17
 */

class AmqpHelper
{
    protected static $sessionId = null;
    protected static $correlationId = 1;

    public static function randomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @return string
     */
    public static function getTmpQueueName()
    {
        if (empty(self::$sessionId)) {
            self::$sessionId = 'user-' . self::randomString(10);
        }
        return self::$sessionId;
    }

    /**
     * @param \AMQPEnvelope $envelope
     * @return string
     */
    public static function envelopeToString(\AMQPEnvelope $envelope)
    {
        $m = '';
        $m .= 'AppId: ' . $envelope->getAppId() . "\n";
        $m .= 'Body: ' . $envelope->getBody() . "\n";
        $m .= 'ContentEncoding: ' . $envelope->getContentEncoding() . "\n";
        $m .= 'ContentType: ' . $envelope->getContentType() . "\n";
        $m .= 'CorrelationId: ' . $envelope->getCorrelationId() . "\n";
        $m .= 'DeliveryTag: ' . $envelope->getDeliveryTag() . "\n";
        $m .= 'ExchangeName: ' . $envelope->getExchangeName() . "\n";
        $m .= 'Expiration: ' . $envelope->getExpiration() . "\n";
        $m .= 'Headers: ' . json_encode($envelope->getHeaders()) . "\n";
        $m .= 'MessageId: ' . $envelope->getMessageId() . "\n";
        $m .= 'Priority: ' . $envelope->getPriority() . "\n";
        $m .= 'ReplyTo: ' . $envelope->getReplyTo() . "\n";
        $m .= 'RoutingKey: ' . $envelope->getRoutingKey() . "\n";
        $m .= 'TimeStamp: ' . $envelope->getTimeStamp() . "\n";
        $m .= 'Type: ' . $envelope->getType() . "\n";
        $m .= 'UserId: ' . $envelope->getUserId() . "\n";
        $m .= 'isRedelivery: ' . $envelope->isRedelivery() . "\n";
        $m .= "\n";
        return $m;
    }
}