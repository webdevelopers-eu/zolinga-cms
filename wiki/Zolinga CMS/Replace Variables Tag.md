# Replace Variables Tag

The `<replace-vars>` tag allows you to replace variable placeholders in the content with actual values from GET and POST parameters.

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

- `{{GET:parameter_name}}`: Will be replaced with the value of the GET parameter with the specified name
- `{{POST:parameter_name}}`: Will be replaced with the value of the POST parameter with the specified name

## Features

- Replacements are performed in all text nodes and attribute values within the `<replace-vars>` tag
- If a variable doesn't exist, it will be replaced with an empty string

## Example

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

## Security

All variable replacements are automatically HTML-escaped to prevent XSS attacks. If you need to use raw unescaped values (not recommended), you should implement a custom tag handler.
