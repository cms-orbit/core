<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support;

use League\HTMLToMarkdown\HtmlConverter;

/**
 * Converts stored RichText HTML into Markdown for exports and APIs.
 */
class RichTextConverter
{
    /**
     * Convert HTML produced by the RichText field into Markdown.
     */
    public static function toMarkdown(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
        ]);

        return trim($converter->convert($html));
    }
}
