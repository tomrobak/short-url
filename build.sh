#!/bin/bash

# Short URL Plugin Build Script
# This script creates a properly packaged ZIP file for WordPress plugin distribution

# Set variables
PLUGIN_SLUG="short-url"
VERSION=$(grep "Version:" short-url.php | awk -F': ' '{print $2}' | tr -d ' \t\n\r')
BUILD_DIR="./build"
DIST_DIR="./dist"
ZIP_FILE="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"

# Create necessary directories
echo "Creating build directories..."
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}"
mkdir -p "${DIST_DIR}"

# Copy files to build directory
echo "Copying plugin files..."
rsync -av --exclude=".*" --exclude="build" --exclude="dist" --exclude="node_modules" --exclude="build.sh" --exclude="*.zip" --exclude="*.git*" ./ "${BUILD_DIR}/${PLUGIN_SLUG}/"

# Create the ZIP file
echo "Creating ZIP file: ${ZIP_FILE}"
cd "${BUILD_DIR}" || exit
zip -r "../${ZIP_FILE}" "${PLUGIN_SLUG}"
cd ..

# Clean up
echo "Cleaning up build directory..."
rm -rf "${BUILD_DIR}"

echo "Build complete! Plugin ZIP file created at: ${ZIP_FILE}"
echo "Plugin version: ${VERSION}" 