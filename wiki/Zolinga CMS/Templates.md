Priority: 0.81

# Templates

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
