<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WhatsappService
{
    private const WHATSAPP_API_URL = 'https://graph.facebook.com/v18.0';

    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
        private string $whatsappApiToken,
        private string $whatsappPhoneNumberId
    ) {
    }

    public function sendMessage(string $phoneNumber, string $message): void
    {
        
        if (empty($this->whatsappApiToken) || empty($this->whatsappPhoneNumberId)) {
            $this->logger->warning('WhatsApp API credentials are not configured. Skipping message sending.');
            return;
        }

        try {
            $response = $this->httpClient->request('POST', self::WHATSAPP_API_URL . '/' . $this->whatsappPhoneNumberId . '/messages', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->whatsappApiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $phoneNumber,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Failed to send WhatsApp message', [
                    'statusCode' => $response->getStatusCode(),
                    'response' => $response->getContent(false),
                ]);
            } else {
                $this->logger->info('WhatsApp message sent successfully to ' . $phoneNumber);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error sending WhatsApp message: ' . $e->getMessage());
        }
    }
}