
#! /bin/bash

while getopts "p:" opt; do
	case ${opt} in
		p )
			PROJECT_DIR=${OPTARG}
			;;
	esac
done

# Establish symbolic links for the following directories:
# the data folder
rm data
ln -s ../data/${PROJECT_DIR} data
# the configuration folder
rm configuration
ln -s ../configuration/${PROJECT_DIR} configuration
# the logs folder
rm logs
ln -s ../data/${PROJECT_DIR}/logs logs

# Install the node packages
npm install

# Reload the node processes
pm2 reload "enquiry processor"
pm2 reload "quote processor"

# Set up all the scheduled tasks with cron
chmod 744 setup/zoho-refresh-api-tokens.php
php setup/zoho-refresh-api-tokens.php

CURRENT_WORKING_DIR=`pwd`
HOME=${CURRENT_WORKING_DIR}/logs
CRON_ENV="\n\nPATH=/bin:/usr/bin:/usr/local/bin:${CURRENT_WORKING_DIR}/setup\nHOME=${HOME}\n";
printf $CRON_ENV | cat - setup/tasks.crontab | tee tmp_crontab;
rm setup/tasks.crontab;
mv tmp_crontab setup/tasks.crontab;
cp setup/tasks.crontab ../cronjobs/$PROJECT_DIR.crontab
cat ../cronjobs/*.crontab | crontab -
