# svgSanitizer

A MODX extra to sanitize SVG files and parse them inline, or as part of an SVG sprite. The file is cleaned first to make sure there are no malicious scripts, links or other XSS tricksters inside. After that, you can tweak the output to match any compatibility or design requirements you may have.

Many thanks to Daryll Doyle for providing and maintaining the sanitizer:
https://github.com/darylldoyle/svg-sanitizer/

## Inline usage

To get started, place your SVGs in a folder inside your project and reference the file path in the svgSanitize snippet call:

```
[[svgSanitize?
    &file=`assets/img/icons/sanitize-me.svg`
    &title=`Add a title here for improved accessibility`
]]
```

## Create an SVG sprite

In addition to cleaning your SVG, you can choose to output the result as a symbol, for use in an SVG sprite:

```html
<svg class="hidden">
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
<svg class="hidden">
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

## Caching

The sanitized SVGs are cached inside a sub folder of core/cache, based on the cacheKey setting. By default, this will be `svgsanitizer`. But please note:

> [!NOTE]
> **The default 'svgsanitizer' cache folder is not cleared automatically!**

If you want to clear the SVG cache every time the MODX cache is cleared, you could simply change the cacheKey to 'default'. But I don't recommend this. There's really no need to sanitize each SVG again after a cache clear.

A better option is to install [getCache](https://extras.modx.com/package/getcache) and create a menu button for manually clearing the 'svgsanitizer' partition. Here's an example handler:

```js
var partition = 'svgsanitizer';
var topic = '/getcache/cache/partition/refresh/' + partition;

this.console = MODx.load({
    xtype: 'modx-console',
    register: 'mgr',
    topic: topic,
    show_filename: 0
});

this.console.show(Ext.getBody());

MODx.Ajax.request({
    url: MODx.config.assets_url + 'components/getcache/connector.php',
    params: {
        action: 'cache/partition/refresh',
        partitions: partition,
        register: 'mgr',
        topic: topic
    },
    listeners: {
        'success': {
            fn: function () {
                this.console.fireEvent('complete');
            }, scope: this
        }
    }
});

return false;
```

You could place this under the regular Clear Cache button for example. Attach the `empty_cache` permission to grant the same access as for regular cache clearing.

## Properties

For the svgSanitize snippet.

Name | Description | Default
--- | --- | ---
file | The path to your SVG file. Can be absolute, or relative to your project root folder. |
title | Add a title that describes the content of the SVG graphic. This is important for people using screen readers. |
classes | Add one or more class names to the SVG tag. Only applies if used inline. |
stripFill | Remove inline fill colors from file. Enable this if you want to control fill color with CSS, i.e. for icons. | 0
stripStroke | Remove inline stroke colors from file. Same thing. | 0
minify | Removes unneeded spaces and line breaks. | 1
inline | By default, the snippet strips the XML header and some attributes from your SVG file that are not needed for inline display. But if you are planning to use your SVG as stand-alone file, then you probably want to keep those elements in. | 1
removeRemote | See: https://github.com/darylldoyle/svg-sanitizer/#remove-remote-references | 0
cacheKey | Sanitized SVGs will be cached inside this subfolder of core/cache. | svgsanitizer
cacheExpires | By default, the generated SVGs are cached for 1 year. You can change this to 1 for example, if you don't want it to cache anything during testing. | 86400*365
a11y | If accessibility is not a requirement, or if you already have that covered inside your SVG, you can disable this feature by setting it to 0. | 1

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
