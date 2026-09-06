#!/bin/sh
set -e

# Reload systemd after service file removal
if command -v systemctl >/dev/null 2>&1; then
    systemctl daemon-reload
fi

# Note: We intentionally do NOT remove the augias user, group,
# /etc/augias, or /var/lib/augias on uninstall.
# This preserves configuration and data for reinstallation.
# Users can manually remove these if desired:
#   sudo userdel augias
#   sudo groupdel augias
#   sudo rm -rf /etc/augias /var/lib/augias
