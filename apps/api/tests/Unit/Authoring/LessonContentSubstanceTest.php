<?php

use App\Domains\Authoring\Services\CourseReadinessService;
use App\Platform\Shared\Assessment\Contracts\LessonAssessmentPort;
use App\Platform\Shared\Assessment\Data\AssessmentRef;

/**
 * The payload-substance predicate behind the `lesson.empty_content` blocker.
 *
 * Driven through reflection deliberately. hasMeaningfulContent() is private and should stay that
 * way — it is an implementation detail of one rule, not API — but it is also the exact function
 * whose behaviour blocked course publication, and its input space (strings, nested arrays, mixed
 * scalars) is far too wide to cover economically by building a course per case. The feature-level
 * consequences are asserted separately in CourseReadinessTest.
 */
function substance(mixed $value): bool
{
    $service = new CourseReadinessService(
        new class implements LessonAssessmentPort
        {
            // Never consulted: hasMeaningfulContent() does not touch assessments. The port only has
            // to exist so the service can be constructed without the container.
            public function describe(int $assessmentId): ?AssessmentRef
            {
                return null;
            }

            /**
             * @param  list<int>  $assessmentIds
             * @return array<int, AssessmentRef>
             */
            public function describeMany(array $assessmentIds): array
            {
                return [];
            }

            public function resolveAttachable(string $assessmentPublicId, int $courseId): ?AssessmentRef
            {
                return null;
            }
        }
    );

    $method = new ReflectionMethod($service, 'hasMeaningfulContent');
    $method->setAccessible(true);

    return (bool) $method->invoke($service, $value);
}

it('counts plain readable text', function (): void {
    expect(substance('Welcome to the lesson.'))->toBeTrue()
        ->and(substance('<p>Welcome to the lesson.</p>'))->toBeTrue();
});

/*
 * The regression this rule existed to cause.
 *
 * strip_tags() reduces an embed-only lesson to '', so the text-only test called it empty — and once
 * the check became a Blocker it refused to publish the most common authoring shape in this product.
 */
it('counts an iframe-only body as content', function (): void {
    expect(substance('<iframe src="https://player.example/abc" allowfullscreen></iframe>'))->toBeTrue();
});

it('counts an img-only body as content', function (): void {
    expect(substance('<img src="/media/diagram.png" alt="">'))->toBeTrue();
});

it('counts video, audio, embed, object and source elements as content', function (string $html): void {
    expect(substance($html))->toBeTrue();
})->with([
    '<video controls src="/m/v.mp4"></video>',
    '<audio controls src="/m/a.mp3"></audio>',
    '<embed src="/m/f.pdf">',
    '<object data="/m/f.pdf"></object>',
    '<video><source src="/m/v.webm"></video>',
    '<IFRAME SRC="x"></IFRAME>',
]);

it('counts a structured media reference with no string leaf at all', function (): void {
    // The payload shape the old recursion called empty: it counted string leaves only, so a block
    // that points at a media row by id looked like nothing.
    expect(substance(['embed' => ['media_id' => 12]]))->toBeTrue()
        ->and(substance(['blocks' => [['image' => ['asset_id' => 3]]]]))->toBeTrue()
        ->and(substance(['video' => ['url' => 'https://player.example/abc']]))->toBeTrue();
});

it('treats a metadata-only payload as empty', function (): void {
    // A block an author created and never filled in. "text" is a non-empty string, so the old
    // recursion counted it as substance and let a hollow lesson publish.
    expect(substance(['type' => 'text']))->toBeFalse()
        ->and(substance(['type' => 'video', 'align' => 'center', 'width' => '640']))->toBeFalse();
});

it('treats a genuinely empty payload as empty', function (mixed $value): void {
    expect(substance($value))->toBeFalse();
})->with([
    // Each entry is wrapped in its own array: Pest SPREADS a dataset row into arguments, so a bare
    // [] would arrive as zero arguments rather than as the empty-array value under test.
    'null' => [null],
    'empty string' => [''],
    'whitespace only' => ['   '],
    'empty paragraph' => ['<p></p>'],
    'nested empty markup' => ['<div><span>  </span></div>'],
    'empty array' => [[]],
    'array of empties' => [[[], ['' => '']]],
    'blank html and text keys' => [['html' => '', 'text' => null]],
]);

/*
 * A11.2 — paired on purpose.
 *
 * The negative half alone was a test that could not fail: `<imgur></imgur>` strips to '' so the
 * pre-fix, text-only code returned false too. It survived deleting the entire embed regex. Asserting
 * the positive and negative together means the test dies if the regex goes away AND if the word
 * boundary goes away.
 */
it('matches a real embed tag but not a word that merely starts with one', function (): void {
    expect(substance('<img src="/m/a.png">'))->toBeTrue()
        ->and(substance('<imgur></imgur>'))->toBeFalse()
        ->and(substance('<video src="/m/v.mp4"></video>'))->toBeTrue()
        ->and(substance('<videos></videos>'))->toBeFalse();
});

/*
 * A11.1 — paired on purpose.
 *
 * The negative half alone exercised nothing: under the old string-only recursion `0` is not a
 * string, `'  '` trims to '', and `null` is not a string, so all three returned false without
 * isPresentReference() existing at all. Each case now asserts the present AND absent form together,
 * so the test fails if reference handling is removed.
 */
