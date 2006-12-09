#!/bin/bash

# how phpDoc needs to be calld
if [ "$MKMANUAL_PHPDOC" != "" ]
then 
    PHPDOC=$MKMANUAL_PHPDOC
else    
    PHPDOC=phpdoc
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

VERSION=0.7

echo "Structures_DataGrid Manual Generator $VERSION"
echo 'CVS id: $Id: mkmanual.sh,v 1.15 2006-12-09 17:45:42 wiesemann Exp $'

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
$PHPDOC  -dn Structures_DataGrid \
         -dc Structures \
         -f "DataGrid.php,DataGrid/Column.php" \
         -t $TARGET_DIR_PHPDOC \
         -o "XML:DocBook/peardoc2:default" \
         -ed docs/examples \
         > $BUILD_DIR/phpdoc.log 2>&1 

echo "Done."

echo

echo "Parsing/Generating DataSource and Renderer files"
php tools/manual-gen/parse-options.php $TARGET_DIR_PHPDOC
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
