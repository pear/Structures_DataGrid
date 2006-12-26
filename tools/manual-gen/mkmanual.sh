#!/bin/bash

# Looking for PHP5
if [ "$MKMANUAL_PHPBIN" != "" ]
then 
    PHPBIN=$MKMANUAL_PHPBIN
else    
    if php -v | grep '^PHP 5' > /dev/null
    then
        PHPBIN=php
    elif php5 -v | grep '^PHP 5' > /dev/null
    then
        PHPBIN=php5
    else 
        echo "Could not find PHP5. Please set the MKMANUAL_PHPBIN environment variable"
        exit 1
    fi
fi

# how phpDoc needs to be calld
if [ "$MKMANUAL_PHPDOC" != "" ]
then 
    PHPDOC=$MKMANUAL_PHPDOC
else   
    PHPDOC=$(which phpdoc)
    if [ "$PHPDOC" == "" ]
    then
        echo "Could not find phpdoc. Please set the MKMANUAL_PHPDOC environment variable"
        exit 1
    fi
fi

# temporal build dir
if [ "$MKMANUAL_BUILD_DIR" != "" ]
then 
    BUILD_DIR=$MKMANUAL_BUILD_DIR
else 
    
    BUILD_DIR=/tmp/sdgdoc
fi

# temporal build dir for phpDoc
TARGET_DIR_PHPDOC=/tmp/sdgdoc

# target dir for the built
# (i.e. the directory where the checkout of peardoc is located)
if [ "$MKMANUAL_TARGET_DIR" != "" ]
then 
    TARGET_DIR=$MKMANUAL_TARGET_DIR
else    
    TARGET_DIR=$1
fi

VERSION=0.9

echo "Structures_DataGrid Manual Generator $VERSION"
echo 'CVS id: $Id: mkmanual.sh,v 1.20 2006-12-26 10:00:22 wiesemann Exp $'

if [ "$TARGET_DIR" == "" ] 
then
    echo "Usage: $0 <peardoc_root>"
    exit 1
fi

echo

# Cleaning build directory
echo "Cleaning build directory: $BUILD_DIR"
rm -rf $BUILD_DIR
mkdir $BUILD_DIR

echo

# Building doc
printf "Running PhpDocumentor... Logging output into $BUILD_DIR/phpdoc.log"
$PHPBIN $PHPDOC  -dn Structures_DataGrid \
                 -dc Structures \
                 -f "DataGrid.php,DataGrid/Column.php" \
                 -t $TARGET_DIR_PHPDOC \
                 -o "XML:DocBook/peardoc2:default" \
                 -ed docs/examples \
                 > $BUILD_DIR/phpdoc.log 2>&1 

echo "Done."

echo

echo "Adding XML declaration and revision tag to generated files"
$PHPBIN tools/manual-gen/add-revision-tags.php $TARGET_DIR_PHPDOC
echo "Done."
echo

echo "Parsing/Generating DataSource and Renderer files"

if [ "$MKMANUAL_INCPATH" != "" ]
then
    incpath="-d include_path=$MKMANUAL_INCPATH"
fi

$PHPBIN $incpath tools/manual-gen/parse-options.php $TARGET_DIR_PHPDOC
echo "Done."

# Cleaning "Warnings" and fixing require_once()
# The sed command that removes examples line numbers might be dangerous
echo "Removing Warnings and examples line numbers, fixing require_once() into:"
cd $BUILD_DIR/structures/structures-datagrid
for f in ../*.xml *.xml structures-datagrid/*.xml structures-datagrid-column/*.xml 
do
    echo "  $f"
    cat $f \
        | grep -v '^ *Warning' \
        | sed 's/require_once &apos;\/DataGrid/require_once \&apos;Structures\/DataGrid/' \
        | sed 's/<programlisting role="php-highlighted">1     /<programlisting role="php-highlighted">/' \
        | sed 's/^[0-9]\{1,2\} \{4,5\}//' \
        > $BUILD_DIR/grep.tmp \
        && mv $BUILD_DIR/grep.tmp $f 
done

echo

# Patching new/modified files
cd $BUILD_DIR/structures/structures-datagrid
for dir in \
    structures-datagrid \
    structures-datagrid-column \
    structures-datagrid-datasource \
    structures-datagrid-renderer
do
    if ! [ -d "$dir" ]
    then
        echo "Directory $dir - SKIPPING (no such directory)"
        continue
    elif [ "$(ls $dir)" == "" ]
    then
        echo "Directory $dir - SKIPPING (empty directory)"
        continue
    fi

    echo "Directory $dir - Patching new/modified file: "
    for f in $dir/*.xml
    do
        if ! diff -Nu -I '\$Revision.*\$' \
            $TARGET_DIR/en/package/structures/structures-datagrid/$f \
            $f > $BUILD_DIR/diff
        then 
            patch $TARGET_DIR/en/package/structures/structures-datagrid/$f < $BUILD_DIR/diff
        fi            
    done
done

echo
echo Done. You can now regenerate the html manual.
