Skyrocket for Wordpress Cache
================

**Use at your own risk.**

Before using a system like this, make sure you have reliable and regular backups!


Install & configuration
------
Cron recommendation: */15 * * * * /root/scripts/skyrocket-wp-cache.php >/root/scripts/cron.log

For specific configuration, see head of file

Current configuration works for Debian 6-7, and ISPConfig (even chrooted).


Requirement
------
Free memory must be ramdisk size + 25%


TODO
------
Check path protection

Keep a correct ramdisk space

Allow deleting the directory while is mounted

Detect errors and restore by default

Using semaphore to prevent conflicts