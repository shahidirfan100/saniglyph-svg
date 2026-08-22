=== SaniGlyph SVG ===
Contributors: shahidirfan100
Tags: svg, media, security, optimization, block
Requires at least: 6.4
Tested up to: 7.1
Stable tag: 1.1.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Secure SVG uploads with always-on sanitization, safe optimization, Media Library previews, and optional inline rendering.

== Description ==

SaniGlyph SVG enables SVG uploads for selected WordPress roles while keeping sanitization permanently enabled.

SVG files are XML-based and can contain active or externally referenced content. SaniGlyph SVG uses a fail-closed, allowlist-based validation pipeline before an uploaded SVG is stored. Unsafe or unsupported SVGs are rejected rather than silently rewritten. Valid SVGs keep their original fills, strokes, CSS, gradients, XML formatting, and exporter-specific presentation markup.

= Features =

* Always-on SVG sanitization. It cannot be disabled from the settings screen.
* Plain `.svg` uploads only. Compressed `.svgz` uploads are intentionally not enabled.
* Conservative optimization that preserves geometry, text spacing, and title/description accessibility content.
* Role-based upload permissions in addition to WordPress' normal `upload_files` capability; administrators are the secure default for new installs.
* SVG dimensions and previews in the Media Library.
* Optional inline rendering through the `[saniglyph_svg]` shortcode.
* Dynamic Inline SVG block for the block editor.
* No external services, telemetry, tracking, or remote code.

= Inline shortcode =

Enable inline rendering under **Settings > SaniGlyph SVG**, then use:

`[saniglyph_svg id="123"]`

Optional attributes:

`[saniglyph_svg id="123" class="site-logo" width="160" height="48"]`

Inline output is validated again at render time. Valid SVG markup is returned without an additional HTML-oriented rewrite so SVG-specific presentation details remain intact.

== Installation ==

1. Upload the `saniglyph-svg` folder to `/wp-content/plugins/`, or install the ZIP through **Plugins > Add New > Upload Plugin**.
2. Activate **SaniGlyph SVG**.
3. Go to **Settings > SaniGlyph SVG**.
4. Choose which roles may upload SVG files and configure the optional optimization and inline-rendering features.

== Frequently Asked Questions ==

= Why can't I disable sanitization? =

Allowing unsanitized SVG uploads would remove the plugin's main security boundary. Sanitization is therefore always active.

= Does SaniGlyph SVG require the PHP DOM extension? =

Yes. The DOM extension is required for the strict XML sanitizer. If DOM is unavailable, SaniGlyph SVG keeps SVG uploads disabled rather than falling back to weaker pattern-based sanitization.

= Does SaniGlyph SVG support SVGZ files? =

No. The plugin intentionally enables only plain `.svg` uploads so compressed content cannot bypass the normal sanitization path.

= Does optimization change SVG geometry? =

No. The optimizer is intentionally conservative. It does not round coordinates and does not remove `<title>` or `<desc>` accessibility content.

= Can I use SVGs with Elementor or other page builders? =

Uploaded SVG files behave as Media Library attachments. The `[saniglyph_svg]` shortcode can also be used anywhere shortcodes are supported when inline rendering is enabled.

= Does the plugin send data anywhere? =

No. SaniGlyph SVG does not contact external services, collect telemetry, or load remote JavaScript or CSS.

== Screenshots ==

1. Branded settings overview with the current security status and core SVG protections.
2. Rendering and optimization controls, including optional inline SVG output.
3. Role-based SVG upload permissions with administrators selected by default.
4. Inline shortcode quick-use panel and save controls.

== Changelog ==

= 1.1.6 =
* Updated the WordPress.org `Tested up to` header to 7.1 for current-directory compatibility.
* Synchronized plugin, block, readme, dashboard, and release-package metadata at version 1.1.6.
* Re-audited the submission package for WordPress.org metadata, security, privacy, external-service, and packaging requirements.

= 1.1.5 =
* Reworked the dashboard logo for clear rendering at WordPress admin sizes.
* Simplified the shield and SVG path, increased usable logo size, and removed the translucent background tile that reduced contrast.
* Preserved the existing navy, royal-blue, violet, and white brand palette.

= 1.1.4 =
* Added a distinct SaniGlyph brand mark to the plugin settings dashboard without changing the existing color scheme.
* Added WordPress.org icon, banner, and screenshot assets as a separate distribution package.
* Added a separate screenshot-based user guide and release documentation to keep the installed plugin lightweight.
* Updated release metadata and asset versions consistently.

