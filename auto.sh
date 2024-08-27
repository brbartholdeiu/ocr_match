#!/bin/bash

# Get the current working directory
WATCH_DIR=$(pwd)

# Get the name of the script file
SCRIPT_NAME=$(basename "$0")

# Start monitoring the directory for file changes
fswatch -o "$WATCH_DIR" | while read -r; do
    # Get the list of files changed that are tracked by Git
    CHANGED_FILES=$(git -C "$WATCH_DIR" ls-files --modified --others --exclude-standard)

    # Check if there are any files that need to be committed
    if [[ -n "$CHANGED_FILES" ]]; then
        echo "Change detected in tracked files. Committing and pushing changes."

        # Add all changes that are tracked by Git and not ignored
        git -C "$WATCH_DIR" add $CHANGED_FILES

        # Commit changes with a generic message
        git -C "$WATCH_DIR" commit -m "Auto-commit: Changes detected in $WATCH_DIR"

        # Push changes to the current branch
        git -C "$WATCH_DIR" push origin $(git rev-parse --abbrev-ref HEAD)
    fi
done
