=== IM8 Additional CSS ===
Contributors: intermedi8
Donate link: http://intermedi8.de
Tags: css, styles, post, page, custom post type, additional
Requires at least: 2.9.2
Tested up to: 3.8
Stable tag: trunk
License: MIT
License URI: http://opensource.org/licenses/MIT

Add an additional CSS file and/or CSS styles for each page or (custom) post.

== Description ==

**Add an additional CSS file and/or CSS styles for each page or (custom) post.**

* Easy-to-use due to a new _Additional CSS_ meta box on the regular edit page in the WordPress-Admin
* Multilanguage: currently english and german (please help us with translations if you want to see additional languages)
* Ad-free (of course, donations are welcome)

If you would like to **contribute** to this plugin, see its <a href="https://github.com/intermedi8/im8-additional-css" target="_blank">**GitHub repository**</a>.

== Installation ==

1. Upload the `im8-additional-css` folder to the `/wp-content/plugins` directory on your web server.
2. Activate the plugin through the _Plugins_ menu in WordPress.
3. Find the new _Additional CSS_ meta box on the regular edit page

== Screenshots ==

1. **Additional CSS meta box** - Here you can add an additional CSS file and/or CSS styles.

== Changelog ==

= 2.5 =
* integrated plugin update message
* corrected some DocBlocks

= 2.4 =
* fixed bug that prevented plugin from being loaded when activated network-wide

= 2.3 =
* added direct access guard
* removed trailing `?>`
* in `get_post_types` function, changed argument `'public' => true` to `'show_ui' => true`

= 2.2 =
* Compatible up to WordPress 3.8

= 2.1 =
* Bugfix in `autoupdate` routine
* wrapped plugin in `if (! class_exists('IM8AdditionalCSS'))`
* optimized `uninstall` routine

= 2.0 =
* Complete refactoring (object oriented programming)
* More usage of WordPress core functions
* Moved screenshot to `assets` folder
* Added banner image

= 1.1 =
* Fixed an error message when CSS directory is not available (credits go to Christian Gnos from dunkelweiss GmbH for reporting this bug)

= 1.0 =
* Initial release