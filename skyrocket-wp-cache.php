#!/usr/bin/php
<?php
//         Skyrocket for Wordpress Cache
//   Alexandre Alouit <alexandre.alouit@gmail.com>
//         <!! Use at your own risk !!>
//
//
//                      .
//                     /_\
//                     |_|
//                     [_]
//                    /| |\
//                  /__| |__\
//                 _ | |_| |
//                ( ) /<_>\  __
//               (  `) /=\ /(` )_
//              _ `-> < _ < (  >_( )
//            _( ) (  )(   < < (    )
//           (    ) `'  `(   )  `'-_)
//            (_-'`        `-'
//
//
// Cron usage: */15 * * * * /root/scripts/skyrocket-wp-cache.php >/root/scripts/cron.log
// This file requiert executable permissions (chmod +x ./skyrocket-wp-cache.php)
// Configuration

// Cache directory (without last /)
$vhost_path = "/var/www/clients/client*/web*/web/wp-content/cache";
// Ramdisk directory (without last /)
$ramdisk_dir = "/var/cache/skyrocket";
// Ramdisk size (M, for MB; G for GB)
//$ramdisk_size = "1G";
$ramdisk_size = "768M";
// Currently mounted point
$mounted_file = "/proc/mounts";


// TODO: check last char of $vhost_path & $ramdisk_dir is not /

function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function currently_mounts($mounted_file) {
	// Check the directory is in memory ramdisk
	if(!$currently_mounts = file_get_contents($mounted_file)) {
	        print("Skyrocket: (ERROR) can't read file " . $mounted_file . "\n");
	        exit;
	}

	return $currently_mounts;
}

// Check ramdisk pool is present
if(!is_dir($ramdisk_dir)) {
	if(!mkdir($ramdisk_dir, 744)) {
		print("Skyrocket: (ERROR) can't create directory " . $ramdisk_dir . "\n");
		exit;
	}
}

// Refresh mount list
$currently_mounts = currently_mounts($mounted_file);

// Check the directory is in memory ramdisk
if(strpos($currently_mounts, "tmpfs " . $ramdisk_dir . " tmpfs") === FALSE) {
	print("Skyrocket: (WARNING) " . $ramdisk_dir . " not mounted, try to mount it" . "\n");
// TODO: CHECK FREE SIZE BEFORE MOUNT
	exec("mount -t tmpfs -o size=" . $ramdisk_size . ",rw,nosuid,nodev tmpfs " . $ramdisk_dir);

	// Refresh mount list
	$currently_mounts = currently_mounts($mounted_file);

	// Check the directory is in memory ramdisk (again)
	if(strpos($currently_mounts, "tmpfs " . $ramdisk_dir . " tmpfs") === FALSE) {
        	print("Skyrocket: (ERROR) unable to mount " . $ramdisk_dir . " correctly" . "\n");
		exit;
	}
	print("Skyrocket: (INFO) " . $ramdisk_dir . " was mounted correctly" . "\n");
}

if(!scandir($ramdisk_dir)) {
	print("Skyrocket: (ERROR) Unable to list " . $ramdisk_dir . "\n");
	exit;
}

$currently_cached = glob($ramdisk_dir . "/*");

// TODO: CHECK FREE SIZE IN RAMDISK

// List potential sites.
if(!$current_dir = glob($vhost_path)) {
	print("Skyrocket: (ERROR) can't list directory " . $vhost_path . "\n");
	exit;
}

// Update cache directory for new entries
foreach($current_dir as $path) {
	// Check is empty
	if(empty(glob($path . "/*"))) {
		print("Skyrocket: (INFO) directory found but empty: " . $path . "\n");
		break;
	}

// TODO: CHECK REQUIRED SIZE IN RAMDISK

	$usuable_path = base64url_encode($path);

	// Check he has a dedicated folder
	if(!in_array($ramdisk_dir . "/" . $usuable_path, $currently_cached)) {
		// Create it
                print("Skyrocket: (INFO) create directory ". $ramdisk_dir . "/" . $usuable_path . " for " . $path . "\n");
		if(!mkdir($ramdisk_dir . "/" . $usuable_path, 755)) {
			print("Skyrocket: (ERROR) can't create dedicated directory for " . $path . "\n");
			break;
		}

	        // Copy content recursively
	        print("Skyrocket: (INFO) copy all files to dedicated directory for " . $path . " to " . $ramdisk_dir . "/" . $usuable_path . "\n");
	        exec("cp -R " . $path . "/* " . $ramdisk_dir . "/" . $usuable_path);
// TODO: CHECK COPY
	}

	// Check is mounted
	if(strpos($currently_mounts, $path) === FALSE) {
		// Mount it
		print("Skyrocket: (INFO) mount to dedicated directory for " . $path . " to " . $ramdisk_dir . "/" . $usuable_path . "\n");
		exec("mount --bind " . $ramdisk_dir . "/" . $usuable_path . " " . $path);

		// Refresh mount list
		$currently_mounts = currently_mounts($mounted_file);

		if(strpos($currently_mounts, $path) === FALSE) {
                        print("Skyrocket: (ERROR) can't mount dedicated directory " . $ramdisk_dir . "/" . $usuable_path . " on " . $path . "\n");

			// Delete directory with content
			print("Skyrocket: (INFO) delete unnecessary directory for " . $path . " to " . $ramdisk_dir . "/" . $usuable_path . "\n");
			exec("rm -r " . $ramdisk_dir . "/" . $usuable_path);

			if(is_dir($ramdisk_dir . "/" . $usuable_path)) {
				print("Skyrocket: (ERROR) can't delete directory " . $ramdisk_dir . "/" . $usuable_path . "\n");
			}
                        print("Skyrocket: (INFO) " . $ramdisk_dir . "/" . $usuable_path . " deleted\n");
                }

	}
}

// Update cache directory for missing entries
foreach($currently_cached as $usuable_path) {
	// TODO: IF FREE SIZE IS UNDER 25%, GENERATE ERROR, FORCE UNMOUNT

	$usuable_path = str_replace($ramdisk_dir . "/", "", $usuable_path);
	$path = base64url_decode($usuable_path);

	// TODO: We need solution to allow deleting the directory while it is mounted. Open to any suggestions..
	// Check exist or empty
	if(!array_search($path, $current_dir) || empty(glob($path . "/*"))) {
		// Unmout it
		print("Skyrocket: (INFO) unmount unnecessary point for " . $path . "\n");
		exec("umount " . $path);

		// Refresh mount list
		$currently_mounts = currently_mounts($mounted_file);

		if(strpos($currently_mounts, $path) !== FALSE) {
			print("Skyrocket: (ERROR) can't unmount dedicated directory " . $ramdisk_dir . "/" . $usuable_path . " on " . $path . "\n");
			break;
		}

		// Delete ramdisk directory with content
		print("Skyrocket: (INFO) delete unnecessary directory for " . $path . " to " . $ramdisk_dir . "/" . $usuable_path . "\n");
		exec("rm -r " . $ramdisk_dir . "/" . $usuable_path);

		if(is_dir($ramdisk_dir . "/" . $usuable_path)) {
			print("Skyrocket: (ERROR) can't delete  directory " . $ramdisk_dir . "/" . $usuable_path . "\n");
		}

		// Delete directory content
		print("Skyrocket: (INFO) delete content directory " . $path . " for synchronization and prevent loops\n");
		exec("rm -r " . $path . "/*");

		if(!empty(glob($path))) {
			print("Skyrocket: (ERROR) can't delete content  directory " . $path . "\n");
		}
	}
}

// Update cache directory for ramdisk space missing
// If error: flush!
?>
