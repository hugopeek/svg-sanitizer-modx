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

// Option to remove the <?xml> tag from the header
$stripHeader = $modx->getOption('stripHeader', $scriptProperties, 0);

// Output as SVG symbol instead of stand-alone SVG (for nested use in a parent SVG)
$toSymbol = $modx->getOption('toSymbol', $scriptProperties, 0);

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
if ($stripHeader) {
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

// Maybe it needs to be returned as symbol
if ($toSymbol) {
    $svgToSymbol = preg_replace('/\b(?:xmlns).+?(\s|$)/x', '', $cleanSVG);
    $svgToSymbol = preg_replace('/(?:svg)/x', 'symbol', $svgToSymbol);
    $cacheManager->set($cacheElementKey, $svgToSymbol, $cacheLifetime, $cacheOptions);
    return $svgToSymbol;
}

// Otherwise, cache the SVG first and then return it
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