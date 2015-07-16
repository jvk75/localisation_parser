localisation_parser
===================

Small script to generate iOS and Android localisation files from tsv/csv
Works atleast in Mac environments (maybe also in Linux)

Usage:
```
%> php parse.php -i <file> [-o <output dir>] [-ios | -android | -laravel | -all] [-separator <sheet separator>] [-force] [-multifile] [-of <name>]

-i <file>                     : input file (tsv, csv, ...)
-o <output dir>               : directory where files will be generated
                                (directory will be generated if not existing)
                                default = ./
-ios                          : Generate only iOS files
-android                      : Generate only Android files
-laravel                      : Generate only Laravel(php array) files
-all                          : Generate all supported platforms (default)
-separator <sheet separator>  : Column seprator in input file (default tab "\t")
-force                        : Generates files to languages which translation is not complete
-multifile                    : Each header at input file creates separate xml file (android only)
-of <name>                    : Output file name (Only applicable with -laravel or -all options)
                                default: text from first column of first line (error if empty)
```


