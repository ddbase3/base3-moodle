#!/bin/sh
set -eu

BASE3_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd -P)
if [ ! -f "$BASE3_DIR/install.sh" ] || [ ! -f "$BASE3_DIR/moodle/local/base3/ajax.php" ]; then
    echo 'Error: run this script from the BASE3 Moodle repository.' >&2
    exit 1
fi

# The native Moodle plugin copy is removed by install.sh using rsync --delete.
rm -f "$BASE3_DIR/moodle/local/base3/filemanager.php"
echo 'Removed obsolete Moodle FileManager source endpoint.'
