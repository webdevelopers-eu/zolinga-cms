Priority: 0.5

# Void Element

The `<void>` element is a server-side-only container that is always stripped from the final HTML output. Its children are preserved and lifted into the parent element, but the `<void>` tag itself never reaches the browser.

## Purpose

Use `<void>` as an invisible grouping wrapper when you need to apply server-side logic (templating, shuffling, conditional rendering, etc.) to a block of content without introducing an extra DOM element to the front-end.

## How It Works

During page processing, the CMS before its final output is generated:

1. Finds all `<void>` elements in the document.
2. Moves each `<void>` element's children up into the parent element (preserving order).
3. Removes the now-empty `<void>` element.

This is equivalent to unwrapping the element — the content stays, the wrapper disappears.

## Example

```html
<div class="hero">
    <void>
        <h1>Welcome</h1>
        <p>Intro text</p>
    </void>
</div>
```

**Output sent to browser:**

```html
<div class="hero">
    <h1>Welcome</h1>
    <p>Intro text</p>
</div>
```

## Use Cases

- **Grouping for shuffle**: Wrap sibling elements in `<void>` so the shuffle algorithm treats them as a movable unit, without adding a wrapper element to the output.
- **Conditional blocks**: Use with template logic that should not leave a structural trace.
- **Server-side organization**: Group related elements for readability or processing without affecting the rendered DOM.
- **Translatable chunks**: In combination with translation attributes, wrap large translatable sections without adding extra tags to the output.