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
//$cachedSVG = $cacheManager->get($cacheElementKey, $cacheOptions);

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

// First, we
$new = new DOMDocument();
$new->formatOutput = true;

$new->loadXML("<root><svg aria-labelledby='123'><title id='123'>Test test title</title></svg></root>");

//echo "The 'new document' before copying nodes into it:\n";
//echo $new->saveHTML();


$source = new DOMDocument();
$source->loadXML($cleanSVG, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

$node = $new->getElementsByTagName('title')->item(0);

//echo $source->saveHTML();

// Import the node, and all its children, to the document
$node = $source->importNode($node, true);
// And then append it to the "<root>" node
$source->documentElement->appendChild($node);
$source->documentElement->setAttribute('id', '123');
$source->documentElement->setAttribute('role', 'img');

//echo "\nThe 'new document' after copying the nodes into it:\n";
echo $source->saveHTML();

//$output = new DOMDocument();
//$output->formatOutput = true;
//
//$output->loadXML("<svg><title>Test test title</title></svg>");
//
//echo "The 'new document' before copying nodes into it:\n";
//echo $output->saveHTML();
//
//// Import the node, and all its children, to the document
//$node = $output->importNode($node, true);
//// And then append it to the "<root>" node
//$output->documentElement->appendChild($node);
//
//echo "\nThe 'new document' after copying the nodes into it:\n";
//echo $output->saveHTML();


//$title = $doc->createElement('title', 'Test');
//$id = $doc->createAttribute('id');


//$doc->createElement('id', '123');
//$title->setAttribute('id', '123');
//
//$doc->appendChild($title);
//$svg = $doc->saveHTML();
//
//$title->setAttribute('id', '123');
//
//print_r($svg);


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