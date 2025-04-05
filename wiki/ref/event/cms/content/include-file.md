# Include File

Usage:

```html
<include-file src="$PATH" />
```

* `src` - The path to the file to include. This can be a relative, absolute, or [Zolinga URI paths](:Zolinga Core:Paths and Zolinga URI).
    * Relative paths are resolved from the current CMS layout file.
    * Supports automatic language negotiation, similar to normal CMS pages (e.g., if `something.$LANG.html` exists, it will load that instead of `something.html`).
    * Supports [content variables](:Zolinga CMS:Content Variables)