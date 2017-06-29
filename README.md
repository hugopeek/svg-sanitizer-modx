# svgSanitizer

A MODX extra for parsing SVG files inline. The file is cleaned first, to make sure there's no malicious javascripts or external links inside.

Uses the following sanitizer: https://github.com/darylldoyle/svg-sanitizer/

## Inline usage

All you need to do to get started, is place your SVGs in a folder somewhere inside your project and reference the file path in the svgSanitize snippet call:

```
[[svgSanitize?
    &file=`assets/img/icons/sanitize-me.svg`
    &title=`Add a title here for improved accessibility`
]]
```

## Create SVG sprite

In addition to cleaning your SVG, you can choose to output the result as a symbol, for use in an SVG sprite:

```html
<svg style="display: none;">
    [[svgSanitize?
        &file=`assets/img/icons/sanitize-me.svg`
        &title=`Add a title here for improved accessibility`
        &toSymbol=`1`
    ]]
    ...
</svg>
```

Will result in something like:

```html
<svg style="display: none;">
    <symbol viewBox="0 0 24 24" id="sanitize-me">
        <title>Yay, you cared about accessibility</title>
        ...
    </symbol>
    ...
</svg>
```

Include this SVG somewhere in your HTML and then reference the symbols in your content like this:

```html
<svg>
    <use xlink:href="[[~[[*id]]? &scheme=`full`]]#sanitize-me"></use>
</svg>
```

Note that you have to prepend the anchor with the full URI. This is because MODX pages use the base element `<base href="https://your-site.com/">`, which confuses the anchor link inside the `<use>` element in some browsers.

Read more about that here: https://stackoverflow.com/questions/18259032/

## Properties

For svgSanitize snippet.

Name | Description | Default
--- | --- | ---
file | The path to your SVG file, starting from your project root folder. |
title | Add a title that describes the content of the SVG graphic. This is important for people using screen readers. |
minify | Removes unneeded spaces and line breaks. | 1
inline | By default, the snippet strips the XML header and some attributes from your SVG file that are not needed for inline display.. But if you are planning to use your SVG as stand-alone file, then you probably want to keep those elements in. | 1
removeRemote | See: https://github.com/darylldoyle/svg-sanitizer/#remove-remote-references | 0
cacheExpires | By default, the generated SVGs are cached for 1 year. You can change this to 1 for example, if you don't want it to cache anything during testing. | 86400*365
a11y | If accessibility is not important for you, or if you already have that covered inside your SVG, you can disable this feature by setting it to 0. | 1

## Why

SVGs are a perfect way for delivering sharp and scalable graphics on your site. And serving them inline makes them even more versatile! You can change the colors of individual paths, let the SVG adapt to its parent's fill color or animate the contents with JS.

An added bonus for including SVGs with the svgSanitize snippet, is that you can actually use MODX syntax inside your SVGs. Create multilanguage SVGs by using lexicon strings as text, or use them in getResources templates with unique pagetitles or TV data... Endless possibilities.

Have fun!

## References

Some interesting resources about working with SVG:

- https://css-tricks.com/pretty-good-svg-icon-system/
- https://24ways.org/2014/an-overview-of-svg-sprite-creation-techniques/
- https://medium.com/@webprolific/why-and-how-i-m-using-svg-over-fonts-for-icons-7241dab890f0
- https://nucleoapp.com/how-to-create-an-icon-system-using-svg-symbols/
- https://css-tricks.com/svg-use-with-external-reference-take-2/
- https://codepen.io/NathanPJF/full/GJObGm
- https://www.sarasoueidan.com/blog/svgo-tools/
- https://jakearchibald.github.io/svgomg/
