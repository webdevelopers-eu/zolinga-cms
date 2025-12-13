# Content Variables

You can use following variables in both your template and content `.html` files:

- `\{\{designPath}}`
> It will be replaced with the path to the design folder. This is useful for referencing assets in your design folder.
- `\{\{locale}}`
> It will be replaced with the current locale. E.g. 'en-US'
- `\{\{lang}}`
> It will be replaced with the current language. E.g. 'en'
- `\{\{revHash}}`
> It will be replaced with the current revision hash caclulated from all zolinga.json files. Useful for cache busting of assets. If you change any zolinga.json file the hash will change.

E.g. if you design is `example/main` then the variable will be replaced with `/data/zolinga-cms/designs/example`.

Example:

```html
<link rel="stylesheet" href="{{designPath}}/style.css"/>
...
<a href="/{{lang}}/register" gettext=".">Register Now!</a>
```
