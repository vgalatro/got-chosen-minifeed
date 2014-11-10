got-chosen-minifeed
===================

WP plugin to integrate with Got Chosen's minifeed API.

The plugin provides the following options:

* Feed Key
  * The Feed Key provided by your Got Chosen publisher account to allow access to the Minifeed API.
* Webcurtain Settings
  * Enable/disable the webcurtain overlay for the site.
  * Enable/disable the compatability option to fix the webcurtain display on some sites.
* Minifeed Publishing Options
  * Ability to set a default for the per post option of publishing to the Minifeed API.
  * Make all Minifeed posts shareable.
  * Make all Minifeed posts commentable.
  * Ability to set what the 'Read More' text will read when a post is published to the Minifeed.
* Per post options
  * When editing/adding posts there is an options box added that allows a user to choose if they want that post published to the minifeed or not.

When a post is sent to the Minifeed API, if any errors occur, it will automatically be re-sent with each post save until it's successful.
