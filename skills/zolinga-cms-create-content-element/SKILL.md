---
name: zolinga-cms-create-content-element
description: Use when creating or updating a Zolinga CMS content element listener for cms:content:{tagName}, including ContentElementEvent usage, XPath querying, output fragment handling, and manifest wiring.
argument-hint: "<tag-name> [module]"
---

# Zolinga CMS Create Content Element

## Use When

- Creating a new `cms:content:{tagName}` listener.
- Updating an existing CMS content element handler.
- Reviewing code that transforms a custom HTML element into server-rendered output.
- Explaining how `ContentElementEvent` should be used in Zolinga CMS.

## Workflow

1. Register the handler in module `zolinga.json` under `listen` with:
   - `event`: `cms:content:{tagName}`
   - `origin`: `internal`
   - `class`: listener class name
   - `method`: public handler method
2. Implement a listener class that implements `Zolinga\System\Events\ListenerInterface`.
3. Accept `Zolinga\Cms\Events\ContentElementEvent $event` in the handler method.
4. Read source markup strictly from `$event->input`.
5. Build rendered output strictly in `$event->output`.
6. Use `$event->inputXPath` when querying the source element or its descendants.
7. Use `$event->outputXPath` only when you need to query nodes already appended into the output document.
8. Finish by calling `$event->setStatus(...)` with a `STATUS_*` constant. If status is `STATUS_OK, the original element is replaced by the output fragment. If STATUS_ERROR, the parser treats it as an error and both the original element and the output fragment are discarded and `<content-error>` is inserted instead.

## Basic ContentElementEvent API

Strictly use these properties:

- `$event->input` — `DOMElement`
  - The cloned custom element being processed.
  - Read attributes with `$event->input->getAttribute(...)`.
  - Query descendants relative to it with `$event->inputXPath->query($xpath, $event->input)`.
- `$event->output` — `DOMDocumentFragment`
  - The rendered replacement fragment.
  - Append generated elements, imported nodes, text nodes, or parsed XML into it.
  - The CMS parser inserts its child nodes before the original element and then removes the original element when status is OK.
- `$event->inputXPath` — `DOMXPath`
  - Cached XPath bound to the input document.
  - Pre-registers the `cms` namespace.
  - Best for reading or selecting nodes from `$event->input`.
- `$event->outputXPath` — `DOMXPath`
  - Cached XPath for the same owner document.
  - Useful when you first append nodes to output and then need to query or post-process them.

Do not use ad-hoc property names such as `$event->inputElement` or `$event->outputElement`.

## How the Event Is Processed

The CMS parser clones the original custom element and dispatches `cms:content:{tagName}` with that clone as `$event->input`.

Important consequences:

- Mutating `$event->input` is strictly forbidden. It is a clone of the original element and any changes to it are lost. Always build output in `$event->output`.
- The only supported way to replace the element is to append rendered nodes into `$event->output` and report `STATUS_OK`.
- When the listener returns `STATUS_OK`, the parser inserts `$event->output` children into the real DOM and removes the original custom element.
- If output contains nested custom elements, the parser processes them later in a follow-up pass.

## Common Pattern

1. Read attributes from `$event->input`.
2. Query matching nodes from the input subtree with `$event->inputXPath`.
4. Generate output by appending new elements, text nodes, or imported clones of input nodes into `$event->output`.
5. Set an OK or error status. Error status is set when something went horribly wrong. The common practice is to set `STATUS_OK` when output is ready and the output can be some custom error message. The `status` is just to indicate that element is processed and whether it was success or failure does not matter to the parser. Parser only cares if it should replace the original element with output (OK) or discard both (ERROR).

## Output Patterns

Typical output operations:

- Append cloned existing nodes:
  - `$event->output->appendChild($event->output->ownerDocument->importNode($node->cloneNode(true), true));`
- Append new elements:
  - `$element = $event->output->ownerDocument->createElement('div');`
  - `$event->output->appendChild($element);`
- Append text nodes:
  - `$text = $event->output->ownerDocument->createTextNode('Hello');`
  - `$event->output->appendChild($text);`
- Append mixed text and nodes in one call when convenient:
  - `$event->output->append(', ', $event->output->ownerDocument->importNode($node->cloneNode(true), true));`
- Append parsed XML/HTML fragments carefully when markup is trusted:
  - `$event->output->appendXML('<span>...</span>');`

## XPath Guidance

- Prefer relative XPath expressions when querying inside the current element.
- Pass `$event->input` as the XPath context node.
- Expect XPath to return mixed node types when expressions target text, attributes, comments, or processing instructions.
- Filter node types explicitly only when the handler truly needs a narrower subset. Some handlers, such as `random-chooser`, intentionally accept text nodes too.
- Use `iterator_to_array()` if result ordering or randomization matters.

## Status Handling

- Use `$event::STATUS_OK` when output is ready.
- Use `$event::STATUS_ERROR` when rendering failed and the parser should treat it as an error.
- Only OK causes the original custom element to be replaced by output nodes.
- If you intentionally want the element to remain for client rendering, do not report OK.

## Common Pitfalls

- Writing to `$event->input` instead of `$event->output`.
- Using nonexistent properties like `$event->inputElement` or `$event->outputElement`.
- Forgetting that `$event->input` is a clone, not the live DOM node from the page.
- Appending nodes from another document without `importNode(...)` when needed.
- Assuming XPath results are always `DOMElement` instances.
- Forgetting to set a status.
- Registering the handler with the wrong origin in `zolinga.json`.

## References

- `modules/zolinga-cms/src/Events/ContentElementEvent.php`
- `modules/zolinga-cms/src/ContentParser.php`
- `modules/zolinga-cms/src/RandomChooserListener.php`
- `modules/zolinga-cms/wiki/ref/event/cms/content/wildcard.md`
- `modules/zolinga-cms/zolinga.json`