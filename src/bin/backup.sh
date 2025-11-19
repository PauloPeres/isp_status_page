#!/usr/bin/env bash

#
# Database Backup Script
#
# Creates a backup of the SQLite database and rotates old backups.
# Can be run manually or scheduled via cron.
#
# Usage:
#   ./bin/backup.sh              # Create backup with defaults
#   ./bin/backup.sh --compress   # Create compressed backup
#   ./bin/backup.sh --help       # Show help
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
APP_DIR="$(dirname "$SCRIPT_DIR")"

# Default values
BACKUP_DIR="${APP_DIR}/backups"
KEEP_COUNT=30
COMPRESS=false
PREFIX="backup"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    -c|--compress)
      COMPRESS=true
      shift
      ;;
    -d|--dir)
      BACKUP_DIR="$2"
      shift 2
      ;;
    -k|--keep)
      KEEP_COUNT="$2"
      shift 2
      ;;
    -p|--prefix)
      PREFIX="$2"
      shift 2
      ;;
    -h|--help)
      echo "Database Backup Script"
      echo ""
      echo "Usage: $0 [options]"
      echo ""
      echo "Options:"
      echo "  -c, --compress      Compress backup with gzip"
      echo "  -d, --dir DIR       Backup directory (default: ${APP_DIR}/backups)"
      echo "  -k, --keep COUNT    Number of backups to keep (default: 30)"
      echo "  -p, --prefix NAME   Backup file prefix (default: backup)"
      echo "  -h, --help          Show this help"
      echo ""
      echo "Examples:"
      echo "  $0                           # Create backup with defaults"
      echo "  $0 --compress                # Create compressed backup"
      echo "  $0 --keep 60                 # Keep last 60 backups"
      echo "  $0 --dir /path/to/backups    # Custom backup directory"
      exit 0
      ;;
    *)
      echo -e "${RED}Error: Unknown option: $1${NC}"
      echo "Run '$0 --help' for usage information"
      exit 1
      ;;
  esac
done

# Print header
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  ISP Status Page - Database Backup${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Build command
CMD="${APP_DIR}/bin/cake backup"
CMD="${CMD} --dir=\"${BACKUP_DIR}\""
CMD="${CMD} --keep=${KEEP_COUNT}"
CMD="${CMD} --prefix=\"${PREFIX}\""

if [ "$COMPRESS" = true ]; then
  CMD="${CMD} --compress"
fi

# Execute backup command
echo -e "${YELLOW}Running: ${CMD}${NC}"
echo ""

if eval "$CMD"; then
  echo ""
  echo -e "${GREEN}✓ Backup completed successfully!${NC}"

  # Show backup directory contents
  echo ""
  echo "Recent backups:"
  ls -lht "${BACKUP_DIR}" | head -6

  exit 0
else
  echo ""
  echo -e "${RED}✗ Backup failed!${NC}"
  exit 1
fi
