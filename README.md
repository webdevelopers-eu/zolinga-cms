**This repository holds the  [Zolinga](https://github.com/webdevelopers-eu/zolinga) module.**

Please refer to [Zolinga PHP Framework](https://github.com/webdevelopers-eu/zolinga) for more information. Also refer to inbuilt [Zolinga WIKI](https://github.com/webdevelopers-eu/zolinga/blob/main/system/data/zolinga-wiki-screenshot.png) that comes with the your Zolinga framework installation for most up-to-date information.

# Zolinga CMS

Note: This is a fresh module and may need additional testing. Please report any issues you find.

## Introduction

Zolinga CMS is a PHP database-less content management system that is designed to be easy to use and easy to extend.

Features:

- No database required
- Uses familiar static-HTML file structure
- Support for auto-generated menu
- Support for expanding dynamic custom elements. E.g. `<cms-menu depth="2"></cms-menu>`
- Support for translating content ([Zolinga Intl](https://github.com/webdevelopers-eu/zolinga-intl) module required)
- Support for templates
- And much more...

## But... why?

In the realm of modern web applications, traditional CMS systems are beginning to lose their significance. Robust CMS platforms are being supplanted by single-page applications boasting dynamic interfaces, components, and streamlined backend content databases.

This module responds to the evolving landscape. For the project I developed it for, the need for a conventional CMS is virtually non-existent. The primary component is a single-page, fully-fledged administration interface that operates independently of traditional content storage methods commonly associated with full-blown CMS platforms. As a result, I sought something simple yet powerful to bootstrap all frontend elements required. Something traditional, devoid of unnecessary features, primarily for raw SEO purposes, customer-targeted design features, and other eye candies, terms and conditions, and other static textual content in multiple languages. The intended use is also for mass-managed microsites.

It is a hybrid between a plain static HTML files and a fullblown database-driven CMS. It is designed to be easy to use and easy to extend. Able to support simplified page templates and dynamic elements.

The use cases are endless. You can use it for a simple blog, a small different-design microsites that have content updated
using FTP, a personal site with custom contact form written in PHP...

Really endless. It is up to you. I just provide the shovel. You find the gold.

## Installation

First you need to install the Zolinga framework (1 minute work under ideal conditions). See [Zolinga PHP Framework](https://github.com/webdevelopers-eu/zolinga). Then you can install the Zolinga CMS module by running this command in the root of your Zolinga installation:

```bash
$ ./bin/zolinga install --module=zolinga-cms
```

## Defining a Page Tree

The pages are stored in [private module folder](:Zolinga Core:Module Anatomy) `data/zolinga-cms/pages`. The structure is as you would expect from normal static pages. The root of the page tree is the `index.html` file. The pages are stored in folders and the folder structure defines the page tree.

For example the URL `/about/team` would be stored in the file `data/zolinga-cms/pages/about/team/index.html` or `data/zolinga-cms/pages/about/team.html`.

### Tree Cache

The system creates a cache of the page tree in the `{root}/data/zolinga-cms/menu.cache.*.json` files. The cache will be automatically updated when the *current* page change is detected. Since this detection is performed only for currently visited pages, you may need to manually clear the cache if you change the page tree structure. You can do this by removing the cache files manually.

During development you can disable the cache by setting the `cache` option to `false` in the `{root}/config/local.json` file.

```json
{
    "cms": {
      "menuCache": true
    }
}
```

## Controling the Menu

The `.html` file can contain following meta data that affects the menu:

- `<meta name="cms.template" content="{design}/{layout}"/>` or `<meta name="cms.template" content="public://{module}/{path}"/>` or `<meta name="cms.template" content="dist://{module}/{path}"/>`
> Defines the template to use for the page. For details see bellow.
- `<meta name="cms.priority" content="{priority}"/>`s
> Defines the priority of the page in the menu. The lower the number lower in the menu the page will be among its siblings.
- `<meta name="cms.title" content="{title}"/>`, `<meta name="DC.Title" content="{title}"/>`, `<title>{title}</title>`
> Defines the title of the page in the menu. The listed order is the priority order of the title.
- `<meta name="cms.description" content="{description}"/>`, `<meta name="DC.Description" content="{description}"/>`, `<meta name="description" content="{description}"/>`
> Defines the description of the page in the menu. The listed order is the priority order of the description.
- `<meta name="cms.visibility" content="visible|hidden"/>`
> Optionally hides the page from the menu. Default: "visible"
- `<meta name="cms.class" content="{class class ...}"/>`
> Optionally adds CSS classes to the menu item. You can use classes for more complex control of menu visibility that you can control through CSS or JavaScript.
- `<link rel="canonical" href="{url}"/>`
> Defines custom URL of the menu link. By default the canonicalized file path is used.

## Templates

Each `.html` file may specify a template to use. 

```html
<meta name="cms.template" content="{design}/{layout}"/>
```

The system is expected to find the `public/data/zolinga-cms/templates/{design}/{layout}.html` file with `<cms-content></cms-content>` tag where the HTML content of the page will be inserted. Don't worry `<head>` sections of both files will be merged.

or

```html
<meta name="cms.template" content="public://{module}/{path}"/>
```

If the `cms.template` starts with `public://` or `dist://` the system will expect the [Zolinga URI path](:Zolinga Core:Paths and Zolinga URI) to the template HTML file. Example: `public://zolinga-cms/templates/example/main.html`.

## Shuffling the Content

It is common that you don't want all the content to end up in the `<cms-content></cms-content>` element in the template. You may want to insert something in the content, something in the header and something in the footer. You can do this by using a group of powerful attributes on any element.

- `append-to="{id}"`
> Appends the element to the element with the specified ID.
- `append-contents-to="{id}"`
> Appends all the child elements of the element to the element with the specified ID and then removes the element.
- `prepend-to="{id}"`
> Prepends the element to the element with the specified ID.
- `prepend-contents-to="{id}"`
> Prepends all the child elements of the element to the element with the specified ID and then removes the element.
- `replace="{id}"`
> Replaces the element with the specified ID.
- `replace-with-contents="{id}"`
> Replaces the element with the specified ID with all the child elements of the element. The empty element is then removed.
- `replace-contents="{id}"`
> Replaces all the child elements of the element with the specified ID.
- `replace-contents-with-contents="{id}"`
> Replaces all the child elements of the element with the specified ID with all the child elements of the element. The empty element is then removed.
- `move-before="{id}"`
> Moves the element before the element with the specified ID.
- `move-contents-before="{id}"`
> Moves all the child elements of the element before the element with the specified ID. The empty element is then removed.
- `move-after="{id}"`
> Moves the element after the element with the specified ID.
- `move-contents-after="{id}"`
- Moves all the child elements of the element after the element with the specified ID. The empty element is then removed.

### Example

Template file `public/data/zolinga-cms/templates/example/main.html`:

```html
<!DOCTYPE html>
<html>
<head>
</head>
<body>
    <header id="my-header"></header>

    <main>
        <cms-content></cms-content>
    </main>

    <footer id="my-footer"></footer>
</body>
</html>
```

Page file `data/zolinga-cms/pages/about/index.html`:

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
    <p>Some more content...</p>
    
    <footer replace="my-footer">
        My completely custom footer.
    </footer>
</body>
</html>
```

## Custom Elements

The CMS supports custom dynamic elements. You can define any custom HTML element. When CMS parser sees the element it will dispatch an *internal* event [cms:content:*](:ref:event:cms:content:*) with the element in `$event->input` and will expect any Listener to return the content to replace the element in `$event->output`. 

### Example

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

### Client-side Rendering

Any custom element with attribute `render="client"` will be ignored by CMS. This is useful for elements that need to be processed by JavaScript as [Web Components](:ref:Zolinga Core:Web Components). But if you forget to add the attribute to the element nothing will break. CMS will try to pass it to Listeners as usual and since no Listener will be found it will just ignore it anyway.

## Supported Dynamic Elements

See [the full list of dynamic elements](:ref:cms).

## Variables

You can use following variables in both your template and content `.html` files:

- `\{\{designPath}}`
> It will be replaced with the path to the design folder. This is useful for referencing assets in your design folder.
- `\{\{locale}}`
> It will be replaced with the current locale. E.g. 'en-US'
- `\{\{lang}}`
> It will be replaced with the current language. E.g. 'en'

E.g. if you design is `example/main` then the variable will be replaced with `/data/zolinga-cms/designs/example`.

Example:

```html
<link rel="stylesheet" href="{{designPath}}/style.css"/>
...
<a href="/{{lang}}/register" gettext=".">Register Now!</a>
```

## Translating Content

The CMS is able to cooperate with [Zolinga Intl](https://github.com/webdevelopers-eu/zolinga-intl) module to provide full translation support. Refere to the Zolinga Intl documentation for more information.

# Related

- [List of CMS Elements](:ref:cms)

