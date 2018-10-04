
#! /bin/bash

while getopts "p:" opt; do
	case ${opt} in
		p )
			PROJECT_DIR=${OPTARG}
			;;
	esac
done

# Establish symbolic links to the `data` directory
rm data
ln -s ../configuration/${PROJECT_DIR} configuration
ln -s ../data/${PROJECT_DIR} data

# Reload the node processes
pm2 reload "enquiry processor"
pm2 reload "quote processor"

# Schedule a task to run every 45 minutes
chmod 744 setup/zoho-refresh-api-tokens.php
php setup/zoho-refresh-api-tokens.php
crontab setup/tasks.crontab
