<?php

namespace DS\PepiPost\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Mail\Transport\Transport;
use Swift_Attachment;
use Swift_Image;
use Swift_Mime_Message;


class PepiPostTransport extends Transport
{

    const SMTP_API_NAME = 'pepipostapi';

    const MAXIMUM_FILE_SIZE = 20480000;

    const BASE_URL = 'https://netcoreapp.spicejet.com/netcore-api/sendEmailNoAttachment';

    /**
     * @var Client
     */
    private $client;
    private $attachments;
    private $numberOfRecipients;
    private $apiKey;
    private $endpoint;

    public function __construct(ClientInterface $client, $api_key, $endpoint = null)
    {

        $this->client = $client;
        $this->apiKey = $api_key;
        $this->endpoint = isset($endpoint) ? $endpoint : self::BASE_URL;
    }

    /**
     * Undocumented function
     *
     * @param Swift_Mime_Message $message
     * @param [type] $failedRecipients
     * @return void
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        
        $data = [
            "from" =>  $this->getFrom($message)['fromEmail'],
            "subject" => $message->getSubject(),
            "plainTextContent" => "",
            "htmlContent" => $this->getContents($message),
            "to" => [
                $this->getTo($message)[0]['recipient']
            ],
            "applicationKey" => $this->apiKey,
            "bcc" => [],
            "cc" => []
        ];
        $payload = [
            'headers' => [
                'Content-Type' => 'application/json',
                'user-agent'   => 'pepi-laravel-lib v1',
            ],
            'json' => $data,
        ];

        $response = $this->post($payload);

        return $response;
    }

    /**
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getPersonalizations(Swift_Mime_Message $message)
    {
        $setter = function (array $addresses) {
            $recipients = [];
            foreach ($addresses as $email => $name) {
                $address = [];
                $address['email'] = $email;
                if ($name) {
                    $address['name'] = $name;
                }
                $recipients[] = $address;
            }
            return $recipients;
        };
        $personalization = $this->getTo($message);

        return $personalization;
    }


    /**
     * Get From Addresses.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getTo(Swift_Mime_Message $message)
    {

        $this->numberOfRecipients = 0;
        if ($message->getTo()) {
            $toarray = [];
            foreach ($message->getTo() as $email => $name) {
                $recipient = [];
                $recipient['recipient'] = $email;
                if ($cc = $message->getCc()) {
                    $recipient['recipient_cc'] = $this->getCC($message);
                }
                if ($bcc = $message->getBcc()) {
                    $recipient['recipient_bcc'] = $this->getBCC($message);
                }
                $toarray[] = $recipient;
                ++$this->numberOfRecipients;
            }
        }
        return $toarray;
    }
    /**
     * Get From Addresses.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getCC(Swift_Mime_Message $message)
    {
        $ccarray = array();
        if ($message->getCc()) {
            foreach ($message->getCc() as $email => $name) {
                $ccarray[] = $email;
            }
        }
        return $ccarray;
    }

    /**
     * Get From Addresses.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getBCC(Swift_Mime_Message $message)
    {
        $bcc = [];
        if ($message->getBcc()) {
            foreach ($message->getBcc() as $email => $name) {
                $bcc[] = $email;
            }
        }
        return $bcc;
    }


    /**
     * Get From Addresses.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getFrom(Swift_Mime_Message $message)
    {
        if ($message->getFrom()) {
            foreach ($message->getFrom() as $email => $name) {
                return ['fromEmail' => $email, 'fromName' => $name];
            }
        }
        return [];
    }

    /**
     * Get ReplyTo Addresses.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getReplyTo(Swift_Mime_Message $message)
    {
        if ($message->getReplyTo()) {
            foreach ($message->getReplyTo() as $email => $name) {
                return $email;
            }
        }
        return null;
    }

    /**
     * Get contents.
     * @param Swift_Mime_Message $message
     * @return string
     */
    private function getContents(Swift_Mime_Message $message)
    {
        return $message->getBody();
    }

    /**
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getAttachments(Swift_Mime_Message $message)
    {
        $attachments = [];
        foreach ($message->getChildren() as $attachment) {
            if (
                (!$attachment instanceof Swift_Attachment && !$attachment instanceof Swift_Image)
                || $attachment->getFilename() === self::SMTP_API_NAME
                || !strlen($attachment->getBody()) > self::MAXIMUM_FILE_SIZE
            ) {
                continue;
            }
            $attachments[] = [
                'fileContent'     => base64_encode($attachment->getBody()),
                'fileName'    => $attachment->getFilename(),
            ];
        }
        return $this->attachments = $attachments;
    }

    /**
     * Set Request Body Parameters
     *
     * @param Swift_Mime_Message $message
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function setParameters(Swift_Mime_Message $message, $data)
    {
        $smtp_api = [];
        foreach ($message->getChildren() as $attachment) {
            if (
                !$attachment instanceof Swift_Image ||
                !in_array(
                    self::SMTP_API_NAME,
                    [
                        $attachment->getFilename(),
                        $attachment->getContentType()
                    ]
                )
            ) {
                continue;
            }

            $smtp_api = $attachment->getBody();
        }

        foreach ($smtp_api as $key => $val) {

            switch ($key) {

                case 'settings':
                    $this->setSettings($data, $val);
                    continue 2;
                case 'tags':
                    array_set($data, 'tags', $val);
                    continue 2;
                case 'templateId':
                    array_set($data, 'templateId', $val);
                    continue 2;
                case 'personalizations':
                    $this->setPersonalizations($data, $val);
                    continue 2;

                case 'attachments':
                    $val = array_merge($this->attachments, $val);
                    break;
            }


            array_set($data, $key, $val);
        }
        return $data;
    }

    /**
     * @param $data
     * @param $personalizations
     */
    private function setPersonalizations(&$data, $personalizations)
    {

        foreach ($personalizations as $index => $params) {

            if ($this->numberOfRecipients <= 0) {
                array_set($data, 'personalizations' . '.' . $index, $params);
                continue;
            }
            $count = 0;
            while ($count < $this->numberOfRecipients) {
                if (in_array($params, ['attributes', 'x-apiheader', 'x-apiheader_cc']) && !in_array($params, ['recipient', 'recipient_cc'])) {
                    array_set($data, 'personalizations.' . $count . '.' . $index, $params);
                } else {
                    array_set($data, 'personalizations.' . $count . '.' . $index, $params);
                }
                $count++;
            }
        }
    }

    /**
     * @param $data
     * @param $settings
     */
    private function setSettings(&$data, $settings)
    {
        foreach ($settings as $index => $params) {
            array_set($data, 'settings.' . $index, $params);
        }
    }

    /**
     * @param $payload
     * @return Response
     */
    private function post($payload)
    {
        return $this->client->post($this->endpoint, $payload);
    }
}
