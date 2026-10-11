#!/bin/sh
set -eu

# Install the three native Moodle plugins from the BASE3 repository.
# This script never modifies modules/, Moodle core or moodledata.
BASE3_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd -P)
MOODLE_DIR=$(CDPATH= cd -- "$BASE3_DIR/.." && pwd -P)

if [ ! -f "$MOODLE_DIR/config.php" ] || [ ! -d "$MOODLE_DIR/public/local" ]; then
    echo 'Error: base3/install.sh must be located directly in a Moodle installation.' >&2
    exit 1
fi
if ! command -v rsync >/dev/null 2>&1; then
    echo 'Error: rsync is required for installation.' >&2
    exit 1
fi

for component in local blocks filter; do
    source_dir="$BASE3_DIR/moodle/$component/base3"
    if [ ! -f "$source_dir/version.php" ] || [ -L "$source_dir" ]; then
        echo "Error: missing native Moodle plugin source: $source_dir" >&2
        exit 1
    fi
    target_dir="$MOODLE_DIR/public/$component/base3"
    if [ -L "$target_dir" ]; then
        echo "Error: Moodle plugin directory must not be a symlink: $target_dir" >&2
        exit 1
    fi
done

if [ ! -f "$BASE3_DIR/modules/Base3Framework/base3.json" ] || \
   [ ! -f "$BASE3_DIR/modules/Base3Moodle/base3.json" ] || \
   [ ! -f "$BASE3_DIR/modules/Base3MoodleLab/base3.json" ] || \
   [ ! -f "$BASE3_DIR/modules/ClientStack/assets/assetloader/assetloader.min.js" ]; then
    echo 'Error: BASE3 modules or ClientStack assets are missing.' >&2
    exit 1
fi

for component in local blocks filter; do
    source_dir="$BASE3_DIR/moodle/$component/base3"
    target_dir="$MOODLE_DIR/public/$component/base3"
    mkdir -p "$target_dir"
    rsync -a --delete "$source_dir/" "$target_dir/"
    echo "Installed: public/$component/base3"
done

# Publish actual asset files, never links. Relative ES module imports remain valid.
asset_target="$MOODLE_DIR/public/local/base3/assets"
for module_dir in "$BASE3_DIR"/modules/*; do
    [ -d "$module_dir/assets" ] || continue
    [ -f "$module_dir/base3.json" ] || continue
    module_name=${module_dir##*/}
    mkdir -p "$asset_target/$module_name"
    rsync -aL --delete "$module_dir/assets/" "$asset_target/$module_name/"
    echo "Copied assets: $module_name"
done

echo 'BASE3 Moodle files and module assets installed. Moodle handles native plugin upgrades when required.'
