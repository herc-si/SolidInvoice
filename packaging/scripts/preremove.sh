#!/bin/sh
set -e

# Stop and disable the service before removal
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet augias 2>/dev/null; then
        systemctl stop augias
    fi
    if systemctl is-enabled --quiet augias 2>/dev/null; then
        systemctl disable augias
    fi
fi
