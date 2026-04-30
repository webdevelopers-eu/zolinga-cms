---
name: zolinga-cms-page-authoring
description: "How to author CMS pages in data/zolinga-cms/pages/: cms.* meta tags, template resolution, content injection, shuffling, custom elements, content variables, and page structure conventions. Use when creating or editing any .html page in the pages directory."
argument-hint: "[page name or purpose]"
---

# Zolinga CMS Page Authoring

## Use When

- Creating a new page in `data/zolinga-cms/pages/`
- Editing an existing CMS page
- Setting up menu visibility, priority, or link text
- Understanding how templates, content injection, and shuffling work

## Critical Rule: Do Not Modify Existing cms.* Tags

When editing an **existing** page, never remove or modify these tags unless explicitly asked:
- `cms.title` - menu link text
- `cms.priority` - menu sort order
- `cms.class` - CSS class on menu item
- `cms.canonical` - menu link URL (may contain `javascript:...` for app-launch behavior)
- Any `<script>` blocks that support `cms.canonical` (e.g. `openApp()` imports)

These are functional settings, not content. Changing them breaks menu behavior.

## How Templates Work

A page declares its template via `<meta name="cms.template">`. Two formats:

| Format | Example | Resolution |
|---|---|---|
| `{design}/{layout}` | `example/main` | Resolves to `public://zolinga-cms/designs/{design}/{layout}.html` |
| Zolinga URI | `dist://some-module/design/page.html` | Resolved via `$api->fs->toPath()` directly |

**Content injection**: The page's `<body>` children are moved into the template's `<cms-content>` element. The page's `<head>` nodes are merged into the template's `<head>`.

**To find available templates**: Look for `.html` files containing `<cms-content>` in module `install/dist/design/` or `install/public/designs/` directories. The built-in default is `public://zolinga-cms/designs/default/default.html`.

**Template resolution code** (`Page.php`):
```php
if (preg_match('@^(public|dist)://@', $this->meta['cms_template'])) {
    $this->layoutFilePath = $api->fs->toPath($this->meta['cms_template']);
} else {
    list($design, $layout) = array_map('basename', explode('/', $this->meta['cms_template'] ?: '/'));
    $this->layoutFilePath = $api->fs->toPath("public://zolinga-cms/designs/$design/$layout.html");
}
```

## Content Variables

Available in both templates and page `.html` files (string-replaced before DOM parsing):

| Variable | Description | Example |
|---|---|---|
| `{{designPath}}` | URL path to the design folder | `/dist/some-module/design` |
| `{{locale}}` | Full locale code | `en-US` |
| `{{lang}}` | Primary language code | `en` |
| `{{revHash}}` | Revision hash from all zolinga.json files (cache busting) | `a3f2b1c` |

Usage: `<link rel="stylesheet" href="{{designPath}}/css/style.css" />` or `<a href="/{{lang}}/register">`

## `<include-file>` Element

Includes HTML fragments from another file into the template:

```html
<include-file src="shared.html" select="//head/*" />
```

- `src` - path relative to the current template's directory
- `select` - optional XPath expression (default: `/*` = all root children)
- Included file also gets content variables resolved

## Content Shuffling Attributes

After content injection, elements can be repositioned using these attributes:

| Attribute | Effect |
|---|---|
| `append-to="#id"` | Appends element to target |
| `append-contents-to="#id"` | Appends children, removes empty wrapper |
| `prepend-to="#id"` | Prepends element to target |
| `prepend-contents-to="#id"` | Prepends children, removes wrapper |
| `replace="#id"` | Replaces target with this element |
| `replace-with-contents="#id"` | Replaces target with this element's children |
| `replace-contents="#id"` | Replaces target's children with this element |
| `replace-contents-with-contents="#id"` | Replaces target's children, removes wrapper |
| `move-before="#id"` | Moves element before target |
| `move-contents-before="#id"` | Moves children before target |
| `move-after="#id"` | Moves element after target |
| `move-contents-after="#id"` | Moves children after target |

Selectors: `#id` format or `xpath:` prefix for XPath expressions.

Example: `<h1 append-to="my-header">About Us</h1>` moves the h1 into the template's header.

## `<cms-menu>` Element

Generates navigation menu in templates:

```html
<cms-menu level="1" depth="2" id="main-nav" class="nav"></cms-menu>
```

- `level` - starting depth (1 = root, default: 1)
- `depth` - how many levels deep (default: 1)

## `<replace-vars>` Element

Replaces variable placeholders with GET/POST/runtime values:

```html
<replace-vars>
    <h2>Results for: {{GET:search}}</h2>
    <p>Page: {{GET:page|1}}</p>
    <p>You submitted: {{POST:message}}</p>
    <p>Custom: {{VAR:key|default}}</p>
</replace-vars>
```

- `{{GET:param}}` / `{{GET:param|default}}` - from query string
- `{{POST:param}}` / `{{POST:param|default}}` - from POST data
- `{{VAR:key}}` / `{{VAR:key|default}}` - set via `$api->replaceVars->set('key', 'value')`
- All replacements are HTML-escaped for security

## Custom Elements

Any HTML element with a hyphen in its tag name that lacks `render="client"` triggers a `cms:content:{tag-name}` internal event. If a listener responds, the element is replaced with the listener's output. If no listener is found, the element is left for client-side rendering (Web Components).

Built-in custom elements:

