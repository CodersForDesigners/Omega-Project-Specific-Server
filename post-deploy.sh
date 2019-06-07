
#! /bin/bash

while getopts "p:" opt; do
	case ${opt} in
		p )
			PROJECT_DIR=${OPTARG}
			;;
	esac
done

# Establish symbolic links for the following directories:
# the environment folder
rm __environment
mkdir -p ../environment/${PROJECT_DIR}
ln -s ../environment/${PROJECT_DIR} __environment
# # For backwards compatability
# the data folder
rm data
ln -s ../data/${PROJECT_DIR} data
# the configuration folder
rm configuration
ln -s __environment/configuration configuration
# the logs folder
rm logs
ln -s ../data/${PROJECT_DIR}/logs logs

# Install the node packages
npm install

# Reload the node processes
# pm2 reload "enquiry processor"
pm2 reload "quote processor"


# -/-/-/-/-
# Set up all the scheduled tasks
# -/-/-/-/-
# Set permissive permission
chmod 744 */scheduled-task*
chmod 744 setup/scheduled-tasks/*.{sh,php,js}

CURRENT_WORKING_DIR=`pwd`
CRON_ENV="\n\nPATH=/bin:/usr/bin:/usr/local/bin:${CURRENT_WORKING_DIR}\nHOME=${CURRENT_WORKING_DIR}\n";
find -type f -name '*.crontab' -exec cat {} \; > tmp_crontab;
printf $CRON_ENV | cat - tmp_crontab | tee tmp_2_crontab;
rm tmp_crontab;
mkdir -p setup;
mv tmp_2_crontab setup/scheduled-tasks/all_tasks.crontab;
cp setup/scheduled-tasks/all_tasks.crontab __environment/scheduled-tasks/$PROJECT_DIR.crontab
cat __environment/scheduled-tasks/*.crontab | crontab -

# Just run this script
php setup/scheduled-tasks/zoho-refresh-api-tokens.php
