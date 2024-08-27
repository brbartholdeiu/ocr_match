#!/bin/bash

# Get the current working directory
WATCH_DIR=$(pwd)

# Start monitoring the directory for file changes
fswatch -o "$WATCH_DIR" | while read -r; do
    echo "Change detected in $WATCH_DIR. Committing and pushing changes."

    # Add all changes
    git -C "$WATCH_DIR" add .

    # Commit changes with a generic message
    git -C "$WATCH_DIR" commit -m "Auto-commit: Changes detected in $WATCH_DIR"

    # Push changes to the current branch
    git -C "$WATCH_DIR" push origin $(git rev-parse --abbrev-ref HEAD)
done