# Shuffling the Content

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
> Moves all the child elements of the element after the element with the specified ID. The empty element is then removed.