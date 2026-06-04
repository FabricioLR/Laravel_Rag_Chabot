<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Http\Requests\FeedbackRequest;
use App\Services\AnswerGeneration;
use App\Services\PostCategories;
use App\Services\ConversationHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\DomainManager;
use Exception;

class ChatController extends Controller
{

    public function __construct(
        protected AnswerGeneration $pipelineService,
        protected PostCategories $categoryService,
        protected DomainManager $domainManager,
        protected ConversationHistory $historyService
    ) {}


    public function categories(Request $request): JsonResponse
    {   
        $parent = $request->query('parent', 0);

        if (!is_numeric($parent)) {
            return response()->json([
                'error' => "Invalid parameter. 'parent' must be a number."
            ], 500);
        }

        try{
            $categories = $this->categoryService->getActiveCategories($parent);
            
            return response()->json([
                'categories' => $categories
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history(Request $request, string $sessionId): JsonResponse
    {
        $token = $request->header('X-Client-Token') ?? $request->input('token');
        $origin = $request->header('Origin');

        if (!$origin){
            $origin = $request->header('Referer');
        }

        if (!$token || !$origin) {
            return response()->json(['error' => 'Missing authorization credentials context.'], 401);
        }

        $isAuthorized = $this->domainManager->verify($token, $origin);

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized embed code environment connection.'], 403);
        }

        try {
            $messages = $this->historyService->getMessagesForWidget($sessionId);

            return response()->json([
                'messages' => $messages
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function chat(ChatRequest $request): JsonResponse
    {
        $token = $request->header('X-Client-Token') ?? $request->input('token');
        $origin = $request->header('Origin');
        
        if (!$origin){
            $origin = $request->header('Referer');
        }

        if (!$token || !$origin) {
            return response()->json(['error' => 'Missing authorization credentials context.'], 401);
        }

        $isAuthorized = $this->domainManager->verify($token, $origin);

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized embed code environment connection.'], 403);
        }

        $userInput = $request->input('chatInput');
        $sessionId = $request->input('sessionId');
        $mainCategory = $request->input('mainCategory');
        $childCategory = $request->input('childCategory');

        if ($mainCategory && in_array(strtolower($mainCategory), ['general', 'geral'])) {
            $mainCategory = null;
        }

        if ($childCategory && in_array(strtolower($childCategory), ['general', 'geral'])) {
            $childCategory = null;
        }

        try {
            $result = $this->pipelineService->generate($userInput, $sessionId, $mainCategory, $childCategory);
            //$answer = "testando...";
            return response()->json([
                'conversationId' => $result['conversationId'],
                'answer' => $result['answer'],
                'question' => $userInput
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function feedback(FeedbackRequest $request): JsonResponse
    {
        $token = $request->header('X-Client-Token') ?? $request->input('token');
        $origin = $request->header('Origin') ?? $request->header('Referer');

        if (!$token || !$origin) {
            return response()->json(['error' => 'Missing authorization credentials context.'], 401);
        }

        $isAuthorized = $this->domainManager->verify($token, $origin);

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized embed code environment connection.'], 403);
        }

        $conversationId = $request->input('conversationId');
        $feedbackValue = $request->input('rating');

        if (!$conversationId || !$feedbackValue) {
            return response()->json(['error' => 'Missing required body parameters (conversationId, rating).'], 422);
        }

        try {
            $updated = $this->historyService->updateFeedback($conversationId, $feedbackValue);

            if (!$updated) {
                return response()->json([
                    'error' => 'No matching conversation record found to update feedback for this session.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Feedback successfully recorded.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred while saving your feedback.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}