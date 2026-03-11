<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $dto = CreateCommentDTO::fromRequest(
            array_merge($request->validated(), ['post_id' => $post->id]),
            $request->user()->id,
            $request->ip()
        );

        $comment = $this->commentService->createComment($dto);

        return (new CommentResource($comment->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Comment $comment): CommentResource
    {
        $request->validate(['body' => ['required', 'string', 'min:1', 'max:2000']]);

        $updated = $this->commentService->updateComment(
            $comment,
            $request->user(),
            $request->input('body')
        );

        return new CommentResource($updated->load('user'));
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->commentService->deleteComment($comment, $request->user());

        return response()->json(null, 204);
    }
}
