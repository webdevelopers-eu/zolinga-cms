# Markdown to HTML

The `<markdown-to-html>` tag converts Markdown content inside the element to HTML using Parsedown.

## Usage

```html
<markdown-to-html>
# Hello World

This is **bold** and *italic* text.

- Item one
- Item two
- Item three
</markdown-to-html>
```

## How It Works

1. The inner text content of the `<markdown-to-html>` tag is extracted.
2. Common leading indentation is stripped so you can indent the Markdown inside HTML without affecting code blocks.
3. The Markdown is parsed by Parsedown and the resulting HTML is inserted into the output.

## Example

Input:

```html
<article>
  <markdown-to-html>
  # Features

  - Fast conversion
  - Parsedown powered
  - Works inside any HTML
  </markdown-to-html>
</article>
```

Output:

```html
<article>
  <h1>Features</h1>
  <ul>
    <li>Fast conversion</li>
    <li>Parsedown powered</li>
    <li>Works inside any HTML</li>
  </ul>
</article>
```

## Notes

- The tag has no attributes.
- Only text and CDATA child nodes are processed; nested elements are ignored.
- Parsedown's markup escaping is enabled for safety.
