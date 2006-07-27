#!/bin/bash

if [ "$MKMANUAL_BUILD_DIR" != "" ]
then 
    BUILD_DIR=$MKMANUAL_BUILD_DIR
else    
    BUILD_DIR=/tmp/sdgdoc
fi

TARGET_DIR=$1
VERSION=0.1

echo "Structures_DataGrid Manual Generator $VERSION"

if [ "$TARGET_DIR" == "" ] 
then
    echo "Usage: $0 <peardoc_root>"
    exit 1
fi

echo

# Cleaning build directory
echo "Cleaning build directory : $BUILD_DIR"
rm -rf $BUILD_DIR
mkdir $BUILD_DIR

echo

# Building doc
printf "Running PhpDocumentor... Logging output into $BUILD_DIR/phpdoc.log"
phpdoc  -dn Structures_DataGrid \
        -dc Structures \
        -f "DataGrid.php,DataGrid/Column.php" \
        -t $BUILD_DIR \
        -o "XML:DocBook/peardoc2:default" \
        -ed docs/examples \
        > $BUILD_DIR/phpdoc.log 2>&1 

echo "Done."

echo

# Cleaning "Warnings" and fixing require_once()
# The sed command that removes examples line numbers might be dangerous
echo "Removing Warnings and examples line numbers, fixing require_once() into :"
cd $BUILD_DIR/structures/structures-datagrid
for f in structures-datagrid/*.xml structures-datagrid-column/*.xml 
do
    echo "  $f"
    cat $f \
        | grep -v '^Warning' \
        | sed 's/require_once &apos;\/DataGrid/require_once \&apos;Structures\/DataGrid/' \
        | sed 's/[0-9]\{1,2\} \{4,5\}//' \
        > $BUILD_DIR/grep.tmp \
        && mv $BUILD_DIR/grep.tmp $f 
done

echo

# Patching new/modified files
cd $BUILD_DIR/structures/structures-datagrid
echo "Patching new/modified file : "
for f in structures-datagrid/*.xml structures-datagrid-column/*.xml; do
    if ! diff -Nu -I '\$Revision.*\$' $TARGET_DIR/en/package/structures/structures-datagrid/$f \
        $f > $BUILD_DIR/diff
    then 
        patch $TARGET_DIR/en/package/structures/structures-datagrid/$f < $BUILD_DIR/diff
    fi            
done       

echo
echo Done. You can now regenerate the html manual.



