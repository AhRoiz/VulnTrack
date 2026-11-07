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

    public function __construct(private Database $db)
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
        $counts = ['all' => 0];
        foreach (self::CATEGORIES as $slug => $_) {
            $counts[$slug] = 0;
        }

        $rows = $this->db->fetchAll(
            'SELECT category, COUNT(*) AS total
             FROM forum_posts
             WHERE parent_id IS NULL
             GROUP BY category'
        );

        foreach ($rows as $row) {
            $cat = (string) ($row['category'] ?? '');
            $cnt = (int) $row['total'];
            if (isset($counts[$cat])) {
                $counts[$cat] = $cnt;
                $counts['all'] += $cnt;
            }
        }

        return $counts;
    }

    public function threads(?string $category = null): array
    {
        $params = [];
        $where  = 'p.parent_id IS NULL';

        if ($category !== null && $category !== '' && isset(self::CATEGORIES[$category])) {
            $where .= ' AND p.category = :category';
            $params['category'] = $category;
        }

        $rows = $this->db->fetchAll(
            "SELECT p.id, p.user_id, p.category, p.subject, p.message, p.created_at, p.updated_at,
                    COUNT(r.id) AS reply_count
             FROM forum_posts p
             LEFT JOIN forum_posts r ON r.parent_id = p.id
             WHERE {$where}
             GROUP BY p.id, p.user_id, p.category, p.subject, p.message, p.created_at, p.updated_at
             ORDER BY p.updated_at DESC, p.id DESC",
            $params
        );

        if (!$rows) {
            return [];
        }

        $subsByPost = $this->subscriptionsMap();
        return array_map(
            static fn (array $row): array => self::normalizeThread($row, $subsByPost[(int) $row['id']] ?? []),
            $rows
        );
    }

    public function findThread(int $id): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT p.id, p.user_id, p.category, p.subject, p.message, p.created_at, p.updated_at,
                    COUNT(r.id) AS reply_count
             FROM forum_posts p
             LEFT JOIN forum_posts r ON r.parent_id = p.id
             WHERE p.id = :id AND p.parent_id IS NULL
             GROUP BY p.id, p.user_id, p.category, p.subject, p.message, p.created_at, p.updated_at
             LIMIT 1',
            ['id' => $id]
        );

        if ($row === null) {
            return null;
        }

        $subRows = $this->db->fetchAll(
            'SELECT user_id FROM forum_subscriptions WHERE post_id = :pid',
            ['pid' => $id]
        );
        $subs = array_map(static fn (array $s): int => (int) $s['user_id'], $subRows);

        return self::normalizeThread($row, $subs);
    }

    public function repliesForThread(int $threadId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT id, parent_id AS thread_id, user_id, message, created_at
             FROM forum_posts
             WHERE parent_id = :tid
             ORDER BY created_at ASC, id ASC',
            ['tid' => $threadId]
        );

        return array_map(static fn (array $r): array => [
            'id'         => (int) $r['id'],
            'thread_id'  => (int) $r['thread_id'],
            'user_id'    => (int) $r['user_id'],
            'message'    => (string) $r['message'],
            'created_at' => (string) $r['created_at'],
        ], $rows);
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

        return $this->db->transaction(function (Database $db) use ($category, $subject, $message, $follow, $user): int {
            $now = date('Y-m-d H:i:s');
            $uid = (int) $user['id'];

            $id = $db->insert(
                'INSERT INTO forum_posts (parent_id, user_id, category, subject, message, created_at, updated_at)
                 VALUES (NULL, :uid, :category, :subject, :message, :created_at, :updated_at)',
                [
                    'uid'        => $uid,
                    'category'   => $category,
                    'subject'    => $subject,
                    'message'    => $message,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            if ($follow) {
                $db->execute(
                    'INSERT INTO forum_subscriptions (post_id, user_id, created_at) VALUES (:pid, :uid, :created_at)',
                    ['pid' => $id, 'uid' => $uid, 'created_at' => $now]
                );
            }

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

        return $this->db->transaction(function (Database $db) use ($threadId, $message, $user): int {
            $now = date('Y-m-d H:i:s');
            $uid = (int) $user['id'];

            $replyId = $db->insert(
                'INSERT INTO forum_posts (parent_id, user_id, category, subject, message, created_at, updated_at)
                 VALUES (:parent_id, :uid, NULL, NULL, :message, :created_at, :updated_at)',
                [
                    'parent_id'  => $threadId,
                    'uid'        => $uid,
                    'message'    => $message,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $db->execute(
                'UPDATE forum_posts SET updated_at = :updated_at WHERE id = :id AND parent_id IS NULL',
                ['updated_at' => $now, 'id' => $threadId]
            );

            return $replyId;
        });
    }

    public function toggleSubscribe(int $threadId, int $userId): bool
    {
        return $this->db->transaction(function (Database $db) use ($threadId, $userId): bool {
            $exists = $db->fetchValue(
                'SELECT 1 FROM forum_subscriptions WHERE post_id = :pid AND user_id = :uid LIMIT 1',
                ['pid' => $threadId, 'uid' => $userId]
            );

            if ($exists !== null) {
                $db->execute(
                    'DELETE FROM forum_subscriptions WHERE post_id = :pid AND user_id = :uid',
                    ['pid' => $threadId, 'uid' => $userId]
                );
                return false;
            }

            $db->execute(
                'INSERT INTO forum_subscriptions (post_id, user_id, created_at) VALUES (:pid, :uid, :created_at)',
                ['pid' => $threadId, 'uid' => $userId, 'created_at' => date('Y-m-d H:i:s')]
            );
            return true;
        });
    }

    /** @return array<int, list<int>> */
    private function subscriptionsMap(): array
    {
        $rows = $this->db->fetchAll('SELECT post_id, user_id FROM forum_subscriptions');
        $map  = [];
        foreach ($rows as $row) {
            $pid = (int) $row['post_id'];
            $map[$pid][] = (int) $row['user_id'];
        }
        return $map;
    }

    /** @param list<int> $subscriberIds */
    private static function normalizeThread(array $row, array $subscriberIds): array
    {
        return [
            'id'             => (int) $row['id'],
            'category'       => (string) $row['category'],
            'user_id'        => (int) $row['user_id'],
            'subject'        => (string) $row['subject'],
            'message'        => (string) $row['message'],
            'subscriber_ids' => $subscriberIds,
            'reply_count'    => (int) ($row['reply_count'] ?? 0),
            'created_at'     => (string) $row['created_at'],
            'updated_at'     => (string) ($row['updated_at'] ?? $row['created_at']),
        ];
    }
}
