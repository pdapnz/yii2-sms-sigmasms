<?php

namespace pdapnz\sms\sigmasms;

use wadeshuler\sms\BaseSms;
use Yii;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\web\HttpException;

/**
 * Sms is a wrapper component for the Sigma SMS API.
 *
 * To use Sms, you should configure it in the application configuration like the following:
 *
 * ```php
 * [
 *     'components' => [
 *         'sms' => [
 *             'class' => 'pdapnz\sms\sigmasms\Sms',
 *             'viewPath' => '@common/sms',     // Optional: defaults to '@app/sms'
 *
 *             // send all sms to a file by default. You have to set
 *             // 'useFileTransport' to false and configure $messageConfig['from'],
 *             // 'sid', and 'token'
 *             'useFileTransport' => true,
 *
 *             'messageConfig' => [
 *                 'from' => 'senderName',  //имя отппавителя в сообщениях
 *             ],
 *
 *             'username' => 'SigmaSmsApiUser', //имя пользователя
 *             'password' => 'SigmaSmsApiPassword' //пароль
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ],
 * ```
 *
 * To send an SMS, you may use the following code:
 *
 * ```php
 * Yii::$app->sms->compose('test-message', ['user' => $user])
 *     ->setFrom('12345')       // Your SigmaSMS number (shortcode or full number)
 *     ->setTo('+15552224444')  // Full number including '+' and country code
 *     ->send();
 * ```
 *
 * -- or --
 *
 * ```php
 * Yii::$app->sms->compose()
 *     ->setFrom('12345')       // Your SigmaSMS number (shortcode or full number)
 *     ->setTo('+15552224444')  // Full number including '+' and country code
 *     ->setMessage('Hello ' . $name . ', This is a test message!')
 *     ->send();
 * ```
 *
 */
class Sms extends BaseSms
{
    /**
     * @var string message default class name.
     */
    public $messageClass = 'pdapnz\sms\sigmasms\Message';
    //public $from;
    public $username;
    public $password;

    private $TOKEN_LIFETIME_SECONDS = 60 * 60 * 6; //6 hours
    private $_baseUrl = 'https://online.sigmasms.ru/api/';
    private $token;
    private $tokenReceived;

    public function init()
    {
        if ($this->useFileTransport === false) {
            if (!isset($this->username) || empty($this->username)) {
                throw new InvalidConfigException(self::class . ": SigmaSMS 'username' configuration parameter is required!");
            }

            if (!isset($this->password) || empty($this->password)) {
                throw new InvalidConfigException(self::class . ": SigmaSMS 'password' configuration parameter is required!");
            }
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function sendMessage($message)
    {
        /* @var $message Message */
        try {
            $from = $message->getFrom();
            $to = $message->getTo();

            if (!isset($from) || empty($from)) {
                throw new InvalidConfigException(self::class . ": Invalid 'from' phone number!");
            }

            if (!isset($to) || empty($to)) {
                throw new InvalidConfigException(self::class . ": Invalid 'to' phone number!");
            }

            $this->loginIfExpired();
            $response = $this->getHttpResponse('sendings', 'POST',
                [
                    'recipient' => $to,
                    'type' => 'sms',
                    'payload' => [
                        'sender' => $from,
                        'text' => $message->toString()
                    ]
                ]);

            return $response;

        } catch (\Exception $e) {
            file_put_contents(Yii::getAlias('@runtime') . '/logs/sms-exception.log', '[' . date('m-d-Y h:i:s a', time()) . '] SMS Failed - Phone: ' . $to . PHP_EOL . $e->getMessage() . PHP_EOL . '---' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return false;
    }

    private function login()
    {
        $response = $this->getHttpResponse('login', 'POST', ['username' => $this->username, 'password' => $this->password]);
        $this->token = $response['token'];
        $this->tokenReceived = time();
        //Сохраняем токен и дату его формирования
        Yii::$app->cache->set('sigma_sms_token', $this->token);
        Yii::$app->cache->set('sigma_sms_token_received', $this->tokenReceived);
    }

    private function isTokenExpired()
    {
        if (!$this->token) $this->token = Yii::$app->cache->get('sigma_sms_token');
        if (!$this->tokenReceived) $this->tokenReceived = Yii::$app->cache->get('sigma_sms_token_received');
        return !$this->token || time() - $this->tokenReceived >= $this->TOKEN_LIFETIME_SECONDS;
    }

    public function loginIfExpired()
    {
        if ($this->isTokenExpired())
            $this->login();
        return $this->token;
    }

    private function getHttpResponse($url, $method, $data = null)
    {
        Yii::info($method . " " . $this->_baseUrl . $url . "\n" . json_encode($data, JSON_UNESCAPED_UNICODE), 'yii-sms\httpclient\*');
        $api = new Client(['baseUrl' => $this->_baseUrl]);
        $response = $api->createRequest()
            ->setHeaders(['Authorization' => $this->token])
            ->setMethod($method)
            ->setFormat(yii\httpclient\Client::FORMAT_JSON)
            ->setUrl($url)
            ->setData($data)
            ->send();
        Yii::info($response->data, 'yii-sms\httpclient\*');
        return $response->data;
    }

    /**
     * @param $response
     * @throws HttpException
     */
    private function checkResponse($response)
    {
        if (!$response->isOk) {
            $previousException = null;
            $code = 0;
            if (isset($response->data['code'])) $code = $response->data['code'];
            if (isset($response->data['type'])) {
                $class = $response->data['type'];
                $previousException = new $class($response->data['message'], $code);
            }
            throw new Exception("Error '" . $response->getStatusCode() . "' during request to CRM. " . json_encode($response->data, JSON_UNESCAPED_UNICODE), $code, $previousException);
        }
        return $response->data;
    }


}
