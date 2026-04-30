---
name: zolinga-cms-page-authoring
description: "How to author CMS pages in data/zolinga-cms/pages/: cms.* meta tags, template selection, menu control, SEO tags, and page structure conventions. Use when creating or editing any .html page in the pages directory."
argument-hint: "[page name or purpose]"
---

# Zolinga CMS Page Authoring

## Use When

- Creating a new page in `data/zolinga-cms/pages/`
- Editing an existing CMS page's `<head>` section
- Setting up menu visibility, priority, or link text

## cms.* Meta Tags

All `cms.*` meta tags are parsed via PHP's `get_meta_tags()`. The parser lowercases names and replaces non-alphanumeric chars with `_`, so `cms.template` becomes key `cms_template`.

| Meta Tag | Default | Purpose |
|---|---|---|
| `cms.template` | `''` | Page template/layout. Two forms: `{design}/{layout}` resolves to `public://zolinga-cms/designs/{design}/{layout}.html`; or `public://...` / `dist://...` Zolinga URI resolved directly. Content is injected into the template's `<cms-content>` tag. Stripped from output HTML. |
| `cms.title` | `''` | **Menu link text** - keep it short, this is NOT SEO. Fallback chain: `cms.title` -> `DC.Title` -> `<title>` element -> "Untitled Page". |
| `cms.priority` | `'0.5'` | Sort order among sibling pages in the menu tree. Lower = lower in menu. Clamped to 0.000001 - 0.999999. |
| `cms.visibility` | `'visible'` | Menu visibility. Values: `visible` or `hidden`. If not `visible`, page is hidden from menu. |
| `cms.class` | `''` | CSS class(es) added to the menu `<li>` element. Space-separated. |
| `cms.canonical` | `''` | Custom URL for the menu link. Supports `javascript:...` scheme. Fallback: `<link rel="canonical" href="...">`. |
| `cms.right` | not implemented | Access control right. If set to `'hidden'`, hides page from menu (same as `cms.visibility=hidden`). Not yet fully implemented. |
| `cms.description` | `''` | Page description. Fallback chain: `cms.description` -> `DC.Description` -> `description` meta -> `false`. |

## Key Distinctions

- **`cms.title`** = menu label (short, e.g. "Monitoring", "Pricing"). NOT for SEO.
- **`<title>`** = SEO title (longer, keyword-rich, e.g. "AI Trademark Monitoring - Protect Your Brand Across 40+ Countries | IP Defender").
- **`<meta name="description">`** = SEO meta description. Use `gettext="content"` attribute for translatability.
- **`<link rel="canonical">`** = SEO canonical URL. Separate from `cms.canonical` which controls menu link href.

## Minimal Page Template

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SEO Title Here | IP Defender</title>
    <meta name="cms.template" content="dist://my-module/design/page.html" />
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

## Template Options


## SEO Best Practices

- Add `<script type="application/ld+json">` structured data in `<head>` (SoftwareApplication, FAQPage, etc.)
- Use `<meta name="keywords">` for relevant search terms
- Use semantic HTML: proper heading hierarchy, `<ol>` for numbered lists, `<em>` for emphasis
- Add `gettext="content"` on meta descriptions and `gettext="."` on translatable text elements

## References

- `modules/zolinga-cms/src/Page.php` - parses all cms.* meta tags (line ~111)
- `modules/zolinga-cms/src/PageServer.php` - strips cms.template from output
- `modules/zolinga-cms/src/PageMenu.php` - renders menu using cms.title, cms.priority, cms.visibility, cms.class, cms.canonical