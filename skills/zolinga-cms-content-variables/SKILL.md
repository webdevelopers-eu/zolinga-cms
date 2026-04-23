---
name: zolinga-cms-content-variables
description: Use when editing Zolinga CMS page or template HTML that references design assets, locale-aware links, or revisioned static files.
argument-hint: "[page or template path]"
---

# Zolinga CMS Content Variables

## Use When

- Editing `data/zolinga-cms/pages/*.html` content files.
- Editing CMS template HTML that references design assets.
- Replacing hardcoded design URLs such as `/dist/<module>/design/...` with CMS variables.
- Adding locale-aware links or cache-busted asset URLs in CMS-rendered HTML.

## Workflow

1. Treat Zolinga CMS content variables as the default for CMS page and template HTML.
2. Use `{{designPath}}` for assets that live in the active design folder.
3. Prefer `{{designPath}}/css/...`, `{{designPath}}/img/...`, and similar paths instead of hardcoded `/dist/<module>/design/...` URLs.
4. Use `{{lang}}` or `{{locale}}` for language-specific links or asset variants when needed.
5. Use `{{revHash}}` only when cache busting is needed for static asset URLs.
6. Keep replacements limited to CMS-rendered HTML files; do not apply these variables inside arbitrary JavaScript or PHP unless the content is passed through the CMS variable replacement flow.

## Common Patterns

```html
<link rel="stylesheet" href="{{designPath}}/style.css" />
<img src="{{designPath}}/img/hero.webp" alt="" />
<a href="/{{lang}}/register">Register</a>
<script src="{{designPath}}/app.js?v={{revHash}}"></script>
```

## References

- `modules/zolinga-cms/wiki/Zolinga CMS/Content Variables.md`
- `modules/zolinga-cms/README.md`