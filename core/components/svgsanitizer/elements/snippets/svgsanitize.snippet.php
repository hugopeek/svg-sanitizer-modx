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
$minify = $modx->getOption('minify', $scriptProperties, 1);
$removeRemote = $modx->getOption('removeRemote', $scriptProperties, 0);

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
$cacheLifetime = 86400*365;
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
    $cleanSVG = preg_replace('/(?:svg)/x', 'symbol', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:height=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:width=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:x=).+?(\s|$|(?=>))/x', '', $cleanSVG);
    $cleanSVG = preg_replace('/\b(?:y=).+?(\s|$|(?=>))/x', '', $cleanSVG);
}


// Cache the output we have at this point and then return it
$cacheManager->set($cacheElementKey, $cleanSVG, $cacheLifetime, $cacheOptions);

return $cleanSVG;


//if ($tpl) {
//    $output = $modx->getChunk($tpl, $addressArray);
//}
//
//$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);
//if (!empty($toPlaceholder)) {
//    $modx->setPlaceholder($toPlaceholder, $output);
//    return '';
//}
//return $output;