<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
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


    public function categories(): JsonResponse
    {   
        try{
            $categories = $this->categoryService->getActiveCategories();
            
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

        if (!$token || !$origin) {
            return response()->json(['error' => 'Missing authorization credentials context.'], 401);
        }

        $isAuthorized = $this->domainManager->verify($token, $origin);

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized embed code environment connection.'], 403);
        }

        $userInput = $request->input('chatInput');
        $sessionId = $request->input('sessionId');

        try {
            $answer = $this->pipelineService->generate($userInput, $sessionId);
            
            return response()->json([
                'answer' => $answer, 
                'question' => $userInput
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
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