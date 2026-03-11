<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bookmarks = $request->user()
            ->bookmarks()
            ->with(['author', 'category'])
            ->published()
            ->paginate(15);

        return response()->json(PostResource::collection($bookmarks));
    }

    public function toggle(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($user->bookmarks()->where('post_id', $post->id)->exists()) {
            $user->bookmarks()->detach($post->id);
            return response()->json(['bookmarked' => false]);
        }

        $user->bookmarks()->attach($post->id);
        return response()->json(['bookmarked' => true]);
    }
}
