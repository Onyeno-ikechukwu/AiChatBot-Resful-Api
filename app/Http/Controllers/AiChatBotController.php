<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatBotRequest;
use App\Http\Resources\AiChatBotResource;
use App\Models\AiChatBot;
use Illuminate\Http\Request;

class AiChatBotController extends Controller
{
    /**
     * List the authenticated user's AI chat conversations.
     *
     * Returns a paginated list of the user's chat conversations.
     * Supports searching by title/description and sorting by fields like title, price, created_at, etc.
     * Prefix sort field with "-" for descending order (e.g. "-created_at").
     */
    Public function index(Request $request, AiChatBotRequest $requesValidator){

        $posts = $requesValidator->indexValidationRule($request);
        return response()->json(AiChatBotResource::collection($posts));
    }

    /**
     * Create a new AI chat conversation.
     *
     * Sends the user's text prompt and/or images to the Groq AI service, then stores the resulting conversation.
     * Unauthenticated (non-subscribed) users are limited to 110 free chats per day.
     * Requires either a "user_prompt" text or "images" file, or both.
     */
    public function store(AiChatBotRequest $request)
    {
        $paths= [];

        $chat = $request->storeValidationRule($request, $paths);

        return response()->json(new AiChatBotResource($chat));
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
        $chat = $chat->where('user_id', $request->user()->id)->first();

        return response()->json(new AiChatBotResource($chat)); 
    }

    /**
     * Update an existing chat conversation.
     *
     * Updates the user prompt and/or images of a chat, then re-generates the AI response.
     * Only the owner of the chat can update it.
     *
     * @param AiChatBot $chat The chat conversation instance to update (auto-resolved via route-model binding).
     */
    public function update(AiChatBot $chat, AiChatBotRequest $request)
    {
        $paths= [];
        $show = $request->updateValidationRule($request, $chat, $paths);

        return response()->json(new AiChatBotResource($show));
    }

    /**
     * Delete a chat conversation.
     *
     * Permanently removes a chat conversation. Only the owner can delete their own chats.
     *
     * @param AiChatBot $chat The chat conversation instance to delete (auto-resolved via route-model binding).
     */
    function destroy(AiChatBot $chat,AiChatBotRequest $requestValidator){
        $requestValidator->destroyValidationRule($chat);
        return response()->json(['message' => 'AiChatBot deleted successfully.'], 200);
    }
}