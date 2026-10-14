#!/usr/bin/env bash
# Builds the plugin as it is released: the files of a git ref without the development files (git archive leaves
# out every path marked export-ignore in .gitattributes) plus the production dependencies in vendor/, which are
# not committed but installed from composer.lock.
#
#   .github/scripts/build-plugin.sh <ref> <directory>
#
# Writes the plugin to <directory>/bunq-for-woocommerce/, the folder name WordPress uses as the plugin slug.
set -euo pipefail

ref="$1"
slug=bunq-for-woocommerce
target="$2/$slug"

rm -rf "$target"
mkdir -p "$target"
git archive --format=tar "$ref" | tar -x -C "$target"

composer install --working-dir="$target" --no-dev --optimize-autoloader --no-interaction --no-progress

test -f "$target/vendor/autoload.php"
