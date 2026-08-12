# SaniGlyph SVG 1.1.5 Release Notes

## Changes

- Rebuilt the dashboard brand mark for clear rendering at small WordPress admin sizes.
- Simplified the shield and SVG path to two large nodes with thicker, cleaner shapes.
- Removed the translucent logo tile and increased the mark's usable size and contrast.
- Updated the WordPress.org icons and banner to use the same simplified mark.
- Preserved the existing navy `#18264e`, royal blue `#3157d5`, and violet `#7145d7` visual palette.
- Added standard and high-DPI WordPress.org icons and banners as a separate asset package.
- Added four WordPress.org screenshots and matching `readme.txt` captions.
- Added a separate screenshot-based user guide and publishing guide.
- Synchronized the plugin header, `SGSVG_VERSION`, block metadata, block asset metadata, stable tag, changelog, and upgrade notice at version 1.1.5.

## Packaging decision

Only the optimized 88 × 88 px dashboard logo is included in the installable plugin. WordPress.org banners, directory icons, screenshots, and user documentation are distributed separately so the plugin remains lightweight.

The documentation archive and its screenshot paths include `1.1.5` in their names so an older extracted folder or cached download cannot silently supply 1.1.4 images.
