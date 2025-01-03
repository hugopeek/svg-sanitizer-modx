<?php
/**
 * Lexicon entries for svgSanitizer properties
 *
 * @package svgsanitizer
 * @subpackage lexicon
 */

$_lang['svgsanitizer.svgsanitize.file'] = 'The path to your SVG file. Can be absolute, or relative to your project root folder.';
$_lang['svgsanitizer.svgsanitize.title'] = 'Add a title that describes the content of the SVG graphic. This is important for people using screen readers.';
$_lang['svgsanitizer.svgsanitize.class'] = 'Add one or more class names to the SVG tag. Only applies if used inline.';
$_lang['svgsanitizer.svgsanitize.stripFill'] = 'Remove inline fill colors from file. Enable this if you want to control fill color with CSS, i.e. for icons.';
$_lang['svgsanitizer.svgsanitize.stripStroke'] = 'Remove inline stroke colors from file. Same thing as with fill.';
$_lang['svgsanitizer.svgsanitize.minify'] = 'Removes unneeded spaces and line breaks.';
$_lang['svgsanitizer.svgsanitize.inline'] = 'By default, the snippet strips the XML header and some attributes from your SVG file that are not needed for inline display. But if you are planning to use your SVG as stand-alone file, then you probably want to keep those elements in.';
$_lang['svgsanitizer.svgsanitize.removeRemote'] = 'See: https://github.com/darylldoyle/svg-sanitizer/#remove-remote-references';
$_lang['svgsanitizer.svgsanitize.a11y'] = 'If accessibility is not a requirement, or if you already have that covered inside your SVG, you can disable this feature by setting it to 0.';
