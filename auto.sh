#!/bin/bash

# Get the current working directory
WATCH_DIR=$(pwd)

# Function to squash commits
squash_commits() {
    # List recent commits to help the user choose SHAs
    echo "Recent commits:"
    git -C "$WATCH_DIR" log --oneline

    # Ask the user for the start and end SHAs
    read -p "Enter the start SHA to squash from: " START_SHA
    read -p "Enter the end SHA to squash to (leave empty for HEAD): " END_SHA

    if [[ -z "$END_SHA" ]]; then
        END_SHA="HEAD"
    fi

    # Perform an interactive rebase to squash commits
    git -C "$WATCH_DIR" rebase -i $START_SHA^ $END_SHA

    # Push the squashed commit
    git -C "$WATCH_DIR" push -f origin $(git -C "$WATCH_DIR" rev-parse --abbrev-ref HEAD)

    echo "Squash complete!"
}

# Check for the --squash flag
if [[ "$1" == "--squash" ]]; then
    squash_commits
    exit 0
fi

# Start monitoring the directory for file changes
while true; do
    # Get the list of files that have changed and are tracked by Git
    CHANGED_FILES=$(git -C "$WATCH_DIR" ls-files --modified --others --exclude-standard)

    # Check if there are any files that need to be committed
    if [[ -n "$CHANGED_FILES" ]]; then
        echo "Change detected in tracked files. Committing and pushing changes."

        # Add all changes that are tracked by Git and not ignored
        git -C "$WATCH_DIR" add .

        # Commit changes with a generic message
        git -C "$WATCH_DIR" commit -m "Auto-commit: Changes detected in $WATCH_DIR"

        # Push changes to the current branch
        git -C "$WATCH_DIR" push origin $(git -C "$WATCH_DIR" rev-parse --abbrev-ref HEAD)

        # Check if the push resulted in a conflict
        if [[ $? -ne 0 ]]; then
            echo "Merge conflict detected. Attempting to resolve."

            # Attempt to resolve conflicts by favoring 'theirs' (incoming changes)
            git -C "$WATCH_DIR" merge --strategy-option=theirs

            # Add and commit the resolved merge
            git -C "$WATCH_DIR" add .
            git -C "$WATCH_DIR" commit -m "Auto-commit: Resolved merge conflict"

            # Push the resolved merge
            git -C "$WATCH_DIR" push origin $(git -C "$WATCH_DIR" rev-parse --abbrev-ref HEAD)
        fi
    fi

    # Sleep for a short time to avoid excessive CPU usage
    sleep 2
done
