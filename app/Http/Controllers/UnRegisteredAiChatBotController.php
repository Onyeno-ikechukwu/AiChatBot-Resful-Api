<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnRegisteredAiChatBotRequest;
use App\Http\Resources\UnRegisteredAiChatBotResource;
use App\Models\AiChatBot;
use Illuminate\Http\Request;

class UnRegisteredAiChatBotController extends Controller
{
    /**
     * List the authenticated user's AI chat conversations.
     *
     * Returns a paginated list of the user's chat conversations.
     * Supports searching by title/description and sorting by fields like title, price, created_at, etc.
     * Prefix sort field with "-" for descending order (e.g. "-created_at").
     */
    Public function index(Request $request, UnRegisteredAiChatBotRequest $requestValidator){
        $posts = $requestValidator->indexValidationRule($request);
        return response()->json(UnRegisteredAiChatBotResource::collection($posts));
    }

    /**
     * Create a new AI chat conversation.
     *
     * Sends the user's text prompt and/or images to the Groq AI service, then stores the resulting conversation.
     * Unauthenticated (non-subscribed) users are limited to 110 free chats per day.
     * Requires either a "user_prompt" text or "images" file, or both.
     */
    public function store(UnRegisteredAiChatBotRequest $request)
    {
        $paths= [];

        $chat = $request->storeValidationRule($request, $paths);

        return response()->json(new UnRegisteredAiChatBotResource($chat));
    }

    /**
     * Get a specific chat conversation by ID.
     *
     * Returns the details of a single chat conversation that belongs to the authenticated user.
     *
     * @param AiChatBot $chat The chat conversation instance (auto-resolved via route-model binding).
     */
    public function show(AiChatBot $chat, Request $request)
    {
        $chat = $chat->where('ip_address', $request->ip())->first();

        return response()->json(new UnRegisteredAiChatBotResource($chat)); 
    }

    /**
     * Update an existing chat conversation.
     *
     * Updates the user prompt and/or images of a chat, then re-generates the AI response.
     * Only the owner of the chat can update it.
     *
     * @param AiChatBot $chat The chat conversation instance to update (auto-resolved via route-model binding).
     */
    public function update(AiChatBot $chat, UnRegisteredAiChatBotRequest $request)
    {
        $paths= [];
        $show = $request->updateValidationRule($request, $chat, $paths);

        return response()->json(new UnRegisteredAiChatBotResource($show));
    }

    /**
     * Delete a chat conversation.
     *
     * Permanently removes a chat conversation. Only the owner can delete their own chats.
     *
     * @param AiChatBot $chat The chat conversation instance to delete (auto-resolved via route-model binding).
     */
    function destroy(AiChatBot $chat,UnRegisteredAiChatBotRequest $request){
        abort_if($request ->ip() != $chat->ip_address, 403, 'Access Forbidden');
        $chat->delete(); 
        return response()->json(['message' => $chat->ip_address], 200);
    }
}