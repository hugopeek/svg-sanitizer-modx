<?php
/**
 * svgSanitize
 *
 *
 *
 *
 * @author Hugo Peek
 */

$corePath = $modx->getOption('svgsanitizer.core_path', null, $modx->getOption('core_path') . 'components/svgsanitizer/');

$file = $modx->getOption('file', $scriptProperties, '');
$title = $modx->getOption('title', $scriptProperties, '');
$minify = $modx->getOption('minify', $scriptProperties, 1);
$a11y = $modx->getOption('a11y', $scriptProperties, 1);
$removeRemote = $modx->getOption('removeRemote', $scriptProperties, 0);
$cacheExpires = $modx->getOption('cacheExpires', $scriptProperties, 86400*365);

// Indicate if the SVG will be parsed inline or not
$svgInline = $modx->getOption('inline', $scriptProperties, 1);

// Output as SVG symbol instead, for nested use in a parent SVG
$svgToSymbol = $modx->getOption('toSymbol', $scriptProperties, 0);

// Setup svgSanitize
if (!class_exists('\enshrined\svgSanitize\Sanitizer')) {
    require $corePath . 'vendor/autoload.php';
}

use enshrined\svgSanitize\Sanitizer;

// Create a new sanitizer instance
$sanitizer = new Sanitizer();

// Apply some options if preferable
if ($minify) {
    $sanitizer->minify(true);
}
if ($removeRemote) {
    $sanitizer->removeRemoteReferences(true);
}
if ($svgInline) {
    $sanitizer->removeXMLTag(true);
}

// Load the dirty svg
$dirtySVG = file_get_contents($file);

// Pass it to the sanitizer and get it back clean
$cleanSVG = $sanitizer->sanitize($dirtySVG);

// Don't bother proceeding if the file could not be cleaned
if (!$cleanSVG) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[svgSanitize] SVG could not be cleaned: ' . $file);
    return '';
}

// Cache results, to prevent unnecessary cleaning operations
$cacheManager = $modx->getCacheManager();
$cacheKey = 'svgsanitizer';
$cacheElementKey = 'svg/' . md5(json_encode($file));
$cacheLifetime = $cacheExpires;
$cacheOptions = array(
    xPDO::OPT_CACHE_KEY => $cacheKey,
    xPDO::OPT_CACHE_EXPIRES => $cacheLifetime,
);

// Check the cache first
$cachedSVG = $cacheManager->get($cacheElementKey, $cacheOptions);

// If a cached result was found, use that data
if ($cachedSVG) {
    return $cachedSVG;
}

// If the SVG is going to be parsed inline, then certain tags and properties
// will need to be stripped from the output.
if ($svgInline) {
    $cleanSVG = preg_replace('/\b(?:xml).+?(\s|$|(?=>))/x', '', $cleanSVG);
}

// Maybe it needs to be returned as symbol
if ($svgToSymbol) {
    $cleanSVG = preg_replace('/\b(?:svg)/x', 'symbol', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:height=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:width=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:x=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:y=).+?(\s|$|(?=>))/x', '', $cleanSVG);
}

// For better accessibility, we inject a few extra properties into the SVG.
// Because we all know what happens if we don't... (nothing)
if ($a11y) {
    // We'll create a new document, based on the cleaned SVG
    $output = new DOMDocument();
    $output->loadXML($cleanSVG, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // For interpretation on screen readers, our SVG needs a title and some aria attributes
    // See this pen for more details: https://codepen.io/NathanPJF/full/GJObGm
    $titleID = pathinfo($file, PATHINFO_FILENAME);
    $titleElement = $output->createElement('title', $title);
    $titleElement->setAttribute('id', 'title-' . $titleID);

    $output->documentElement->appendChild($titleElement);
    $output->documentElement->setAttribute('role', 'img');
    $output->documentElement->setAttribute('aria-labelledby', 'title-' . $titleID);

    $cleanSVG = $output->saveHTML();
}

// Cache the output we have at this point and then return it
$cacheManager->set($cacheElementKey, $cleanSVG, $cacheLifetime, $cacheOptions);

return $cleanSVG;