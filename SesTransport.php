<?php

namespace jakumi\SymfonySes;

use Aws\Ses\SesClient;

class SesTransport extends \Swift_Transport_AbstractSmtpTransport {

    private $client;

    function __construct(\Swift_Events_EventDispatcher $eventDispatcher, SesClient $client) {
        $this->_eventDispatcher = $eventDispatcher;
        $this->client = $client;
    }

    const MAX_RECIPIENTS = 50;

    protected function _getBufferParams() {
        return [];
    }

    protected function _formatAddress(string $address, string $name=null) {
        if(!empty($name)) {
            return '=?utf-8?B?'.\Swift_Encoding::getBase64Encoding()->encodeString($recipient).'?=" <'.$address.'>';
        } else {
            return $address;
        }
    }

    public function start() {}
    public function stop() {}
    public function isStarted() {
        return true;
    }


    /**
     * Sends the given message.
     *
     * @param \Swift_Mime_Message $message
     * @param string[]           $failedRecipients An array of failures by-reference
     *
     * @return int The number of sent emails
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null) {

        if ($evt = $this->_eventDispatcher->createSendEvent($this, $message)) {
            $this->_eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }
        $to = (array) $message->getTo();
        $cc = (array) $message->getCc();
        $tos = array_merge($to, $cc);
        $bcc = (array) $message->getBcc();
        $count = (
            count((array) $message->getTo())
            + count((array) $message->getCc())
            + count((array) $message->getBcc())
        );
        if($number > static::MAX_RECIPIENTS) {
            throw new Swift_TransportException('to many recipients for AWS SES');
        }
        if(!$this->getSender()) {
            throw new Swift_TransportException('sender must be specified');
        }

        try {
            $result = $this->client->sendRawEmail([
                'RawMessage'=> [
                    'Data' => $message->toString(),
                ],
            ]);
            $messageId = $result->get('MessageId');
        } catch (\Exception $e) {
            throw new \Swift_TransportException($e->getMessage());
        }


        $message->setBcc([]);

        if ($evt) {
            $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
            $this->_eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        return count($to) + count($cc);
    }
}