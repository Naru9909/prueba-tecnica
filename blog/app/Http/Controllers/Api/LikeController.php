<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(
        private readonly LikeService $likeService
    ) {}

    public function togglePost(Request $request, Post $post): JsonResponse
    {
        $result = $this->likeService->toggle($request->user(), $post);
        return response()->json($result);
    }

    public function toggleComment(Request $request, Comment $comment): JsonResponse
    {
        $result = $this->likeService->toggle($request->user(), $comment);
        return response()->json($result);
    }
}
