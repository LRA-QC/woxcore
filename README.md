# woxcore

**woxcore** is a PHP framework (compatible with **PHP 7**.x.x). Plenty of included modules wrapper : Database , authentication, controller and more


# author

Luc Raymond : https://lucraymond.net

# build and optimization

One of the thing which is not often considered with frameworks is that they include tons of files. Including several small files is very slow.

A special build process is included so that all files will be concatened in a single file. You have the choice of using one version that still have all the source code comments or a single minified version without comments.


Launch './_rebuild.sh' to build the optimized versions
- wc_core.php : full minified version, no linefeed and no comments.
- wc_core_with_comments.php : full with comments 

You only have to link wc_core.php in your projects.

if you want to include a specific file, do so by using it's filename.


# Example

will be able later
