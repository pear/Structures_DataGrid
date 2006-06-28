#!/bin/bash

BUILD_DIR=/tmp/sdgdoc
TARGET_DIR=$1

if [ "$TARGET_DIR" == "" ]; then
    echo "Please provide a target directory (peardoc root)"
    exit 1
fi

# Cleaning build directory
rm -rf $BUILD_DIR

# Building doc
phpdoc -c tools/manual-gen/sdg-manual.ini

# Cleaning "Warnings" and fixing require_once()
cd $BUILD_DIR/structures/structures-datagrid
for f in structures-datagrid/*.xml structures-datagrid-column/*.xml; do
    echo "Removing Warnings and fixing require_once() into $f"
    cat $f \
        | grep -v '^Warning' \
        | sed 's/require_once &apos;\/DataGrid/require_once \&apos;Structures\/DataGrid/' \
        > $BUILD_DIR/grep.tmp \
        && mv $BUILD_DIR/grep.tmp $f 
done

# Removing old files from target
cd $TARGET_DIR/en/package/structures/structures-datagrid
rm structures-datagrid/*.xml structures-datagrid-column/*.xml

# Copying new files

echo "Copying all XML files :"
echo "  from $BUILD_DIR/structures/structures-datagrid/structures-datagrid"
echo "  to $(pwd)/structures-datagrid"
cp $BUILD_DIR/structures/structures-datagrid/structures-datagrid/*.xml structures-datagrid

echo "Copying all XML files :"
echo "  from $BUILD_DIR/structures/structures-datagrid/structures-datagrid-column"
echo "  to $(pwd)/structures-datagrid-column"
cp $BUILD_DIR/structures/structures-datagrid/structures-datagrid-column/*.xml structures-datagrid-column



