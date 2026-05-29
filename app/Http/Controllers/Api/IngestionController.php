<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ingestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IngestionController extends Controller
{

    public function __construct(
        protected Ingestion $ingestionService,
    ) {}

    public function indexedPosts(): JsonResponse
    {   
        try{
            $categories = $this->ingestionService->getIndexedPostsIds();
            
            return response()->json([
                'indexed_posts_ids' => $categories
            ], 200);
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
