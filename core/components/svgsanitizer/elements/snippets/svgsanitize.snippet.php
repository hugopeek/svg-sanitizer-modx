<?php
/**
 * svgSanitize
 *
 * A snippet for parsing SVG files inline. The file is cleaned first, to make
 * sure there's no malicious javascripts or external links inside.
 *
 * Uses the following sanitizer: https://github.com/darylldoyle/svg-sanitizer/
 *
 * @author Hugo Peek
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$corePath = $modx->getOption('svgsanitizer.core_path', null, $modx->getOption('core_path') . 'components/svgsanitizer/');

if (!class_exists('\enshrined\svgSanitize\Sanitizer')) {
    require $corePath . 'vendor/autoload.php';
}

use enshrined\svgSanitize\Sanitizer;

$sanitizer = new Sanitizer();

$file = $modx->getOption('file', $scriptProperties, '');
$title = $modx->getOption('title', $scriptProperties, '');
$classes = $modx->getOption('class', $scriptProperties, '');
$stripFill = $modx->getOption('stripFill', $scriptProperties, 0);
$stripStroke = $modx->getOption('stripStroke', $scriptProperties, 0);
$minify = $modx->getOption('minify', $scriptProperties, 1);
$a11y = $modx->getOption('a11y', $scriptProperties, 1);
$removeRemote = $modx->getOption('removeRemote', $scriptProperties, 0);
$cacheExpires = $modx->getOption('cacheExpires', $scriptProperties, 86400*365);

// Indicate if the SVG will be parsed inline or not
$svgInline = $modx->getOption('inline', $scriptProperties, 1);

// Output as SVG symbol instead, for nested use in a parent SVG
$svgToSymbol = $modx->getOption('toSymbol', $scriptProperties, 0);

// Sanity check
if (!file_exists($file)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[svgSanitize] File not found: ' . $file);
    return '';
}

// Use caching to prevent unnecessary cleaning operations
$cacheManager = $modx->getCacheManager();
$cacheKey = 'svgsanitizer';
$cacheElementKey = 'svg/' . md5(json_encode($file . filesize($file) . $title . $classes));
$cacheLifetime = $cacheExpires;
$cacheOptions = array(
    xPDO::OPT_CACHE_KEY => $cacheKey,
    xPDO::OPT_CACHE_EXPIRES => $cacheLifetime,
);

// If a cached result was found, use that data
$cachedSVG = $cacheManager->get($cacheElementKey, $cacheOptions);
if ($cachedSVG) {
    return $cachedSVG;
}

// Apply some options if preferable
if ($minify) {
    $sanitizer->minify(true);
}
if ($removeRemote) {
    $sanitizer->removeRemoteReferences(true);
}
if ($svgInline || $svgToSymbol) {
    $sanitizer->removeXMLTag(true);
}

// Load the dirty svg
$dirtySVG = file_get_contents($file);

// Pass it to the sanitizer and get it back clean
if ($dirtySVG) {
    $cleanSVG = $sanitizer->sanitize($dirtySVG);
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, '[svgSanitize] File not found: ' . $file);
    return '';
}

// Don't bother proceeding if the file could not be cleaned
if (!$cleanSVG) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[svgSanitize] SVG could not be cleaned: ' . $file);
    return '';
}

// Strip some additional tags when displaying SVG inline
if ($svgInline) {
    $cleanSVG = preg_replace('/\b(?:xml).+?(\s|$|(?=>))/x', '', $cleanSVG);

    // Retain IDs in symbols
    if (!$svgToSymbol) {
        $cleanSVG = preg_replace('/\b(?:id=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    }
}

// Maybe it needs to be returned as symbol
if ($svgToSymbol) {
    $cleanSVG = preg_replace('/\b(?:svg)/x', 'symbol', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:height=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:width=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:x=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:y=).+?(\s|$|(?=>))/x', '', $cleanSVG);
}

// Remove inline fill colors from file
if ($stripFill) {
    $cleanSVG = preg_replace('/\b(?:fill=).+?(\s|$|(?=>))/x', '', $cleanSVG);
}

// Remove inline stroke colors from file
if ($stripStroke) {
    $cleanSVG = preg_replace('/\b(?:stroke=).+?(\s|$|(?=>))/x', '', $cleanSVG);
}

// Create temporary element, based on the cleaned SVG
if ($a11y || $classes || $svgInline) {
    $output = new DOMDocument();
    $output->loadXML($cleanSVG, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Add width and height to SVG based on viewBox
    $width = $output->documentElement->getAttribute('width');
    $height = $output->documentElement->getAttribute('height');
    $viewBox = $output->documentElement->getAttribute('viewBox');
    if (!$width && !$height && $viewBox) {
        $viewBox = explode(' ', $viewBox);
        $width = $viewBox[2];
        $height = $viewBox[3];

        $output->documentElement->setAttribute('width', $width);
        $output->documentElement->setAttribute('height', $height);
    }
}

// Add classes if specified
if ($classes) {
    $output->documentElement->setAttribute('class', $classes);
    $cleanSVG = $output->saveHTML();
}

// Improve accessibility
if ($a11y) {
    // For interpretation on screen readers, our SVG needs a title and some aria attributes
    // See this pen for more details: https://codepen.io/NathanPJF/full/GJObGm
    $titleID = pathinfo($file, PATHINFO_FILENAME);
    $titleElement = $output->createElement('title', $title);

    $output->documentElement->setAttribute('role', 'img');
    $output->documentElement->setAttribute('aria-label', $titleID);
    $output->documentElement->appendChild($titleElement);

    $cleanSVG = $output->saveHTML();
}

// Cache the output we have at this point
$cacheManager->set($cacheElementKey, $cleanSVG, $cacheLifetime, $cacheOptions);

// Return SVG
return $cleanSVG;