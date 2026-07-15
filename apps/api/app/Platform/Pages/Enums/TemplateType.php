<?php

namespace App\Platform\Pages\Enums;

/**
 * The EXACT set of predefined page templates a static page may use. This is NOT a freeform page
 * builder — a page picks one of these fixed templates and the frontend renders a matching layout
 * (a standard editorial page, a narrow legal document, an FAQ, or a contact page). Adding a new
 * template is a deliberate code change, never an admin/data action.
 */
enum TemplateType: string
{
    case Standard = 'standard';
    case Legal = 'legal';
    case Faq = 'faq';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Legal => 'Legal',
            self::Faq => 'FAQ',
            self::Contact => 'Contact',
        };
    }

    /** @return array<string, string> value => label, for Filament selects. */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
