# HTML to Markdown

The `<html-to-markdown>` tag converts HTML content inside the element to Markdown using the XSLT-based converter from `$api->convert->htmlToMarkdown()`.

## Usage

```html
<html-to-markdown>
<h1>Hello World</h1>
<p>This is <strong>bold</strong> and <em>italic</em> text.</p>
<ul>
  <li>Item one</li>
  <li>Item two</li>
</ul>
</html-to-markdown>
```

## How It Works

1. The inner HTML of the `<html-to-markdown>` tag is extracted.
2. The HTML is converted to Markdown via an XSLT stylesheet (`module://zolinga-commons/data/html2md.xsl`).
3. The resulting Markdown is wrapped in a `<pre>` element and inserted into the output.

## Supported Elements

The XSLT converter supports the following HTML elements:

| HTML Element | Markdown Output |
|--------------|-----------------|
| `h1` – `h6` | `#` – `######` headings |
| `p`, `div` | Paragraphs (blank line separated) |
| `strong`, `b` | `**bold**` |
| `em`, `i` | `*italic*` |
| `code` | `` `inline code` `` |
| `pre` | Indented code block |
| `blockquote` | `>` quoted lines |
| `a` | `[text](url)` links |
| `img` | `![alt](src)` images |
| `ul` / `li` | `* ` bulleted lists |
| `ol` / `li` | `1. ` numbered lists |
| `br` | Two trailing spaces + newline |
| `hr` | `----` horizontal rule |

## Example

Input:

```html
<section>
  <html-to-markdown>
  <h2>Quick Start</h2>
  <p>Install with <code>composer require</code>.</p>
  </html-to-markdown>
</section>
```

Output:

```html
<section>
  <pre>## Quick Start

Install with `composer require`.</pre>
</section>
```

## Notes

- The tag has no attributes.
- Unsupported elements and their content are stripped.
- The result is always wrapped in a `<pre>` tag to preserve Markdown formatting.
