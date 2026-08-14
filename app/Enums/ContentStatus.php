<?php

namespace App\Enums;

/**
 * The only three states any content_items row may be in. Deliberately kept
 * to exactly these three — no Scheduled, no Archived — per product
 * decision. Draft = never gone live (no published_at). Published = live.
 * Unpublished = was live before and has been taken down; unlike draft, its
 * original published_at is preserved as a historical record.
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Published => 'منشور',
            self::Unpublished => 'غير منشور',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
