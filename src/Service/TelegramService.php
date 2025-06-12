<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramService
{
    private string $botToken;
    private string $chatId;
    private HttpClientInterface $httpClient;
    private Client $client;

    public function __construct(string $botToken, string $chatId, HttpClientInterface $httpClient)
    {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->httpClient = $httpClient;
        $this->client = new Client();
    }

    public function sendMessage(string $message): void
    {
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $this->botToken);

        $this->httpClient->request('POST', $url, [
            'body' => [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ],
        ]);
    }

    public function sendDocument(string $filePath, ?string $caption = null): void
    {
        $url = sprintf('https://api.telegram.org/bot%s/sendDocument', $this->botToken);

        $multipart = [
            [
                'name' => 'chat_id',
                'contents' => $this->chatId,
            ],
            [
                'name' => 'document',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
        ];

        if ($caption) {
            $multipart[] = [
                'name' => 'caption',
                'contents' => $caption,
            ];
            $multipart[] = [
                'name' => 'parse_mode',
                'contents' => 'HTML',
            ];
        }

        $this->client->post($url, [
            'multipart' => $multipart,
        ]);
    }
}
