Skyrocket for Wordpress Cache
================

**Use at your own risk.**

Before using a system like this, make sure you have reliable and regular backups!


First tests are given good result, 400ms to 500ms (950ms to 600ms) gain for DOM loading on lightweight websites.


Install & configuration
------
File requiert executable permissions (chmod +x ./skyrocket-wp-cache.php)

Cron recommendation: */15 * * * * /root/scripts/skyrocket-wp-cache.php >/root/scripts/cron.log

For specific configuration, see head of file

Current configuration works for Debian 6-7, and ISPConfig (even chrooted).


Requirement
------
Free memory must be ramdisk size + 25%


TODO
------
Check path protection

Create directory with good owner

Copying with same owner

Keep a correct ramdisk space

Allow deleting the directory while is mounted

Detect errors and restore by default

Using semaphore to prevent conflicts