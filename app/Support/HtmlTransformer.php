<?php

namespace App\Support;

class HtmlTransformer
{
    public function __construct(
        public string $html,
    ) {
        //
    }

    public function formatted(): string
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true); // suppress warnings

        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$this->html);

        $tagReplacements = [
            'h1' => fn ($node, $html) => "<h1 class=\"text-2xl font-bold mb-3\">{$html}</h1>",
            'h2' => fn ($node, $html) => "<h2 class=\"text-xl font-semibold mb-3\">{$html}</h2>",
            'h3' => fn ($node, $html) => "<h3 class=\"text-lg font-semibold mb-2\">{$html}</h3>",
            'p' => fn ($node, $html) => "<p class=\"text-sm mb-4\">{$html}</p>",
            'ul' => fn ($node, $html) => "<ul class=\"list-disc pl-5 mb-4\">{$html}</ul>",
            'ol' => fn ($node, $html) => "<ol class=\"list-decimal pl-5 mb-4\">{$html}</ol>",
            'li' => fn ($node, $html) => "<li class=\"mb-2\">{$html}</li>",
            'a' => fn ($node, $html) => "<a class=\"inline font-medium underline-offset-[6px] hover:decoration-current underline [[data-color]>&]:text-inherit [[data-color]>&]:decoration-current/20 dark:[[data-color]>&]:decoration-current/50 [[data-color]>&]:hover:decoration-current text-[var(--color-accent-content)] decoration-[color-mix(in_oklab,var(--color-accent-content),transparent_80%)]\" href=\"{$node->getAttribute('href')}\" target=\"_blank\" data-flux-link=\"\">{$html}</a>",
            'blockquote' => fn ($node, $html) => "<blockquote class=\"border-l-4 pl-4 italic text-gray-600 mb-4\">{$html}</blockquote>",
        ];

        $body = $dom->getElementsByTagName('body')->item(0);
        $output = '';

        foreach ($body->childNodes as $node) {
            $output .= self::transformNode($node, $tagReplacements);
        }

        return $output;
    }

    private static function transformNode($node, $tagReplacements)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue);
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        $tag = strtolower($node->nodeName);

        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= self::transformNode($child, $tagReplacements);
        }

        if (array_key_exists($tag, $tagReplacements)) {
            return $tagReplacements[$tag]($node, $html);
        }

        // No replacement, output original tag wrapping the children
        return "<{$tag}>{$html}</{$tag}>";
    }
}
