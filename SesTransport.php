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
    public fucntion isStarted() {
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
            throw new \Swift_TransportException('to many recipients for AWS SES');
        }
        if(!$this->getSender()) {
            throw new \Swift_TransportException('sender must be specified');
        }
        foreach($to as $address => $recipient) {
            $_to[] = $this->_formatAddress($address, $recipient);
        }
        foreach($cc as $address => $recipeint) {
            $_cc[] = $this->_formatAddress($address, $recipient);
        }
        foreach($bcc as $address => $recipeint) {
            $_bcc[] = $this->_formatAddress($address, $recipient);
        }


        //$request = array();
        //$request['Source'] = $mail->sender_email;
        //$request['Destination']['ToAddresses'] = [$mail->format_recipient()];
        //$request['Message']['Subject']['Data'] = $mail->subject;
        //$request['Message']['Subject']['Charset'] = 'UTF-8';
        //$request['Message']['Body']['Text']['Data'] = $mail->body;
        //$request['Message']['Body']['Text']['Charset'] = 'UTF-8';
        //

        $message = [
            'ReturnPath' => $this->_getReversePath,
            'Source' => $this->getSender(),
            //'ReplyToAddresses' => [],
            'Destination' => [
                'ToAddresses' => $_to,
                'CcAddresses' => $_cc,
                'BccAddresses' => $_bcc,
            ],
            'Message' => [
                'Subject' => [
                    'Data' => $mail->getSubject(),
                    'Charset' => 'UTF-8',
                ],
                'Body' => [
                    'Data' => $mail->getBody(),
                    'Charset' => 'UTF-8',
                ],
            ]
        ];

        try {
            $result = $this->client->sendEmail($request);
            $messageId = $result->get('MessageId');
            return null;
        } catch (Exception $e) {
            return $e->getMessage();
        }


        $message->setBcc([]);

        if ($evt) {
            $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
            $this->_eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        return count($to) + count($cc);
    }
}