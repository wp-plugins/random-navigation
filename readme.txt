=== Random nvigation ===
Plugin Name: Random nvigation
Contributors: fred19726
Tags: widget, widgets, menu, navigation, page, pages, categories, tag, tags, sidebar
Requires at least: 3.0
Tested up to: 3.1.1
Stable tag: trunk

Random navigation is a widget which provides navigation menu with many features. 

== Description ==
Random navigation is a widget which provides an easy to use list based navigation menu with many features:

This plugin requires PHP5!

Features:
* Start end and level can be configured
* The menu root page can be configured
* The page title of the menus parent can be used as widget title
* By default only subpages the pages in current page path are shown, but can also always expand the full tree (useful for javascript dropdown menus; repects start level, end level and the root page)
* Single pages can be excluded
* Optionaly include the blog as menu entry
* Optionaly show tags or categories below the blog like normal pages
* Usable as breadcrumbs (shows only the pages which are in the curren page path)
* Fully styleable via css. Classes for: current level, active entry, entries in the current path, entries which have children

== Installation ==

The installation is straight forward:

1. Upload `random-nav.php` to the `/wp-content/plugins/random-nav/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place the "Random nvigation" Widget

== Changelog ==

= 0.6 =
* fix css class name for unique entry

= 0.5 =
* properly escape the href attribute in the menu links

= 0.4 =
* first official version

== Upgrade Notice ==

No special action necessary for now