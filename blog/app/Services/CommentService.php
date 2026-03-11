<?php

namespace App\Services;

use App\DTOs\CreateCommentDTO;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository
    ) {}

    public function getCommentsForPost(int $postId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->commentRepository->findByPost($postId, $perPage);
    }

    public function getThreadedComments(int $postId): Collection
    {
        return $this->commentRepository->findRootComments($postId);
    }

    public function createComment(CreateCommentDTO $dto): Comment
    {
        // Validar que el post exista y acepte comentarios
        $post = Post::findOrFail($dto->postId);

        if (!$post->allow_comments) {
            throw new \RuntimeException("Este post no acepta comentarios.");
        }

        if ($dto->parentId) {
            $parent = Comment::find($dto->parentId);
            if (!$parent || $parent->post_id !== $dto->postId) {
                throw new \InvalidArgumentException("Comentario padre inválido.");
            }
        }

        return $this->commentRepository->create($dto->toArray());
    }

    public function updateComment(Comment $comment, User $user, string $body): Comment
    {
        // PREGUNTA: ¿Esta autorización debería estar aquí, en un Policy, o en el Controller?
        // ¿Cuál es la diferencia entre un Gate y un Policy en Laravel?
        if ($comment->user_id !== $user->id && !$user->isAdmin()) {
            throw new \RuntimeException("No tienes permiso para editar este comentario.");
        }

        return $this->commentRepository->update($comment, ['body' => $body]);
    }

    public function deleteComment(Comment $comment, User $user): bool
    {
        if ($comment->user_id !== $user->id && !$user->isAdmin()) {
            throw new \RuntimeException("No tienes permiso para eliminar este comentario.");
        }

        return $this->commentRepository->delete($comment);
    }

    public function countForPost(int $postId): int
    {
        return $this->commentRepository->countForPost($postId);
    }
}
