<?php
/* 
 * Copyright (C) 2014 Uncovery
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * zip messages more than one month old
 * this still needs to be tested.
 *
 * @global array $XMPP_ERROR
 */
function XMPP_ERROR_archive() {
    global $XMPP_ERROR;
    // get the relative day
    $date_obj = new DateTime($XMPP_ERROR['config']['reports_archive_date']);
    // we allow definition of an alternative timezone to be more admin-friendly
    if ($XMPP_ERROR['config']['reports_timezone']) {
        $date_obj->setTimezone(new DateTimeZone($XMPP_ERROR['config']['reports_timezone']));
    }    
    $year = $date_obj->format('Y');
    $month = $date_obj->format('m');
    $day = $date_obj->format('d');

    // create paths
    $day_path = $XMPP_ERROR['config']['reports_path'] . "/$year/$month/$day";
    $archive_file = $XMPP_ERROR['config']['reports_path'] . "/XMPP_ERRROR_archive-$year-$month-$day.zip";

    // check if there is anything to archive
    if (file_exists($day_path)) {
        // archive the folder
        $zip_check = XMPP_ERROR_zipTree($day_path, $archive_file);
        if (!$zip_check && $XMPP_ERROR['config']['self_track']) {
             XMPP_ERROR_trigger("Archive $archive_file failed");
        }
        // delete archived files by removing the directory of that day
        $rm_check = XMPP_ERROR_delTree($day_path);
        if (!$rm_check && $XMPP_ERROR['config']['self_track']) {
            XMPP_ERROR_trace("Archive remove $day_path failed");
        }
    } else {
        XMPP_ERROR_trace("No archive created", "PAth $day_path does not exist");
    }
}

/**
 * Recursive deletion of non-empty directories
 * source: http://php.net/manual/en/function.rmdir.php
 * @param string $dir
 */
function XMPP_ERROR_delTree($dir){
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        if (is_dir("$dir/$file")) {
            XMPP_ERROR_delTree("$dir/$file");
        } else {
            unlink("$dir/$file");
        }
    }
    return rmdir($dir);
}

/**
 * Zip a folder
 * Source: http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
 * 
 * @global type $XMPP_ERROR
 * @param type $source
 * @param type $destination
 * @return boolean
 */
function XMPP_ERROR_zipTree($source, $destination) {
    global $XMPP_ERROR;
    if ($XMPP_ERROR['config']['self_track']) {
        XMPP_ERROR_trace(__FUNCTION__, func_get_args());
    }
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $real_source = str_replace('\\', '/', realpath($source));
    if (is_dir($real_source) === true) {
        $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($real_source), 
                RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);
            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ) {
                continue;
            }
            $file = realpath($file);
            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($real_source . '/', '', $file . '/'));
            } else if (is_file($file) === true) {
                $zip->addFromString(str_replace($real_source . '/', '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($real_source) === true) {
        $zip->addFromString(basename($real_source), file_get_contents($real_source));
    }

    return $zip->close();
}