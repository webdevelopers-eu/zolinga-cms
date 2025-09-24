# Shuffling the Content

It is common that you don't want all the content to end up in the `<cms-content></cms-content>` element in the template. You may want to insert something in the content, something in the header and something in the footer. You can do this by using a group of powerful attributes on any element.

## Selector Formats

Each of the shuffling attributes accepts a target selector in one of two formats:

1. **ID selector**: Using `#id` format to target elements by their ID attribute
2. **XPath selector**: Using `xpath:` prefix followed by a valid XPath expression

## Shuffling Attributes

- `append-to="{selector}"`
> Appends the element to the element matched by the selector.
- `append-contents-to="{selector}"`
> Appends all the child elements of the element to the element matched by the selector and then removes the element.
- `prepend-to="{selector}"`
> Prepends the element to the element matched by the selector.
- `prepend-contents-to="{selector}"`
> Prepends all the child elements of the element to the element matched by the selector and then removes the element.
- `replace="{selector}"`
> Replaces the element matched by the selector.
- `replace-with-contents="{selector}"`
> Replaces the element matched by the selector with all the child elements of the element. The empty element is then removed.
- `replace-contents="{selector}"`
> Replaces all the child elements of the element matched by the selector.
- `replace-contents-with-contents="{selector}"`
> Replaces all the child elements of the element matched by the selector with all the child elements of the element. The empty element is then removed.
- `move-before="{selector}"`
> Moves the element before the element matched by the selector.
- `move-contents-before="{selector}"`
> Moves all the child elements of the element before the element matched by the selector. The empty element is then removed.
- `move-after="{selector}"`
> Moves the element after the element matched by the selector.
- `move-contents-after="{selector}"`
> Moves all the child elements of the element after the element matched by the selector. The empty element is then removed.

## Examples

### Using ID selector:
```html
<div move-after="#header">This content will be moved after the element with id="header"</div>
```

### Using XPath selector:
```html
<video 
    move-after="xpath://article/h1"
    class="appear decor right"
    poster="/path/to/video-poster.webp"
    src="/path/to/video.mp4">
</video>
```

This will move the video element after the first H1 element inside an article.