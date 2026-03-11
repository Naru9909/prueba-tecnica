<?php

namespace App\Repositories\Eloquent;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        private readonly Comment $model
    ) {}

    public function findByPost(int $postId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->with(['user', 'replies.user'])
            ->where('post_id', $postId)
            ->approved()
            ->root()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findRootComments(int $postId): Collection
    {
        return $this->model
            ->with(['user', 'replies' => fn($q) => $q->with('user')->approved()])
            ->where('post_id', $postId)
            ->approved()
            ->root()
            ->get();
    }

    public function findReplies(int $commentId): Collection
    {
        return $this->model
            ->with('user')
            ->where('parent_id', $commentId)
            ->approved()
            ->get();
    }

    public function create(array $data): Comment
    {
        return $this->model->create($data);
    }

    public function update(Comment $comment, array $data): Comment
    {
        $comment->update(array_merge($data, [
            'is_edited' => true,
            'edited_at' => now(),
        ]));
        return $comment->fresh();
    }

    public function delete(Comment $comment): bool
    {
        return (bool) $comment->delete();
    }

    public function approve(Comment $comment): Comment
    {
        $comment->update(['status' => 'approved']);
        return $comment;
    }

    public function markAsSpam(Comment $comment): Comment
    {
        $comment->update(['status' => 'spam']);
        return $comment;
    }

    public function countForPost(int $postId): int
    {
        return $this->model
            ->where('post_id', $postId)
            ->approved()
            ->count();
    }
}
