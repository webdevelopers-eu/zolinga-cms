Priority: 0.82

# Defining a Page Tree

The pages are stored in [private module folder](:Zolinga Core:Module Anatomy) `data/zolinga-cms/pages`. The structure is as you would expect from normal static pages. The root of the page tree is the `index.html` file. The pages are stored in folders and the folder structure defines the page tree.

For example the URL `/about/team` would be stored in the file `data/zolinga-cms/pages/about/team/index.html` or `data/zolinga-cms/pages/about/team.html`.

## Tree Cache

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
- `<link rel="canonical" href="{url}"/>` or `<meta name="cms.canonical" content="{url|javascript:...}"/>`
> Defines custom URL of the menu link. By default the canonicalized file path is used.
