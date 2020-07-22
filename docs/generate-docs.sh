#!/bin/bash

# IMPORTANT NOTE:
# If you want to use this file directly, execute the command from the projects root directory.
# (user@computer: ~/path/to/hubcore$ bash docs/generate-docs.sh)
# Usually, this script is used by the "composer docs" command.

echo "Create the php documentation ..."
php vendor/bin/phpdox
rm -rf build/
