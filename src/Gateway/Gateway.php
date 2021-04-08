<?php
/**
 * Manual Gateway
 */

namespace Omnipay\ZeroValue;

use Omnipay\Common\AbstractGateway;
use Omnipay\ZeroValue\Message\Request;

/**
 * Zero Value Gateway
 *
 * This gateway is basically a clone of the omnipay manual gateway, but
 * intended to specifically support Zero Value orders (such as a free download).
 * 
 * The gateway simply authorizes every payment passed to it. By default this
 * gateway is not registered to SilverStripe Omnipay and is automatically
 * used when the order had a zero value.
 *
 */
class Gateway extends AbstractGateway
{
    const GATEWAY_NAME = 'ZeroValue';

    public function getName()
    {
        return self::GATEWAY_NAME;
    }

    public function getDefaultParameters()
    {
        return array();
    }

    public function authorize(array $parameters = array())
    {
        return $this->createRequest(Request::class, $parameters);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest(Request::class, $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(Request::class, $parameters);
    }

    public function completeAuthorise(array $parameters = array())
    {
        return $this->createRequest(Request::class, $parameters);
    }

    public function capture(array $parameters = array())
    {
        return $this->createRequest(Request::class, $parameters);
    }

    public function void(array $parameters = array())
    {
        return $this->createRequest(Request::class, $parameters);
    }

    public function refund(array $parameters = array())
    {
        return $this->createRequest(Request::class, $parameters);
    }
}