= 1.1.3 =
* Fixed SVG artwork becoming black or losing appearance after upload/inline rendering.
* Changed the security pipeline from destructive cleanup to fail-closed validation: unsafe or unsupported SVGs are rejected, while accepted SVG source is preserved unchanged by the validator.
* Removed the extra HTML-oriented KSES rewrite from the inline-rendering path after strict SVG validation, preventing valid SVG presentation attributes and styles from being normalized away.
* Added common root attributes such as `version` and `baseProfile` to the SVG allowlist.
* Added a regression test against an Adobe Illustrator-exported SVG containing white/off-white and dark inline fills; all 14 style declarations are preserved and the rendered result is pixel-identical before and after the upload pipeline.

= 1.1.2 =
* Fixed SVGs losing their original colors and appearing blank after upload.
* Restored safe support for inline `style` attributes and class-based `<style>` rules used by Illustrator, Figma, Inkscape, and common icon exporters.
* Added a strict CSS property/value allowlist so styling is preserved without allowing remote URLs, executable CSS, imports, or unsafe data schemes.
* Expanded the SVG presentation allowlist for gradients, filters, clipping, masks, markers, text styling, and common filter primitives.
* Preserved local `url(#id)` paint/filter references while continuing to reject external references.

= 1.1.1 =
* Prefixed all settings-view variables and loop variables with the plugin-specific `sgsvg_` prefix to satisfy WordPress Coding Standards `PrefixAllGlobals`.
* No functional behavior changed.

= 1.1.0 =
* Rebranded the pre-release plugin from Pro SVG Manager to SaniGlyph SVG.
* Matched the plugin folder, main file, text domain, block namespace, settings keys, and asset handles to the new identity.
* Replaced short global identifiers with the collision-resistant five-letter `sgsvg_` / `SGSVG_` prefix.
* Rebuilt SVG sanitization around a strict DOM allowlist and fail-closed behavior.
* Added explicit DOM-extension availability checks and an administrator notice; uploads remain disabled when secure parsing is unavailable.
* Made sanitization permanently enabled and removed the unsafe regex fallback.
* Removed SVGZ support to prevent compressed-file sanitization bypasses.
* Blocked DTDs, external entities, event attributes, remote references, and executable URL schemes.
* Added a second sanitization pass after optimization and another security pass for inline rendering.
* Replaced geometry-changing optimization with conservative serialization that preserves meaningful SVG text spacing.
* Preserved SVG title and description elements for accessibility.
* Added validation against WordPress roles and the core upload_files capability, with administrators as the secure default for new installs.
* Reworked SVG dimension detection and Media Library preview metadata.
* Added a functional dynamic Inline SVG block using block.json and moved the pre-release shortcode to the collision-resistant `[saniglyph_svg]` tag.
* Redesigned the settings screen with a responsive, accessible, dependency-free admin interface.
* Replaced jQuery admin behavior with lightweight vanilla JavaScript.
* Removed placeholder URLs, development repository files, cache-flush behavior, and unnecessary rewrite flushing.
* Updated compatibility metadata for WordPress 7.0 and PHP 7.4+.

= 1.0.0 =
* Initial pre-release build as Pro SVG Manager.

== Upgrade Notice ==

= 1.1.6 =
Submission-readiness update: declares testing through WordPress 7.1 and synchronizes release metadata.

= 1.1.5 =
Dashboard branding fix: improves logo scale, contrast, and clarity without changing the plugin interface or functionality.

= 1.1.4 =
Branding and documentation release: adds a lightweight dashboard brand mark plus separate WordPress.org assets and user documentation.

= 1.1.3 =
Visual-fidelity fix: valid SVGs are validated without being destructively rewritten, and inline rendering no longer runs the already-validated SVG through an HTML-oriented KSES pass that can change SVG presentation.

= 1.1.2 =
Rendering compatibility fix: preserves safe SVG colors, CSS classes, gradients, filters, and inline styles that v1.1.x could strip too aggressively.

= 1.1.1 =
Coding-standards patch: fixes non-prefixed global variables in the settings-page view without changing plugin behavior.

= 1.1.0 =
Security and submission-readiness update with a new plugin identity. Existing pre-release settings are migrated when available. The pre-release `[inline_svg]` shortcode is now `[saniglyph_svg]`.
