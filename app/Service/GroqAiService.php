<?php

namespace App\Service;

use App\Models\AiChatBot;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class GroqAiService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    private function buildMessages($history, $currentPrompt)
    {
        $messages = [];

        foreach ($history as $chat) {

            if ($chat->user_prompt) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $chat->user_prompt
                ];
            }

            if ($chat->ai_response) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $chat->ai_response
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $currentPrompt
        ];

        return $messages;
    }

    

    public function getChatImagePrompt(UploadedFile $image, string $userPromt, array $history){
        $imageData = base64_encode(file_get_contents($image->getpathname()));
        $mimeType = $image->getMimeType();

        $messages = [];

        foreach ($history as $chat) {

            if (!empty($chat->user_prompt) && is_string($chat->user_prompt)) {

                $messages[] = [
                    'role' => 'user',
                    'content' => $chat->user_prompt,
                ];
            }

            if (!empty($chat->ai_response) && is_string($chat->ai_response)) {

                $messages[] = [
                    'role' => 'assistant',
                    'content' => $chat->ai_response,
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => $userPromt
                ],
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:'.$mimeType.';base64,'.$imageData
                    ]
                ]
            ]
        ];
        
        $response = Http::withToken(config('services.groq.api_key'))
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
                'messages' => $messages 
        
        ]);
        
        return $response['choices'][0]['message']['content'];


    } 

    public function getChatImage(UploadedFile $images){
        $imageData = base64_encode(file_get_contents($images->getpathname()));
        $mimeType = $images->getMimeType();

        $response = Http::withToken(config('services.groq.api_key'))
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
                    'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'analyse the following image and answer all your findings ina an educative manner'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $mimeType . ';base64,' . $imageData
                            ]
                        ]
                    ]
                ]
            ]
            
        ]);
        return $response['choices'][0]['message']['content'];

    }

    public function getChatPrompt(string $userPrompt, Collection $history)
    {
        $messages = $this->buildMessages($history, $userPrompt);

        $response = Http::withToken(config('services.groq.api_key'))
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => $messages
            ]);

        return $response['choices'][0]['message']['content'];
    }
}
