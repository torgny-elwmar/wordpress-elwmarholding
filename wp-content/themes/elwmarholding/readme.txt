Elwmar Holding AB
==================

Contributors: elwmarholding
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 3.0.3
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A professional, long-lived WordPress Full-Site-Editing theme for elwmarholding.se.

== Description ==

Elwmar Holding AB is a modern block theme built for longevity. It features:

* The Elwmar Holding palette: charcoal green, deep charcoal, taupe, walnut and chalk
* Fluid typography using Inter for structure, Fraunces for voice and Yellowtail for signature details
* The official Elwmar Holding mark in the header, footer and subtle page watermark
* Full-Site Editing with template parts for Header and Footer
* Templates for front-page, single posts, pages, archives, search, and 404
* Block patterns for hero sections and feature grids
* Responsive, accessible design with sticky header and smooth transitions
* Optimised for performance with minimal CSS output
* Translation-ready PHP, JavaScript, block templates and template parts
* Editable WordPress block navigation and Polylang-compatible language selector

== Translations ==

The theme uses the `elwmarholding` text domain and stores translation files in `/languages`.
Use `languages/elwmarholding.pot` as the source catalog when creating a new translation.
Editorial page and post content is managed separately by WordPress and requires translated content, typically through a multilingual plugin.
The header language selector uses Polylang and appears when at least two languages have been configured.

== Installation ==

1. Upload the `elwmarholding` folder to `/wp-content/themes/`
2. Activate the theme via **Appearance → Themes**
3. Customise via the **Site Editor** (**Appearance → Editor**)
4. Create and maintain pages, posts, navigation and site settings in WordPress; the theme contains presentation only

== Changelog ==

= 3.0.3 =
* Restored the blurred navigation backdrop while keeping the drawer panel opaque.
* Normalised page backgrounds and aligned the home-page perspective section.
* Added reusable styles for the bilingual news index and home-page headlines.
* Simplified the footer copyright line.

= 3.0.2 =
* Removed the conflicting drawer fade and blur layers.
* Added deployable Swedish and English language flag fallbacks.
* Replaced the footer logo with database-backed company details.

= 3.0.1 =
* Restored the always-visible hamburger navigation and slide-out menu.
* Added separate bundled assets for the browser icon and full website logo.
* Added a branded WordPress theme preview image.

= 3.0.0 =
* Applied the complete Elwmar Holding visual identity across global styles and reusable components.
* Added the official brand mark and responsive header/footer lockups.
* Kept page and post content database-driven for the production-first content workflow.

= 2.2.5 =
* Rendered language and footer controls dynamically so cached patterns cannot stale their database state.

= 2.2.4 =
* Refined the active-language marker and disabled links to missing translations.
* Prevented secondary languages without a translated front page from falling through to the posts index.
* Added Polylang as a source-controlled project plugin.

= 2.2.3 =
* Replaced language abbreviations with accessible Polylang flag icons.

= 2.2.2 =
* Replaced embedded header links with a persistent, editable WordPress navigation.
* Added a direct Appearance → Huvudmeny admin entry.
* Added an accessible Polylang language selector with responsive header styling.

= 2.1.0 =
* Added complete theme internationalisation for PHP and JavaScript.
* Moved translatable block-template text into PHP-based theme patterns.
* Added the `elwmarholding.pot` translation catalog.
* Let WordPress control the document language instead of forcing `sv-SE` in theme code.

= 1.0.0 =
* Initial release.

== Copyright ==

Elwmar Holding AB Theme, 2024 Elwmar Holding AB
Elwmar Holding AB Theme is distributed under the terms of the GNU GPL.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

Fonts used:
* Inter — SIL Open Font License 1.1 — https://fonts.google.com/specimen/Inter
* Fraunces — SIL Open Font License 1.1 — https://fonts.google.com/specimen/Fraunces
* Yellowtail — SIL Open Font License 1.1 — https://fonts.google.com/specimen/Yellowtail
