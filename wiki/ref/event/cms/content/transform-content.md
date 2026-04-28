# Transform Content

Applies an XSLT stylesheet to the inner content of the `<transform-content>` element.

## Usage

```html
<transform-content xslt="ZOLINGA_PATH">
    ...content to transform...
</transform-content>
```

## Attributes

- `xslt` (required) — Path to the XSLT stylesheet file. Supports [Zolinga URI paths](:Zolinga Core:Paths and Zolinga URI), e.g. `module://my-module/templates/my-transform.xslt`.
- `preprocess` (optional) — Whether to expand inner custom content tags before applying the XSLT transformation. Default: `true`. Set to `false` to skip pre-processing.
- `strip-root` (optional) — Whether to strip the root element from the XSLT output. When not set, the root element is automatically stripped if it is named `<void>`. When `false`, the full result document (including its root element) is always inserted. When `true`, only the children of the root element are inserted, discarding the root wrapper. Useful when the XSLT produces a wrapper element that should not appear in the final output.

## How It Works

1. Inner custom content tags (elements with a dash in their name) are expanded first, unless `preprocess="false"` is set.
2. The inner content of `<transform-content>` is wrapped in a `<void>` root element and passed as the source XML to the XSLT processor. The `<transform-content>` element itself is **not** included — only its children inside `<void>`.
3. The XSLT stylesheet from the `xslt` attribute is loaded and applied.
4. The transformation result replaces the original `<transform-content>` element in the page output. If `strip-root` is not set and the result root element is `<void>`, it is automatically stripped (only its children are inserted). If `strip-root="true"`, the root element is always stripped. If `strip-root="false"`, the full result including the root element is always inserted.

If the stylesheet is missing or the transformation fails, an error message is rendered in place of the output.

## Example

Given this XSLT file at `module://zolinga-cms/templates/example.xslt`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" omit-xml-declaration="yes"/>
    <xsl:template match="void">
        <ul>
            <xsl:for-each select="item">
                <li><xsl:value-of select="."/></li>
            </xsl:for-each>
        </ul>
    </xsl:template>
</xsl:stylesheet>
```

And this CMS page content:

```html
<transform-content xslt="module://zolinga-cms/templates/example.xslt">
    <item>First</item>
    <item>Second</item>
    <item>Third</item>
</transform-content>
```

The output will be:

```html
<ul>
    <li>First</li>
    <li>Second</li>
    <li>Third</li>
</ul>
```

## Error Handling

If the `xslt` attribute is missing, the file does not exist, or the transformation fails, a red error box is displayed in place of the content with a description of the problem.
