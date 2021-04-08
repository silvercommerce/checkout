<?php

namespace Omnipay\ZeroValue\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Zero Value Gateway Request
 */
class Request extends AbstractRequest
{
    public function getData()
    {
        $this->validate('amount');

        return $this->getParameters();
    }

    public function sendData($data)
    {
        return $this->response = new Response($this, $data);
    }
}
