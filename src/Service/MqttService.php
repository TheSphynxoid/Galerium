<?php

namespace App\Service;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttService
{
    private string $brokerHost;
    private int $brokerPort;
    private string $topic;

    public function __construct()
    {
        $this->brokerHost = 'broker.hivemq.com';
        $this->brokerPort = 1883;
        $this->topic = 'sms/send';
    }

    /**
     * Send an MQTT message
     *
     * @param string $to Phone number of the recipient
     * @param string $message Message content
     * @return bool True if message was sent successfully, false otherwise
     */
    public function sendSms(string $to, string $message): bool
    {
        try {
            $mqtt = new MqttClient($this->brokerHost, $this->brokerPort, 'galerium_client_' . uniqid());
            
            $connectionSettings = (new ConnectionSettings())
                ->setKeepAliveInterval(60)
                ->setLastWillTopic($this->topic)
                ->setLastWillMessage('client disconnected')
                ->setLastWillQualityOfService(1);

            $mqtt->connect($connectionSettings, true);

            $payload = json_encode([
                'to' => $to,
                'message' => $message
            ]);

            $mqtt->publish($this->topic, $payload, 1);
            $mqtt->disconnect();

            return true;
        } catch (\Exception $e) {
            // Log error if needed
            error_log('MQTT Error: ' . $e->getMessage());
            return false;
        }
    }
}

