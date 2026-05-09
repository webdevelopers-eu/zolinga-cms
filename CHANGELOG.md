# Changelog

All notable changes to this module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0/).

## [Unreleased]

## [1.6.5] - 2026-05-09

### Fixed
- Language redirection now uses the original (unrewritten) path to correctly detect and add/remove language prefixes.
- Improved status messages in page serving to include the requested path for better debugging.
- HTML parsing now prepends UTF-8 BOM to ensure correct encoding when loading HTML files.
- Updated namespace imports in `Page.php` to use fully-qualified class names.

### Added
- Documentation for the `<void>` content element.

## [1.6.4] - 2025-05-08