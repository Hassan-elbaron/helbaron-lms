<?php

use App\Platform\Shared\Html\HtmlSanitizer;

it('strips scripts, event handlers, iframes, and javascript: URLs while keeping safe markup', function () {
    $sanitizer = new HtmlSanitizer;

    $dirty = '<p>Intro <strong>bold</strong></p>'
        .'<script>alert(1)</script>'
        .'<a href="javascript:alert(1)" onclick="window.pwn=1">bad link</a>'
        .'<a href="https://example.com" title="ok">good link</a>'
        .'<img src="https://cdn.example.com/a.png" alt="ok" onerror="pwn()">'
        .'<iframe src="https://evil.example"></iframe>'
        .'<style>body{display:none}</style>';

    $clean = $sanitizer->sanitize($dirty);

    expect($clean)
        ->toContain('<strong>bold</strong>')
        ->toContain('https://example.com')
        ->toContain('<img src="https://cdn.example.com/a.png"')
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        ->not->toContain('<iframe')
        ->not->toContain('<style');
});

it('sanitizes only html-bearing keys inside nested content arrays', function () {
    $sanitizer = new HtmlSanitizer;

    $content = [
        'type' => 'article',
        'title' => '<script>not html field, left as-is</script>',
        'blocks' => [
            ['html' => '<p>ok</p><script>alert(1)</script>'],
            ['body' => '<em>fine</em><img src="x" onerror="pwn()">'],
        ],
    ];

    $clean = $sanitizer->sanitizeArray($content);

    expect($clean['title'])->toBe('<script>not html field, left as-is</script>')
        ->and($clean['blocks'][0]['html'])->toContain('<p>ok</p>')
        ->and($clean['blocks'][0]['html'])->not->toContain('<script')
        ->and($clean['blocks'][1]['body'])->toContain('<em>fine</em>')
        ->and($clean['blocks'][1]['body'])->not->toContain('onerror');
});

it('forces rel=noopener on links opening in a new context', function () {
    $clean = (new HtmlSanitizer)->sanitize('<a href="https://example.com" target="_blank">x</a>');

    expect($clean)->toContain('noopener');
});
