# Changelog

All notable changes to this module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0/).

## [Unreleased]

## [1.7.0] - 2026-11-07

### Added
- New `{{domain}}` content variable. It is replaced with the host name from the `baseURL` configuration, so page content and templates can build absolute links such as `https://{{domain}}/mcp` without hardcoding the domain.

### Fixed
- The language prefix in the published URL of a page no longer keeps a trailing slash for the site root.
- The `<html-to-markdown>` and `<markdown-to-html>` elements now use the `markdown` service.

## [1.6.6] - 2026-08-29

### Fixed
- Menu cache now flushes when a page file is newer than the cached tree. Previously the flush ran during `$api->cmsTree` construction and never deleted the stale files, so every request logged a warning and kept serving the old menu.

## [1.6.5] - 2026-05-09

### Fixed
- Language redirection now uses the original (unrewritten) path to correctly detect and add/remove language prefixes.
- Improved status messages in page serving to include the requested path for better debugging.
- HTML parsing now prepends UTF-8 BOM to ensure correct encoding when loading HTML files.
- Updated namespace imports in `Page.php` to use fully-qualified class names.

### Added
- Documentation for the `<void>` content element.

## [1.6.4] - 2025-05-08