# CMS Menu

The custom tag `<cms-menu>` is used to render a menu.

## Syntax

```
<cms-menu 
    [id="{id}"] 
    [class="{class}"]
    [level="{level}"]
    [depth="{depth}"]
    >
    [ <menu>{append items}</menu> ]    
</cms-menu>
```

Tip: To reorder the extra items, use the CSS [order](https://developer.mozilla.org/en-US/docs/Web/CSS/order) property.


### Attributes

- `id="{id}"`
> Will be copied on the output element as is.
- `class="{class}"`
> Will be copied on the output element as is.
- `level="{level}"`
> The level of the menu to render. Default is 1.
- `depth="{depth}"`
> The depth of the<cms-menu  menu to render. Default is 1.
- `{append items}` if present, will be used to copy items to the generated `<menu>` element.

## Example

```html
<cms-menu id="my-menu" class="menu" level="2" depth="2"></cms-menu>
```

```html
<cms-menu id="my-menu" class="menu" level="2" depth="2">
    <menu>
        <li><a href="#">Extra Item 1</a></li>
        <li><a href="#">Extra Item 2</a></li>
    </menu>
</cms-menu>
```