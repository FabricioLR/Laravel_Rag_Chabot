<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Services\AnswerGeneration;
use App\Services\PostCategories;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
class ChatController extends Controller
{

    public function __construct(
        protected AnswerGeneration $pipelineService,
        protected PostCategories $categoryService
    ) {}


    public function categories(): JsonResponse
    {
        $categories = $this->categoryService->getActiveCategories();
        
        return response()->json([
            'categories' => $categories
        ], 200);
    }

    public function chat(ChatRequest $request): JsonResponse
    {
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