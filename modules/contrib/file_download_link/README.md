CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------
This module provides a field formatter plugin for rendering
file fields or image fields as configurable download links.
You can configure the link text, whether or not it opens
the file in a new window, and whether or not to try to force
download with the html5 download attribute. It also adds
some helpful classes to the link in case you want to style
different types of files in different ways.

In short, the formatter has these features:

  * Configurable link text with token integration
  * Configurable "target" attribute
  * Configurable "download" attribute
  * Automatic CSS class based on file type
  * Configurable additional CSS classes on link
  
The submodule file_download_link_media allows you to directly render
a Media reference field and a link the Media's source file or image.
This may save you from having to configure a new view mode for
Media in some cases.

This module was created in response to https://www.drupal.org/node/2991022

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/file_download_link

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/file_download_link


REQUIREMENTS
------------
Nothing outside of Drupal 8 core is required.


RECOMMENDED MODULES
-------------------
Token: when this module is installed the configurable link text supports tokens.

 * https://www.drupal.org/project/token


INSTALLATION
------------

 - Install the File Download Link module as you would normally install a
   contributed Drupal module.
   Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------
There is no global configuration for this module. To configure a file/image
field to be displayed using the File Download Link formatter:

 1. Add File field or Image field to a node type. (Or you could add it to a 
    Media type or Taxonomy vocabulary. In this example we'll assume we're 
    putting the field on a node type.)
 2. Go to the "Manage Display" page for the node type.
 3. Select "File Download Link" as the formatter for you File or Image field.
 4. Click the gear icon at the right of the form to configure the
    File DownloadLink formatter. See the introduction for descriptions of the
    various options.


TROUBLESHOOTING
---------------
Note that the "download" attribute will only work in modern browsers.

See https://caniuse.com/#feat=download


FAQ
---
Q: Can I use the file description as the link text?
A: If you enable the token module you can use something like
   [node:field_file:description] as the link text. However, there is 
   not a "use description as text" checkbox.


MAINTAINERS
-----------
 * Dan Flanagan (https://www.drupal.org/u/danflanagan8)