| Element | Description |
|---|---|
| `<include-file>` | Include external HTML fragments |
| `<cms-menu>` | Generate navigation menu |
| `<replace-vars>` | Replace GET/POST/VAR placeholders |
| `<random-chooser>` | Randomly select N child elements |
| `<transform-content>` | Apply XSLT transformation |
| `<html-to-markdown>` | Convert inner HTML to Markdown |
| `<markdown-to-html>` | Convert inner Markdown to HTML |

Use `render="client"` on Web Components to skip server-side processing.

## cms.* Meta Tags Reference

All `cms.*` meta tags are parsed via PHP's `get_meta_tags()`. The parser lowercases names and replaces non-alphanumeric chars with `_`, so `cms.template` becomes key `cms_template`.

| Meta Tag | Default | Purpose |
|---|---|---|
| `cms.template` | `''` | Page template. Stripped from output HTML. See "How Templates Work" above. |
| `cms.title` | `''` | **Menu link text** - keep it short, NOT for SEO. Fallback: `cms.title` -> `DC.Title` -> `<title>` -> "Untitled Page". |
| `cms.priority` | `'0.5'` | Sort order among sibling pages in menu. Lower = lower in menu. Clamped 0.000001 - 0.999999. |
| `cms.visibility` | `'visible'` | Menu visibility. Values: `visible` or `hidden`. |
| `cms.class` | `''` | CSS class(es) added to the menu `<li>` element. Space-separated. |
| `cms.canonical` | `''` | Custom URL for the menu link. Supports `javascript:...` scheme. Fallback: `<link rel="canonical">`. |
| `cms.right` | not implemented | Access control. If `'hidden'`, hides from menu. Not yet fully implemented. |
| `cms.description` | `''` | Page description. Fallback: `cms.description` -> `DC.Description` -> `description` meta -> `false`. |

## Key Distinctions

- **`cms.title`** = menu label (short, e.g. "My Brand", "Pricing"). NOT for SEO.
- **`<title>`** = SEO title (longer, keyword-rich).
- **`<meta name="description">`** = SEO meta description. Use `gettext="content"` for translatability.
- **`<link rel="canonical">`** = SEO canonical URL. Separate from `cms.canonical` which controls menu link href.

## Page Tree and URLs

- Pages live in `data/zolinga-cms/pages/` as `.html` files
- Directory structure = URL structure: `about/team/index.html` -> `/about/team`
- Tree cached in `data/zolinga-cms/menu.cache.{locale}.json`
- Disable cache in development: set `cms.menuCache` to `false` in `config/local.json`

## Minimal Page Example

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SEO Title Here</title>
    <meta name="cms.template" content="default/main" />
    <meta name="cms.title" content="Menu Label" />
    <meta name="cms.priority" content="0.5" />
    <meta name="cms.visibility" content="visible" />
    <meta gettext="content" name="description"
        content="SEO meta description here." />
    <link rel="canonical" href="https://www.example.com/page-slug" />
</head>
<body>
    <div class="box appear content-width">
        <h1>Page Heading</h1>
        <p>Content here.</p>
    </div>
</body>
</html>
```

## Template + Shuffling Example

Template (`public://zolinga-cms/designs/example/main.html`):
```html
<!DOCTYPE html>
<html>
<head></head>
<body>
    <header id="my-header"></header>
    <main>
        <cms-content></cms-content>
    </main>
    <footer id="my-footer"></footer>
</body>
</html>
```

Page (`data/zolinga-cms/pages/about/index.html`):
```html
<!DOCTYPE html>
<html>
<head>
    <meta name="cms.template" content="example/main"/>
    <meta name="cms.title" content="About Us"/>
    <title>About Us</title>
</head>
<body>
    <h1 append-to="my-header">About Us</h1>
    <p>Some content...</p>
    <footer replace="my-footer">My custom footer.</footer>
</body>
</html>
```

## SEO Best Practices

- Add `<script type="application/ld+json">` structured data in `<head>` (SoftwareApplication, FAQPage, etc.)
- Use `<meta name="keywords">` for relevant search terms
- Use semantic HTML: proper heading hierarchy, `<ol>` for numbered lists, `<em>` for emphasis
- Add `gettext="content"` on meta descriptions and `gettext="."` on translatable text elements

## References

- `modules/zolinga-cms/src/Page.php` - template resolution, content injection, reshuffling
- `modules/zolinga-cms/src/PageServer.php` - request handling, file resolution
- `modules/zolinga-cms/src/PageMenu.php` - `<cms-menu>` handler, menu rendering
- `modules/zolinga-cms/src/IncludeFile.php` - `<include-file>` handler
- `modules/zolinga-cms/src/ReplaceVarsListener.php` - `<replace-vars>` handler
- `modules/zolinga-cms/src/ContentParser.php` - custom element event dispatch
- `modules/zolinga-cms/wiki/Zolinga CMS/Templates.md` - template format docs
- `modules/zolinga-cms/wiki/Zolinga CMS/Content Variables.md` - variable docs
- `modules/zolinga-cms/wiki/Zolinga CMS/Shuffling the Content.md` - shuffling docs
- `modules/zolinga-cms/wiki/Zolinga CMS/Custom Elements.md` - custom element docs
- `modules/zolinga-cms/wiki/Zolinga CMS/Defining a Page Tree.md` - page tree docs
- `modules/zolinga-cms/wiki/Zolinga CMS/Replace Variables Tag.md` - replace-vars docs
- `modules/zolinga-cms/wiki/Zolinga CMS/Example.md` - template + page example
