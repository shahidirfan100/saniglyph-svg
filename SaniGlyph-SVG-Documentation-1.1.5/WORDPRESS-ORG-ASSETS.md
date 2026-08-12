# WordPress.org Asset Publishing Guide

**Release package:** SaniGlyph SVG 1.1.5  
**Archive:** `SaniGlyph-SVG-WordPress.org-Assets-1.1.5.zip`

These files belong in the top-level `assets` directory of the plugin's WordPress.org SVN repository. Do not place them inside `trunk`, a version tag, or the installable plugin ZIP.

## Included directory assets

| File | Purpose | Dimensions |
| --- | --- | ---: |
| `icon-128x128.png` | Standard directory/plugin icon | 128 × 128 px |
| `icon-256x256.png` | High-DPI directory/plugin icon | 256 × 256 px |
| `banner-772x250.png` | Standard directory banner | 772 × 250 px |
| `banner-1544x500.png` | High-DPI directory banner | 1544 × 500 px |
| `screenshot-1.png` | Full settings overview | 1600 × 1160 px |
| `screenshot-2.png` | Rendering and optimization | 930 × 285 px |
| `screenshot-3.png` | Role permissions | 930 × 310 px |
| `screenshot-4.png` | Shortcode and save controls | 460 × 580 px |

`logo-master-512x512.png` is a reusable brand master. It is not a required WordPress.org filename.

## Recommended SVN layout

```text
saniglyph-svg/
├── assets/
│   ├── banner-772x250.png
│   ├── banner-1544x500.png
│   ├── icon-128x128.png
│   ├── icon-256x256.png
│   ├── screenshot-1.png
│   ├── screenshot-2.png
│   ├── screenshot-3.png
│   └── screenshot-4.png
├── trunk/
└── tags/
```

The screenshot numbering matches the `== Screenshots ==` captions in the plugin's `readme.txt`. Keep all filenames lowercase. WordPress.org caches these files heavily, so an updated image may take time to appear after committing it.

The 1.1.5 logo is the simplified shield with exactly two large circular nodes and no translucent background tile. If a logo has three nodes or appears inside a translucent square, it is the superseded 1.1.4 artwork.
