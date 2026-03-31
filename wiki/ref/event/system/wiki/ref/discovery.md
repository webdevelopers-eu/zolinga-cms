## Description

Internal event dispatched to discover child articles for WIKI reference pages. Allows modules to contribute articles to the `:ref` discovery system.

- **Event:** `system:wiki:ref:discovery`
- **Listeners:**
  - `Zolinga\Cms\WikiCmsElements` — adds CMS Elements articles
- **Origin:** `internal`
- **Event Type:** `\Zolinga\System\Wiki\Events\WikiRefIntegrationEvent`

## Behavior

When a WIKI `:ref` page is rendered, this event is dispatched so modules can register their reference documentation entries (e.g., CMS custom elements, event reference pages).
