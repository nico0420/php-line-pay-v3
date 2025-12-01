<?php

namespace NICO0420\LinePay;

class Response
{
    /**
     * @var object \GuzzleHttp\Psr7\Response $response
     */
    public $response;

    /**
     * @var object \GuzzleHttp\TransferStats $stats
     */
    public $stats;

    /**
     * @var array Cache data for body with array data type
     */
    protected $bodyArrayCache = null;

    /**
     * @var object Cache data for body with array object type
     */
    protected $bodyObjectCache = null;

    /**
     * Constructor
     *
     * @param object \GuzzleHttp\Psr7\Response $response
     */
    public function __construct(\GuzzleHttp\Psr7\Response $response, \GuzzleHttp\TransferStats $stats)
    {
        $this->response = $response;
        $this->stats = $stats;
    }

    /**
     * Get \GuzzleHttp\TransferStats object
     *
     * @return \GuzzleHttp\TransferStats
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Get LINE Pay response body as array
     *
     * @return array
     */
    public function toArray()
    {
        // Cache
        if (!$this->bodyArrayCache) {
            $this->bodyArrayCache = json_decode($this->response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
        }

        return $this->bodyArrayCache;
    }

    /**
     * Get LINE Pay response body as object
     *
     * @return object
     */
    public function toObject()
    {
        // Cache
        if (!$this->bodyObjectCache) {
            $this->bodyObjectCache = json_decode($this->response->getBody(), false, 512, JSON_BIGINT_AS_STRING);
        }

        return $this->bodyObjectCache;
    }

    /**
     * Get LINE Pay API response body's returnCode
     *
     * @return string
     */
    public function getReturnCode()
    {
        return $this->offsetGet('returnCode');
    }

    /**
     * Get LINE Pay API response body's returnMessage
     *
     * @return string
     */
    public function getReturnMessage()
    {
        return $this->offsetGet('returnMessage');
    }

    /**
     * Get LINE Pay API response body's info as array
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->offsetGet('info');
    }

    /**
     * Get LINE Pay API response body's info.paymentUrl (Default type is "web")
     *
     * @return string URL
     */
    public function getPaymentUrl($type = 'web')
    {
        $info = $this->offsetGet('info');

        return isset($info['paymentUrl'][$type]) ? $info['paymentUrl'][$type] : null;
    }

    /**
     * Get LINE Pay API response body's info.payInfo[] or info.[$param1].payInfo[] as array
     *
     * @param integer $itemKey
     * @return array
     */
    public function getPayInfo($itemKey = 0)
    {
        $info = $this->offsetGet('info');
        // Check for confirm or details response format
        if (isset($info['payInfo'])) {
            return $info['payInfo'];
        } elseif (isset($info[$itemKey]['payInfo'])) {
            return $info[$itemKey]['payInfo'];
        }
        return null;
    }

    /**
     * LINE Pay API result successful status
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        if ($this->getReturnCode() == '0000') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Magic method __get()
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $data = $this->toObject();

        return isset($data->$name) ? $data->$name : null;
    }

    /**
     * ArrayAccess method offsetExists
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->toArray()[$offset]);
    }

    /**
     * ArrayAccess method offsetGet
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $data = $this->toArray();
        return isset($data[$offset]) ? $data[$offset] : null;
    }
}
