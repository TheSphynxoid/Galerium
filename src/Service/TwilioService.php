<?php
namespace App\Service;

use Twilio\Rest\Client;

class TwilioService
{
    private string $sid;
    private string $authToken;
    private string $from;

    public function __construct(string $twilioSid, string $twilioAuthToken, string $twilioFrom)
    {
        $this->sid = $twilioSid;
        $this->authToken = $twilioAuthToken;
        $this->from = $twilioFrom;
    }

    public function sendSms(string $to, string $message): void
    {
        $client = new Client($this->sid, $this->authToken);
        $client->messages->create($to, [
            'from' => $this->from,
            'body' => $message,
        ]);
    }
}
