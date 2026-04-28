# Replace Variables Tag

The `<replace-vars>` tag allows you to replace variable placeholders in the content with actual values from GET parameters, POST parameters, or custom variables set programmatically.

## Usage

Wrap any content that contains variable placeholders with the `<replace-vars>` tag:

```html
<replace-vars>
    <div>
        <h2>Search Results for: {{GET:search}}</h2>
        <p>You submitted: {{POST:message}}</p>
    </div>
</replace-vars>
```

## Variable Syntax

The tag supports the following placeholder formats:

- `\{{GET:parameter_name}}`: Will be replaced with the value of the GET parameter with the specified name
- `\{{POST:parameter_name}}`: Will be replaced with the value of the POST parameter with the specified name
- `\{{VAR:variable_name}}`: Will be replaced with a custom variable set programmatically via `$api->replaceVars->set('variable_name', 'value')`

## Features

- Replacements are performed in all text nodes and attribute values within the `<replace-vars>` tag
- Variables are HTML-escaped to prevent XSS attacks
- If a variable doesn't exist and no default is given, the original placeholder is preserved as-is

## Examples

### GET and POST parameters

```html
<replace-vars>
    <div class="search-container" data-query="{{GET:q}}">
        <h2>Search Results for: {{GET:q}}</h2>
        <p>Page: {{GET:page}}</p>
        
        <div class="filter">
            <span>Filter: {{GET:filter}}</span>
        </div>
        
        <div class="form-feedback">
            <p>You submitted: {{POST:message}}</p>
            <p>Category: {{POST:category}}</p>
        </div>
    </div>
</replace-vars>
```

### Custom variables (VAR)

Any module can register custom variables accessible via `{{VAR:...}}`:

```php
// In any PHP code (e.g. event listener, service)
global $api;
$api->replaceVars->set('username', 'Alice');
$api->replaceVars->set('greeting', 'Welcome back');
```

Then in CMS content:

```html
<replace-vars>
    <p>{{VAR:greeting}}, {{VAR:username|dear visitor}}!</p>
</replace-vars>
```

Output when `username` is set:

```html
<p>Welcome back, Alice!</p>
```

Output when `username` is not set (falls back to default):

```html
<p>Welcome back, dear visitor!</p>
```

## Security

All variable replacements are automatically HTML-escaped to prevent XSS attacks. If you need to use raw unescaped values (not recommended), you should implement a custom tag handler.
