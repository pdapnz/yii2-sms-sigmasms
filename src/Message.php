<?php

namespace pdapnz\sms\sigmasms;

use wadeshuler\sms\BaseMessage;

class Message extends BaseMessage
{

    /**
     * Nicename function for getTextBody()
     */
    public function getMessage()
    {
        return $this->getTextBody();
    }

    /**
     * Nicename function for setTextBody()
     */
    public function setMessage($text)
    {
        $this->setTextBody($text);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toString()
    {
        return $this->getTextBody();
    }

}
