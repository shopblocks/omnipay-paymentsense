<?php

namespace Coatesap\PaymentSense\Message;

use DOMDocument;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * PaymentSense Response
 */
class Response extends AbstractResponse implements RedirectResponseInterface
{
    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;

        $this->data = json_decode($data);

        if (!isset($this->data) || empty($this->data)) {
            throw new InvalidResponseException;
        }
    }

    public function getResultElement()
    {
        $resultElement = preg_replace('/Response$/', 'Result', $this->data->getName());

        return $this->data->$resultElement;
    }

    public function isSuccessful()
    {
        return 0 === (int) $this->getResultElement()->StatusCode;
    }

    public function isRedirect()
    {
        return 3 === (int) $this->getResultElement()->StatusCode;
    }

    public function getTransactionReference()
    {
        return (string) $this->data->TransactionOutputData['CrossReference'];
    }

    public function getMessage()
    {
        return (string) $this->getResultElement()->Message;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getRedirectUrl()
    {
        if ($this->isRedirect()) {
            return (string) $this->data->TransactionOutputData->ThreeDSecureOutputData->ACSURL;
        }
    }

    public function getRedirectMethod()
    {
        return 'POST';
    }

    public function getRedirectData()
    {
        return $redirectData = array(
            'PaReq' => (string) $this->data->TransactionOutputData->ThreeDSecureOutputData->PaREQ,
            'TermUrl' => $this->getRequest()->getReturnUrl(),
            'MD' => (string) $this->data->TransactionOutputData['CrossReference'],
        );
    }
}
