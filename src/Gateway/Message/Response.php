<?php

namespace Omnipay\ZeroValue\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Zero Value Gateway Response
 */
class Response extends AbstractResponse
{
    public function isSuccessful()
    {
        return true;
    }
}
