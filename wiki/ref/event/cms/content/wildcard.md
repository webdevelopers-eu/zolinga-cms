## Description

Wildcard event for CMS content element processing. When the CMS parser encounters a custom HTML element in page content, it dispatches `cms:content:{tagName}` with the element context.

- **Event:** `cms:content:*`
- **Emitted by:** `Zolinga\Cms\ContentParser`
- **Event Type:** `\Zolinga\Cms\Events\ContentElementEvent`
- **Origin:** `internal`

## Behavior

The CMS content parser walks through all HTML elements in the page DOM. For each custom element (non-standard HTML tag), it dispatches `cms:content:{tagName}` as an internal event. Listeners registered in `zolinga.json` handle their respective tags by manipulating the DOM via the `ContentElementEvent`.

## ContentElementEvent Properties

| Property | Type | Description |
|---|---|---|
| `element` | `DOMElement` | The DOM element to process |
| `contentText` | `string` | Text content of the element |

## Creating a Handler

1. Register a listener in your module's `zolinga.json`:

```json
{
    "event": "cms:content:my-tag",
    "class": "MyModule\\MyListener",
    "method": "onMyTag",
    "origin": ["internal"]
}
```

2. Implement the handler:

```php
public function onMyTag(ContentElementEvent $event): void
{
    $el = $event->element;
    // Manipulate the DOM
    $event->setStatus($event::STATUS_OK, "Processed");
}
```

## See Also

- Zolinga CMS / Custom Elements documentation
