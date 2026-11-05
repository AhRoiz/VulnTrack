<?php

declare(strict_types=1);

final class ForumService
{
    private const CATEGORIES = [
        'general-updates' => [
            'slug'        => 'general-updates',
            'name'        => 'General Updates',
            'icon'        => 'bi-megaphone',
            'description' => 'Pengumuman rilis, jadwal deployment staging, dan koordinasi umum lintas peran.',
        ],
        'patching-coordination' => [
            'slug'        => 'patching-coordination',
            'name'        => 'Patching Coordination',
            'icon'        => 'bi-wrench-adjustable-circle',
            'description' => 'Sinkronisasi pull request perbaikan, konfigurasi uji, dan kesiapan re-test.',
        ],
        'sla-warnings' => [
            'slug'        => 'sla-warnings',
            'name'        => 'SLA Warnings',
            'icon'        => 'bi-alarm',
            'description' => 'Eskalasi tiket prioritas tinggi yang mendekati atau melewati tenggat SLA.',
        ],
    ];

    public function __construct(private JsonStore $store)
    {
    }

    public function categories(): array
    {
        return self::CATEGORIES;
    }

    public function findCategory(string $slug): ?array
    {
        return self::CATEGORIES[$slug] ?? null;
    }

    public function categoryCounts(): array
    {
        $threads = $this->store->collection('forum_threads');
        $counts = ['all' => count($threads)];
        foreach (self::CATEGORIES as $slug => $_) {
            $counts[$slug] = 0;
        }
        foreach ($threads as $t) {
            $cat = (string) ($t['category'] ?? '');
            if (isset($counts[$cat])) {
                $counts[$cat]++;
            }
        }
        return $counts;
    }

    public function threads(?string $category = null): array
    {
        $data = $this->store->read();
        $threads = $data['forum_threads'] ?? [];
        $replies = $data['forum_replies'] ?? [];

        $replyCounts = [];
        foreach ($replies as $r) {
            $tid = (int) ($r['thread_id'] ?? 0);
            $replyCounts[$tid] = ($replyCounts[$tid] ?? 0) + 1;
        }

        if ($category !== null && $category !== '' && isset(self::CATEGORIES[$category])) {
            $threads = array_values(array_filter(
                $threads,
                fn (array $t): bool => ($t['category'] ?? '') === $category
            ));
        }

        foreach ($threads as &$t) {
            $tid = (int) $t['id'];
            $t['reply_count'] = $replyCounts[$tid] ?? 0;
            $t['updated_at'] = $t['updated_at'] ?? $t['created_at'];
        }
        unset($t);

        usort(
            $threads,
            fn (array $a, array $b): int => strcmp((string) $b['updated_at'], (string) $a['updated_at']) ?: ((int) $b['id'] <=> (int) $a['id'])
        );

        return $threads;
    }

    public function findThread(int $id): ?array
    {
        foreach ($this->store->collection('forum_threads') as $t) {
            if ((int) $t['id'] === $id) {
                return $t;
            }
        }
        return null;
    }

    public function repliesForThread(int $threadId): array
    {
        $replies = array_values(array_filter(
            $this->store->collection('forum_replies'),
            fn (array $r): bool => (int) ($r['thread_id'] ?? 0) === $threadId
        ));

        usort(
            $replies,
            fn (array $a, array $b): int => strcmp((string) $a['created_at'], (string) $b['created_at']) ?: ((int) $a['id'] <=> (int) $b['id'])
        );

        return $replies;
    }

    public function isSubscribed(array $thread, int $userId): bool
    {
        $subs = array_map('intval', $thread['subscriber_ids'] ?? []);
        return in_array($userId, $subs, true);
    }

    /** @throws ValidationException */
    public function createThread(array $input, array $user): int
    {
        $category = trim((string) ($input['category'] ?? ''));
        $subject  = trim((string) ($input['subject'] ?? ''));
        $message  = trim((string) ($input['message'] ?? ''));
        $follow   = !empty($input['subscribe']);

        $errors = [];
        if (!isset(self::CATEGORIES[$category])) {
            $errors['category'] = 'Pilih kategori subforum yang valid.';
        }
        if (mb_strlen($subject) < 5 || mb_strlen($subject) > 150) {
            $errors['subject'] = 'Subjek topik wajib diisi (5 sampai 150 karakter).';
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 3000) {
            $errors['message'] = 'Pesan pembuka wajib diisi (10 sampai 3000 karakter).';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->store->transaction(function (array &$data) use ($category, $subject, $message, $follow, $user): int {
            $id  = JsonStore::nextId($data, 'forum_threads');
            $now = date('Y-m-d H:i:s');
            $uid = (int) $user['id'];

            $data['forum_threads'][] = [
                'id'             => $id,
                'category'       => $category,
                'user_id'        => $uid,
                'subject'        => $subject,
                'message'        => $message,
                'subscriber_ids' => $follow ? [$uid] : [],
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            return $id;
        });
    }

    /** @throws ValidationException */
    public function addReply(int $threadId, array $input, array $user): int
    {
        $message = trim((string) ($input['message'] ?? ''));

        $errors = [];
        if ($this->findThread($threadId) === null) {
            $errors['thread'] = 'Topik diskusi tidak ditemukan.';
        }
        if (mb_strlen($message) < 5 || mb_strlen($message) > 3000) {
            $errors['message'] = 'Isi balasan wajib diisi (5 sampai 3000 karakter).';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->store->transaction(function (array &$data) use ($threadId, $message, $user): int {
            $now = date('Y-m-d H:i:s');
            $replyId = JsonStore::nextId($data, 'forum_replies');

            $data['forum_replies'][] = [
                'id'         => $replyId,
                'thread_id'  => $threadId,
                'user_id'    => (int) $user['id'],
                'message'    => $message,
                'created_at' => $now,
            ];

            foreach ($data['forum_threads'] as &$thread) {
                if ((int) $thread['id'] === $threadId) {
                    $thread['updated_at'] = $now;
                    break;
                }
            }
            unset($thread);

            return $replyId;
        });
    }

    public function toggleSubscribe(int $threadId, int $userId): bool
    {
        return $this->store->transaction(function (array &$data) use ($threadId, $userId): bool {
            foreach ($data['forum_threads'] as &$thread) {
                if ((int) $thread['id'] !== $threadId) {
                    continue;
                }
                $subs = array_values(array_unique(array_map('intval', $thread['subscriber_ids'] ?? [])));
                if (in_array($userId, $subs, true)) {
                    $thread['subscriber_ids'] = array_values(array_filter($subs, fn (int $id): bool => $id !== $userId));
                    return false;
                }
                $subs[] = $userId;
                $thread['subscriber_ids'] = $subs;
                return true;
            }
            unset($thread);
            return false;
        });
    }
}
