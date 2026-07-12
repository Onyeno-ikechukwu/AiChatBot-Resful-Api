<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\AiChatBot;
use App\Models\Payment;
use App\Service\GroqAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiChatBotRequest extends FormRequest
{
    protected GroqAiService $groqAiService;

     public function __construct(GroqAiService $groqAiService){
        $this->groqAiService = $groqAiService;
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('get')) {
            return [];
        }
        
        return [
            'user_prompt' => 'string',
            'images.*' => 'image|mimes:jpg,jpeg,png,gif,svg|max:1024',
        ];
    }
    public function storeValidationRule($request, $paths){

        
        $userId = $request->user()->id;
        $validated = $request->validated();
        $exist = Payment::where('user_id', $userId)->exists();
        $isSubscribed = Payment::where('user_id', $userId)
            ->where('status', 'successful')
            ->where('expires_at', '>', now())
            ->exists();

        if (!$exist && !$isSubscribed) {

            $count = AiChatBot::where('user_id', $userId)
                ->whereDate('created_at', today())
                ->count();

            if ($count >= 110) {
                return response()->json([
                    'message' => 'You have reached your daily free limit. Please subscribe.'
                ], 422);
            }
        }

        if ($request->hasFile('images')) {

            $files = $request->file('images');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $image) {
                $paths[] = $image->store('ChatImages', 'public');
            }
        }

        if ($request->hasFile('images') && !empty($validated['user_prompt'])) {
            
        $response = $this->groqAiService->getChatImagePrompt($request->file('images'), $validated['user_prompt'], $userId);

        } elseif ($request->hasFile('images')) {

            $response = $this->groqAiService->getChatImage($request->file('images'), $userId);

        } elseif (!empty($validated['user_prompt'])) {

            $response = $this->groqAiService->getChatPrompt($validated['user_prompt'],$userId);  
        } else {

            $response = 'You must provide a prompt or an image.';

        }

        $chat = AiChatBot::create([
            'user_id' => $userId,
            'user_prompt' => $validated['user_prompt'] ?? null,
            'ai_response' => $response,
            'image_path' => $paths,
        ]);

        return $chat;
        
    }
    public function indexValidationRule($request){
        $user = $request->user();
        $query = AiChatBot::where('user_id', $user->user_id);

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
        return $query->orderBy($sortField, $sortDirection)->paginate($request->get('per_page'));
    }

    public function updateValidationRule($request,$chat, $paths){

        abort_if(Auth::id() != $chat->user_id, 403, 'Access Forbidden');

        $validated = $request->validated();
        $userId = $request->user()->id;

        $data = [];

        if (isset($validated['user_prompt'])) {
            $data['user_prompt'] = $validated['user_prompt'];
        }

        if ($request->hasFile('images')) {

            $files = $request->file('images');

            if (!is_array($files)) {
                $files = [$files];
            }


            foreach ($files as $image) {
                $paths[] = $image->store('ChatImages', 'public');
            }

        }

        if ($request->hasFile('images') && !empty($validated['user_prompt'])) {
            
            $response = $this->groqAiService->getChatImagePrompt($request->file('images'), $validated['user_prompt'], $userId);

        } elseif ($request->hasFile('images')) {

            $response = $this->groqAiService->getChatImage($request->file('images'), $userId);

        } elseif (!empty($validated['user_prompt'])) {

            $response = $this->groqAiService->getChatPrompt($validated['user_prompt'],$userId);  

        } else {

            return response()->json([
                'message' => 'Nothing to update.'
            ], 422);
        }

        $chat->update([
            'user_id' => $userId,
            'user_prompt' => $validated['user_prompt'] ?? null,
            'ai_response' => $response,
            'image_path' => $paths,
        ]);

        return $chat;
    }
    public function destroyValidationRule($chat){
        abort_if(Auth::id() != $chat->user_id, 403, 'Access Forbidden');
        $chat->delete();
        
    }
}
