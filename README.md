# shopware6-browseResources
This is a simple tool used to browse shopware's resources located in Administration, Storefront and Core directories.

### Usage:

Please provide at least one of these arguments: `-a`, `-s` or `-c` and a text string to look within files in those
directories. By default, this script looks at all files with specific supported formats, but you can limit this function
to one particular type with `-t` option followed by a file type.

* `-t (parameter)` Snow only files with one of accepted file extensions: css, html, js, json, php, scss, twig, xml
* `-l (parameter)` Show only those files which contain a substring in it's name or directory tree.
* `-d (parameter)` Show all files which don't contain such substring.
* `-o` Opens files in a default editor.
* `-p` Opens files using PHPStorm.
* `-r` Input string is treated as perl regex and not a text.
* `-js (optional parameter "both")` Show list of index.js files within listed directories.
* `-v` Prints out more information
* `-h` Prints out help
* `-i` Prints out information about shopware

Example: `./browseResources.phar -a -t twig -l cms sw-checkbox-field` - lists all `.html.twig` files which contain
`sw-checkbox-field` string within Administration directory without `cms` in a file or directory name.

Similar approaches without using this script
* `(cd "/vendor/shopware/platform/src/Administration"; for i in $(find . -type f -name "*.html.twig"); do if [[ $(cat "$i" | grep "sw-checkbox-field" | wc -l) -gt 0 ]]; then echo "$i"; fi; done | grep 'cms')`
* `(cd "/vendor/shopware/platform/src/Administration"; grep -rl 'sw-checkbox-field' | grep -P "\.html\.twig$" | grep 'cms")`

Notice how much cleaner our syntax is

### Compilation:

To compile `browseResources.phar` file just run `./pharCompile.php` from a console.