it('counts a reference that points at something and ignores one that does not', function (): void {
    expect(substance(['media_id' => 12]))->toBeTrue()
        ->and(substance(['media_id' => 0]))->toBeFalse()
        ->and(substance(['url' => 'https://player.example/abc']))->toBeTrue()
        ->and(substance(['url' => '  ']))->toBeFalse()
        ->and(substance(['src' => '/m/a.png']))->toBeTrue()
        ->and(substance(['src' => null]))->toBeFalse();
});

it('finds substance nested arbitrarily deep', function (): void {
    expect(substance(['a' => ['b' => ['c' => ['html' => '<p>Deep</p>']]]]))->toBeTrue();
});

/*
 * -- A6: entities and Unicode whitespace ---------------------------------------------------------
 *
 * `<p>&nbsp;</p>` is what TinyMCE, CKEditor and Quill emit for an empty paragraph — THE canonical
 * empty lesson. strip_tags() leaves the entity behind and trim() strips only ASCII whitespace, so
 * the guard declared it full and let a blank lesson publish.
 */
it('treats an entity-only or Unicode-whitespace-only body as empty', function (mixed $value): void {
    expect(substance($value))->toBeFalse();
})->with([
    'nbsp entity' => ['<p>&nbsp;</p>'],
    'literal NBSP' => ["<p>\u{00A0}</p>"],
    'three nbsp entities' => ['&nbsp;&nbsp;&nbsp;'],
    'zero-width non-joiner' => ['<p>&zwnj;</p>'],
    'zero-width space (numeric)' => ['<p>&#8203;</p>'],
    'em space' => ['<p>&emsp;</p>'],
    'byte order mark' => ["\u{FEFF}"],
]);

/*
 * Decode ORDER matters, and this is the case that pins it.
 *
 * Decoding before strip_tags() would turn this into real markup, strip it to '', and refuse to
 * publish a lesson that teaches HTML — ordinary content in an LMS, and exactly the "legitimate
 * course blocked" failure this rule exists to prevent. Stripping first leaves the entities, which
 * then decode to visible text.
 */
it('counts an escaped-markup code sample as content', function (): void {
    expect(substance('<pre>&lt;div class="wrapper"&gt;&lt;/div&gt;</pre>'))->toBeTrue()
        ->and(substance('<code>&lt;img src="x"&gt;</code>'))->toBeTrue();
});

it('counts a bare escaped tag as visible text, never as an embed', function (): void {
    // "&lt;img&gt;" renders as the characters <img>, which a learner can see. The embed regex runs
    // on the RAW string, so it can never mistake this for an actual image element.
    expect(substance('&lt;img&gt;'))->toBeTrue();
});

it('counts a bare ampersand as a visible character', function (): void {
    // Deliberate rather than accidental: `&` decodes to one visible, non-whitespace character.
    expect(substance('&amp;'))->toBeTrue();
});

it('counts the editor embed elements that were previously missing', function (string $html): void {
    expect(substance($html))->toBeTrue();
})->with([
    // CKEditor 5's media output. Omitting it was a false negative that BLOCKED publishing.
    'oembed' => ['<oembed url="https://youtu.be/abc"></oembed>'],
    'svg' => ['<svg viewBox="0 0 10 10"></svg>'],
    'canvas' => ['<canvas id="c"></canvas>'],
    'picture' => ['<picture><source srcset="a.webp"></picture>'],
    'track' => ['<video><track kind="captions" src="c.vtt"></video>'],
]);

// -- A11.5: structural shapes that read as EMPTY and would block a real lesson --------------------

it('counts a media object addressed by a bare id inside a media container', function (): void {
    expect(substance(['media' => ['id' => 7]]))->toBeTrue()
        ->and(substance(['image' => ['id' => 3, 'alt' => '']]))->toBeTrue()
        ->and(substance(['blocks' => [['attachment' => ['id' => 9]]]]))->toBeTrue();
});

it('does not count a serialised record own id as lesson content', function (): void {
    // Outside a media container a bare `id` is just the record identifying itself. Counting it would
    // make every API-shaped payload look substantial regardless of what it holds.
    expect(substance(['id' => 42]))->toBeFalse()
        ->and(substance(['id' => 42, 'type' => 'text']))->toBeFalse();
});

it('counts a list of attachment references', function (): void {
    expect(substance(['media_ids' => [12, 13]]))->toBeTrue()
        ->and(substance(['attachment_ids' => [3, 4]]))->toBeTrue()
        ->and(substance(['media_id' => [12, 13]]))->toBeTrue()
        // An empty or zero-only list still points at nothing.
        ->and(substance(['media_ids' => []]))->toBeFalse()
        ->and(substance(['media_ids' => [0]]))->toBeFalse();
});

it('treats presentation-only keys as empty however many there are', function (): void {
    // These are emitted alongside an EMPTY block; without them on the denylist a block holding
    // nothing but a colour published a blank lesson.
    expect(substance(['color' => 'red']))->toBeFalse()
        ->and(substance(['size' => 'large', 'icon' => 'star']))->toBeFalse()
        ->and(substance(['placeholder' => 'Write here...']))->toBeFalse()
        ->and(substance(['dir' => 'rtl', 'label' => 'Section']))->toBeFalse()
        ->and(substance(['type' => 'callout', 'color' => 'blue', 'icon' => 'info']))->toBeFalse();
});

it('does not let a presentation key hide real content beside it', function (): void {
    expect(substance(['color' => 'red', 'html' => '<p>Body</p>']))->toBeTrue()
        ->and(substance(['type' => 'video', 'media_id' => 12]))->toBeTrue();
});
