# Random Chooser

The `<random-chooser>` tag evaluates an XPath selector relative to itself, shuffles the matching nodes, and replaces the tag with clones of the selected nodes. The selector may return elements, text nodes, or other node types that can be cloned and appended to the output fragment. It can also insert a text separator between selected items.

## Usage

```html
<random-chooser count="2" text-separator=", ">
    <article>Alpha</article>
    <article>Beta</article>
    <article>Gamma</article>
</random-chooser>
```

## Attributes

- `count` (optional) - Number of shuffled matching nodes to output. Use a positive integer to limit the output, or `0` / `all` to output all matches in random order. Default: `all`.
- `selector` (optional) - XPath selector evaluated relative to the `<random-chooser>` element. Default: `./*`.
- `text-separator` (optional) - Plain text inserted between selected output nodes. Default: empty string.

## How It Works

1. The `selector` XPath is evaluated relative to the `<random-chooser>` element.
2. Matching nodes from the XPath result are eligible for output.
3. The matching nodes are shuffled.
4. Up to `count` nodes are cloned into the final output. If `count` is `0` or `all`, all matches are output in random order.
5. If `text-separator` is set, that text is inserted between the selected output nodes.

If the XPath selector cannot be evaluated, the tag may produce no output or fail depending on the underlying XPath query result.

## Examples

Choose 2 random direct child elements:

```html
<random-chooser count="2">
    <div>One</div>
    <div>Two</div>
    <div>Three</div>
    <div>Four</div>
</random-chooser>
```

Choose 3 random nested elements matched by XPath:

```html
<random-chooser count="3" selector="./section/article">
    <section>
        <article>First</article>
        <article>Second</article>
        <article>Third</article>
        <article>Fourth</article>
    </section>
</random-chooser>
```

Shuffle all matching elements without limiting the output:

```html
<random-chooser count="all" selector="./section/article">
    <section>
        <article>First</article>
        <article>Second</article>
        <article>Third</article>
    </section>
</random-chooser>
```

Join selected elements with a separator string:

```html
<random-chooser count="3" selector="./span" text-separator=", ">
    <span>Alpha</span>
    <span>Beta</span>
    <span>Gamma</span>
    <span>Delta</span>
</random-chooser>
```

Choose text nodes and join them into a comma-separated string:

```html
<random-chooser count="3" text-separator=", " selector="./*/text()">
    <div>111</div>
    <div>222</div>
    <div>333</div>
    <div>444</div>
    <div>555</div>
</random-chooser>
```

This can render output such as:

```html
222, 555, 111
```

The same shuffle-all behavior also works with `count="0"`:

```html
<random-chooser count="0">
    <li>One</li>
    <li>Two</li>
    <li>Three</li>
</random-chooser>
```

## Notes

- `count` means the number of random nodes chosen from the nodes matched by `selector`.
- If fewer than `count` matches exist, all matching nodes are output.
- `count="0"` and `count="all"` both mean: output all matches, but shuffle their order.
- This makes the tag useful as a pure shuffler for existing markup.
- `text-separator` inserts plain text between emitted nodes, but not before the first one.
- `selector` can return text nodes, which makes the tag useful for building shuffled inline text fragments.
- Nested custom elements inside the chosen nodes are still parsed later by the CMS parser after replacement.