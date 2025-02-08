Priority: 0.8

# Custom Elements

The CMS supports custom dynamic elements. You can define any custom HTML element. When CMS parser sees the element it will dispatch an *internal* event [cms:content:*](:ref:event:cms:content:*) with the element in `$event->input` and will expect any Listener to return the content to replace the element in `$event->output`. 

## Example

[Listener](:Zolinga Core:Events and Listeners) setup in `zolinga.json`:

```json
{
    "listen": [
        {
            "description": "My custom element <my-element> processor",
            "event": "cms:content:my-element",
            "class": "\\Example\\MyDynamicElement",
            "method": "onMyElement",
            "origin": ["internal"]
        }
    ]
}
```

Listener implementation

```php
namespace Example;
use \Zolinga\System\Events\ListenerInterface;
use \Zolinga\Cms\Events\ContentElementEvent;

class MyDynamicElement implements ListenerInterface
{
    public function onMyElement(ContentElementEvent $event)
    {
        global $api;

        /** @var \Zolinga\Cms\Page $page */
        $url = $api->cms->currentPage->urlPath;

        /** @var \DOMElement $event->input */
        $name = $event->input->getAttribute('name') ?: 'Unknown';

        /** @var \DOMDocumentFragment $event->output */
        $event->output->appendXML("<div><h1>Hello $name!</h1> <small>URL: $url</small></div>");

        $event->setStatus($event::STATUS_OK, 'Served!');
    }
}
```

Page file `data/zolinga-cms/pages/about/index.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>About Us</title>
</head>
<body>
    <my-element name="Johny"></my-element>
</body>
</html>
```

That's it. Easy, isn't it?

## Client-side Rendering

Any custom element with attribute `render="client"` will be ignored by CMS. This is useful for elements that need to be processed by JavaScript as [Web Components](:ref:Zolinga Core:Web Components). But if you forget to add the attribute to the element nothing will break. CMS will try to pass it to Listeners as usual and since no Listener will be found it will just ignore it anyway.