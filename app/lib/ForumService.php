<?php

declare(strict_types=1);

final class ForumService
{
    public function __construct(private JsonStore $store)
    {
    }

    public function all(): array
    {
        $posts = $this->store->collection('forum_posts');
        usort($posts, fn (array $a, array $b): int => strcmp($b['created_at'], $a['created_at']) ?: ((int) $b['id'] <=> (int) $a['id']));
        return $posts;
    }

    /** @throws ValidationException */
    public function create(array $input, array $user): int
    {
        $subject = trim((string) ($input['subject'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));

        $errors = [];
        if (mb_strlen($subject) < 5 || mb_strlen($subject) > 150) {
            $errors['subject'] = 'Subjek diskusi wajib diisi (5 sampai 150 karakter).';
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
            $errors['message'] = 'Isi pesan wajib diisi (10 sampai 2000 karakter).';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->store->transaction(function (array &$data) use ($subject, $message, $user): int {
            $id = JsonStore::nextId($data, 'forum_posts');
            $data['forum_posts'][] = [
                'id'         => $id,
                'user_id'    => (int) $user['id'],
                'subject'    => $subject,
                'message'    => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            return $id;
        });
    }
}

