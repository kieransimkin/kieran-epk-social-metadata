=== Kieran EPK Social Metadata ===
Contributors: kieransimkin0
Tags: open graph, schema, music
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.2.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Store page facts once for Open Graph, X cards and music or webpage Schema.org metadata.

== Description ==

Store title, description, artwork, public audio and verified video facts once. Generate social sharing tags and MusicRecording, MusicAlbum or WebPage JSON-LD. WordPress retains ownership of titles, canonical URLs and robots directives. Output is opt-in per page; activation does not import data or change page bodies.

Bundled catalogue importers contain site-specific example records: review exact page IDs, permalinks and public assets before explicitly running an import. These records are not a general template for other sites. Keywords are descriptive only and do not promise rankings.

External services: the plugin itself makes no external HTTP requests. Configured public media/player URLs may be loaded by browsers and sharing crawlers. A YouTube or other third-party iframe sends the visitor to that configured provider when embedded; the site owner must obtain appropriate consent before embedding third-party media. No provider is enabled automatically.

Source and support: https://github.com/kieransimkin/kieran-epk-social-metadata
Author and ecosystem: https://kieransimkin.co.uk/ and https://kieransimkin.co.uk/danceflow/

== Installation ==

1. Install the released ZIP or the approved directory listing.
2. Activate the plugin.
3. Review the source README and configure only the page features required.
4. Check the public result, keyboard use, preferences and duplicate metadata owners.

== Frequently Asked Questions ==

= Is an account or paid service required? =

No. The plugin is independent and does not require an external account.

= Does installation guarantee a search or sharing result? =

No. Search engines and sharing services decide how to present a page.

== Changelog ==

= 1.2.2 =

* Prepare directory readme, headers and production-package checks.
* Address the reviewed Plugin Check findings while preserving runtime behavior.

== Upgrade Notice ==

= 1.2.2 =

Directory packaging and compatibility corrections. Review documentation before activating optional features.
