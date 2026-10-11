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

The installer only deploys files. It does not invoke Moodle CLI, Docker, or
BASE3 bootstrap commands. Moodle itself handles installation and upgrades of
native Moodle plugins. On first installation or after changing a native Moodle
plugin version, open Moodle's Site administration and complete any pending
plugin upgrade. Moodle also offers `admin/cli/upgrade.php` for operators who
prefer its CLI. Neither action is normally needed merely because a BASE3
module was added or updated.

BASE3 automatically refreshes its class map on the next bootstrap when the
installed modules change. A manual `--rebuild` is not normally required.
Moodle cache purging is not a mandatory step for BASE3 module additions;
use Moodle's own cache management if changes are not visible when expected.

Changes to the native Moodle plugins belong under `moodle/` and are deployed
with `./install.sh`, not edited in `public/`. Re-run `./install.sh` whenever
an installed BASE3 module's public assets change. The installer must run in
an environment with filesystem write access to the Moodle installation and
`rsync` available; it does not assume a particular web server or deployment
system.
