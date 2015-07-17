<?php namespace andriell\amqp\lib;
/**
 * User: Рыбалко A.M.
 * Date: 25.06.2015
 * Time: 14:07
 */

use \Exception;
use yii\base\Component;

class Amqp extends Component {
    /**
     * @var \AMQPConnection
     */
    protected static $ampqConnection = null;
    /**
     * @var string
     */
    protected $syncCallTransactionId = null;
    /**
     * @var string
     */
    public $host = '127.0.0.1';
    /**
     * @var integer
     */
    public $port = 5672;
    /**
     * @var string
     */
    public $user;
    /**
     * @var string
     */
    public $password;
    /**
     * @var string
     */
    public $vhost = '/';

    /**
     * @inheritdoc
     */
    public function init() {
        if (empty($this->user)) {
            throw new \Exception("Parameter 'user' was not set for AMQP connection.");
        }
        if (empty(self::$ampqConnection)) {
            self::$ampqConnection = new \AMQPConnection();
            self::$ampqConnection->setHost($this->host);
            self::$ampqConnection->setPort($this->port);
            self::$ampqConnection->setLogin($this->user);
            self::$ampqConnection->setPassword($this->password);
            self::$ampqConnection->setVhost($this->vhost);
            self::$ampqConnection->connect();
        }
    }

    /**
     * @return \AMQPConnection
     */
    public function getConnection() {
        return self::$ampqConnection;
    }

    /**
     * @return \AMQPChannel
     */
    public function newChannel() {
        return new \AMQPChannel($this->getConnection());
    }

    /**
     * Новый идентификатор транзакции
     * @param string $type
     * @param bool $date
     * @param bool $key
     * @return string
     */
    public function newTransactionId($type = 'default', $date = false, $key = false) {
        if (empty($date)) {
            $date = date('YmdHis');
        }
        if (empty($key)) {
            $key = AmqpHelper::randomString();
        } elseif (strlen($key) > 32) {
            $key = md5($key);
        }
        return 'app.' . $this->user . '.' . $type . '.' . $date . '-' . $key;
    }

    /**
     * Идентификатор транзакции для синхонного вызова
     * @return string
     */
    public function getSyncCallTransactionId() {
        if (empty($this->syncCallTransactionId)) {
            $this->syncCallTransactionId = $this->newTransactionId('syncCall');
        }
        return $this->syncCallTransactionId;
    }
}