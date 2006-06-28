#!/bin/sh

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

# Cleaning "Warnings"
cd $BUILD_DIR/structures/structures-datagrid
for f in structures-datagrid/*.xml structures-datagrid-column/*.xml; do
    echo Removing Warnings from $f
    cat $f | grep -v '^Warning' > $BUILD_DIR/grep.tmp && mv $BUILD_DIR/grep.tmp $f
done

# Removing old files from target
cd $TARGET_DIR/en/package/structures/structures-datagrid
rm structures-datagrid/*.xml structures-datagrid-column/*.xml

# Copying new files
cp $BUILD_DIR/structures/structures-datagrid/structures-datagrid/*.xml structures-datagrid
cp $BUILD_DIR/structures/structures-datagrid/structures-datagrid-column/*.xml structures-datagrid-column



