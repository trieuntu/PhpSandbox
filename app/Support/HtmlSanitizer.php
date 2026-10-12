<?php
namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer {
    public static function clean(?string $html): ?string {
        if ($html === null || trim($html) === '') return null;

        $config = HTMLPurifier_Config::createDefault();
        // No on-disk definition cache: an unwritable cache dir makes HTMLPurifier raise a warning (HTTP 500).
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed',
            'p,br,hr,strong,b,em,i,u,s,strike,sub,sup,blockquote,pre[class],code[class],span[style|class],div[style|class],'
            . 'h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|target|rel],img[src|alt|width|height|style],'
            . 'table[border|cellpadding|cellspacing|style|class|width],thead,tbody,tfoot,tr,th[colspan|rowspan|style],td[colspan|rowspan|style]');
        $config->set('CSS.AllowedProperties', 'text-align,font-weight,font-style,text-decoration,color,background-color,width,height,border,border-collapse,padding');
        $config->set('Attr.AllowedClasses', ['language-php', 'language-html', 'language-sql', 'language-javascript', 'language-css', 'language-markup', 'language-bash', 'language-json']);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('Attr.AllowedRel', ['noopener', 'noreferrer']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('URI.DisableExternalResources', false);
        $config->set('AutoFormat.RemoveEmpty', false);

        return (new HTMLPurifier($config))->purify($html);
    }

    // Content saved before the rich editor existed is plain text; HTML content is re-sanitized on output.
    public static function render(?string $content): string {
        if ($content === null || $content === '') return '';
        if (!preg_match('~</?(p|br|div|h[1-6]|ul|ol|li|pre|table|img|strong|em|span|a|blockquote)\b[^>]*>~i', $content)) {
            return nl2br(e($content));
        }
        return (string) self::clean($content);
    }
}
