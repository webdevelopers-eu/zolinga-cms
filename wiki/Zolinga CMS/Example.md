Priority: 0.1

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
