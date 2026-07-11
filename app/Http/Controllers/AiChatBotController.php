<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatBotRequest;
use App\Http\Resources\AiChatBotResource;
use App\Models\AiChatBot;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Service\GroqAiService;
use Illuminate\Support\Facades\Auth;

class AiChatBotController extends Controller
{
    protected GroqAiService $groqAiService;

     public function __construct(GroqAiService $groqAiService){
        $this->groqAiService = $groqAiService;
    }


    /**
     * List the authenticated user's AI chat conversations.
     *
     * Returns a paginated list of the user's chat conversations.
     * Supports searching by title/description and sorting by fields like title, price, created_at, etc.
     * Prefix sort field with "-" for descending order (e.g. "-created_at").
     */
    Public function index(Request $request){

        $user = $request->user();

        $query = AiChatBot::where('user_id', $user->id);

        if ($request->has('search') && !empty($request->search)){
            $query->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $allowedSorts = ['title', 'price', 'created_at', 'updated_at','location', 'status', 'views', 'is_featured'];
        $sortField = 'created_at';
        $sortDirection = 'desc';

        if ($request->has('sort') && !empty($request->sort)) {
            $sort = $request->sort;
            if (str_starts_with($sort, '-')){
                $sortField = substr($sort, 1);
                $sortDirection = 'desc';
            } else {
                $sortField = $sort;
                $sortDirection = 'asc';
            }
        }
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
            $sortDirection = 'desc';
        }
        $posts = $query->orderBy($sortField, $sortDirection)->paginate($request->get('per_page'));
             
           

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
        $validated = $request->validated();

        $user = $request->user();

        $exist = Payment::where('user_id', $user->id)->exists();

        $isSubscribed = Payment::where('user_id', $user->id)
            ->where('status', 'successful')
            ->where('expires_at', '>', now())
            ->exists();

        if (!$exist && !$isSubscribed) {

            $count = AiChatBot::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            if ($count >= 110) {
                return response()->json([
                    'message' => 'You have reached your daily free limit. Please subscribe.'
                ], 422);
            }
        }

        $paths = [];

        if ($request->hasFile('images')) {

            $files = $request->file('images');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $image) {
                $paths[] = $image->store('ChatImages', 'public');
            }
        }

        $history = AiChatBot::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get()
            ->reverse();

        if ($request->hasFile('images') && !empty($validated['user_prompt'])) {

            $response = $this->groqAiService->getChatImagePrompt(
                $request->file('images'),
                $validated['user_prompt'],
                $history
            );

        } elseif ($request->hasFile('images')) {

            $response = $this->groqAiService->getChatImage(
                $request->file('images'),
            );

        } elseif (!empty($validated['user_prompt'])) {

            $response = $this->groqAiService->getChatPrompt(
                $validated['user_prompt'],
                $history
            );

        } else {

            return response()->json([
                'message' => 'Please provide a prompt or an image.'
            ], 422);

        }

        $chat = AiChatBot::create([
            'user_id' => $user->id,
            'user_prompt' => $validated['user_prompt'] ?? null,
            'ai_response' => $response,
            'image_path' => $paths,
        ]);

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
        $query = $chat->where('user_id', $request->user()->id);


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
        abort_if(Auth::id() != $chat->user_id, 403, 'Access Forbidden');

        $validated = $request->validated();

        $data = [];

        if (isset($validated['user_prompt'])) {
            $data['user_prompt'] = $validated['user_prompt'];
        }

        if ($request->hasFile('images')) {

            $files = $request->file('images');

            if (!is_array($files)) {
                $files = [$files];
            }

            $paths = [];

            foreach ($files as $image) {
                $paths[] = $image->store('ChatImages', 'public');
            }

            $data['image_path'] = $paths;
        }

        if ($request->hasFile('images') && !empty($validated['user_prompt'])) {

            $response = $this->groqAiService->getChatImagePrompt(
                $request->file('images')[0],
                $validated['user_prompt']
            );

        } elseif ($request->hasFile('images')) {

            $response = $this->groqAiService->getChatImage(
                $request->file('images')
            );

        } elseif (!empty($validated['user_prompt'])) {

            $response = $this->groqAiService->getChatPrompt(
                $validated['user_prompt']
            );

        } else {

            return response()->json([
                'message' => 'Nothing to update.'
            ], 422);
        }

        $data['ai_response'] = $response;

        $chat->update($data);

        return response()->json(new AiChatBotResource($chat));
    }

    /**
     * Delete a chat conversation.
     *
     * Permanently removes a chat conversation. Only the owner can delete their own chats.
     *
     * @param AiChatBot $chat The chat conversation instance to delete (auto-resolved via route-model binding).
     */
    function destroy(AiChatBot $chat){  
        abort_if(Auth::id() != $chat->user_id, 403, 'Access Forbidden');
        $chat->delete();
        return response()->json(['message' => 'AiChatBot deleted successfully.'], 200);
    }
}