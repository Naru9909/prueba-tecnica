<?php

namespace App\Repositories\Contracts;

use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CommentRepositoryInterface
{
    public function findByPost(int $postId, int $perPage = 20): LengthAwarePaginator;

    public function findRootComments(int $postId): Collection;

    public function findReplies(int $commentId): Collection;

    public function create(array $data): Comment;

    public function update(Comment $comment, array $data): Comment;

    public function delete(Comment $comment): bool;

    public function approve(Comment $comment): Comment;

    public function markAsSpam(Comment $comment): Comment;

    public function countForPost(int $postId): int;
}
