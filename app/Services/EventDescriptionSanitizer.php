<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class EventDescriptionSanitizer
{
    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->withMaxInputLength(100_000);

        return (new HtmlSanitizer($config))->sanitize($html);
    }
}
