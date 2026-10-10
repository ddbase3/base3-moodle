# BASE3 Moodle

One Git repository for the three native Moodle integration plugins.
`modules/` contains separately versioned BASE3 modules and is Git-ignored.

## Layout

- `moodle/local/base3/`: Administration, runtime, displays and floating chatbot
- `moodle/blocks/base3/`: Configurable BASE3 display blocks
- `moodle/filter/base3/`: Inline BASE3 display placeholders
- `modules/`: Installed BASE3 modules (not part of this repository)
- `install.sh`: Installs all three plugins and copies module assets to Moodle's public directory

## Install or update

From the Moodle root, run `./base3/install.sh`. This synchronizes only
`public/local/base3`, `public/blocks/base3` and `public/filter/base3`
using `rsync --delete`. Old plugin files are removed. It publishes module
assets as regular files, not symbolic links. No BASE3 module is changed.

After an initial install or a native Moodle plugin version change, run Moodle's
`admin/cli/upgrade.php --non-interactive` in the PHP runtime. After installing
or updating, run `admin/cli/purge_caches.php`.

Changes must be made under `moodle/` and deployed with `./install.sh`, not
edited in `public/`. Re-run `./install.sh` after updating a BASE3 module whose
assets changed. Run the installer from the host, not a read-only container.
