<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatBotRequest;
use App\Http\Resources\AiChatBotResource;
use App\Models\AiChatBot;
use Illuminate\Http\Request;
use App\Service\GroqAiService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UnRegisteredAiChatBotResource;

class UnRegisteredAiChatBotController extends Controller
{
    protected GroqAiService $groqAiService;

     public function __construct(GroqAiService $groqAiService){
        $this->groqAiService = $groqAiService;
    }

    /**
     * List unregistered user's AI chat conversations (by IP address).
     *
     * Returns a paginated list of chat conversations associated with the visitor's IP address.
     * Supports searching by title/description and sorting by various fields.
     * Prefix sort field with "-" for descending order (e.g. "-created_at").
     */
    Public function index(Request $request){
        

        $user = $request->ip();

        $query = AiChatBot::where('ip_address', $user);

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
             
          

        return response()->json(UnRegisteredAiChatBotResource::collection($posts));
    }

    /**
     * Create a new AI chat conversation for an unregistered user.
     *
     * Sends the visitor's text prompt and/or images to the Groq AI service, then stores the conversation.
     * Unregistered users are limited to 10 total chat conversations per IP address.
     * Requires either a "user_prompt" text or "images" file, or both.
     */
    public function store(AiChatBotRequest $request)
    {
        $validated = $request->validated();

        $user = $request->ip();

        $query = AiChatBot::where('ip_address', $request->ip())->count();

        if ($query > 10) {
            return response()->json([    
            'message' => 'You have reached the maximum number of requests allowed for you. Please create an account to continue using the service.'
            ], 422);
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

        $history = AiChatBot::where('ip_address', $user)
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
            'user_id' => $user,
            'user_prompt' => $validated['user_prompt'] ?? null,
            'ai_response' => $response,
            'image_path' => $paths,
        ]);

        return response()->json(new UnRegisteredAiChatBotResource($chat));
    }

    /**
     * Get a specific chat conversation for an unregistered user.
     *
     * Returns the details of a single chat conversation associated with the visitor's IP address.
     *
     * @param AiChatBot $chat The chat conversation instance (auto-resolved via route-model binding).
     */
    public function show(Request $request, AiChatBot $chat)
    {
        $query = $chat->where('ip_address', $request->ip());

        return response()->json(new AiChatBotResource($query));
        
        
    }

    /**
     * Update an existing chat conversation for an unregistered user.
     *
     * Updates the user prompt and/or images of a chat, then re-generates the AI response.
     * Only the IP address that created the chat can update it.
     *
     * @param AiChatBot $chat The chat conversation instance to update (auto-resolved via route-model binding).
     */
    public function update(AiChatBot $chat, AiChatBotRequest $request)
    {
        abort_if($request ->ip() != $chat->user_id, 403, 'Access Forbidden');
        
        
        $validated = $request->validated();


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
     * Delete a chat conversation for an unregistered user.
     *
     * Permanently removes a chat conversation. Only the IP address that created the chat can delete it.
     *
     * @param AiChatBot $chat The chat conversation instance to delete (auto-resolved via route-model binding).
     */
    function destroy(Request $request, AiChatBot $chat){
        abort_if($request ->ip() != $chat->ip_address, 403, 'Access Forbidden');
        $chat->delete();
        return response()->json(['message' => 'AiChatBot deleted successfully.'], 200);
    }
}
