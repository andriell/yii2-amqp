<?php namespace andriell\amqp\lib;
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 14.07.2015
 * Time: 9:12
 */

class AmqpResp implements \ArrayAccess
{
    const kStatus = 'status';
    const kAction = 'action';
    const kBody = 'body';
    const kErrors = 'errors';
    const kErrorsCode = 'code';
    const kErrorsMessage = 'message';

    const vStatusOk = 'Ok';
    const vStatusError = 'Error';

    protected $data = [
        self::kStatus => self::vStatusOk,
        self::kAction => 'Unknown',
        self::kBody => null,
    ];

    public function setAction($action)
    {
        $this->data[self::kAction] = $action;
    }

    public function setBody($body)
    {
        $this->data[self::kBody] = $body;
    }

    public function addError($code, $message)
    {
        $this->data[self::kStatus] = self::vStatusError;
        if (!isset($this->data[self::kErrors])) {
            $this->data[self::kErrors] = [];
        }
        $this->data[self::kErrors][] = [
            self::kErrorsCode => $code,
            self::kErrorsMessage => $message,
        ];
    }


    public function addException(\Exception $e)
    {
        $this->addError($e->getCode(), $e->getMessage());
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}