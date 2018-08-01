<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <neorazorx@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of fs_file_manager
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_file_manager
{

    /**
     * Check and copy .htaccess files
     */
    public static function check_htaccess()
    {
        if (!file_exists(FS_FOLDER . '/.htaccess')) {
            $txt = file_get_contents(FS_FOLDER . '/htaccess-sample');
            file_put_contents(FS_FOLDER . '/.htaccess', $txt);
        }

        /// ahora comprobamos el de tmp/XXXXX/private_keys
        if (file_exists(FS_FOLDER . '/tmp/' . FS_TMP_NAME . 'private_keys') && !file_exists(FS_FOLDER . '/tmp/' . FS_TMP_NAME . 'private_keys/.htaccess')) {
            file_put_contents(FS_FOLDER . '/tmp/' . FS_TMP_NAME . 'private_keys/.htaccess', 'Deny from all');
        }
    }

    /**
     * Clear all RainTPL cache files.
     */
    public static function clear_raintpl_cache()
    {
        foreach (self::scan_files(FS_FOLDER . '/tmp/' . FS_TMP_NAME, 'php') as $file_name) {
            unlink(FS_FOLDER . '/tmp/' . FS_TMP_NAME . $file_name);
        }
    }

    /**
     * Recursive delete directory.
     *
     * @param string $folder
     *
     * @return bool
     */
    public static function del_tree($folder)
    {
        if (!file_exists($folder)) {
            return true;
        }

        $files = is_dir($folder) ? static::scan_folder($folder) : [];
        foreach ($files as $file) {
            $path = $folder . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? static::del_tree($path) : unlink($path);
        }

        return is_dir($folder) ? rmdir($folder) : unlink($folder);
    }

    /**
     * Returns an array with all not writable folders.
     *
     * @return array
     */
    public static function not_writable_folders()
    {
        $notwritable = [];
        foreach (static::scan_folder(FS_FOLDER, true) as $folder) {
            if (is_dir($folder) && !is_writable($folder)) {
                $notwritable[] = $folder;
            }
        }

        return $notwritable;
    }

    /**
     * Copy all files and folders from $src to $dst
     *
     * @param string $src
     * @param string $dst
     * 
     * @return bool
     */
    public static function recurse_copy($src, $dst)
    {
        $folder = opendir($src);

        if (!file_exists($dst) && !@mkdir($dst)) {
            return false;
        }

        while (false !== ($file = readdir($folder))) {
            if ($file === '.' || $file === '..') {
                continue;
            } elseif (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                static::recurse_copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            } else {
                copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            }
        }

        closedir($folder);
        return true;
    }

    /**
     * 
     * @param string $folder
     * @param string $extension
     *
     * @return array
     */
    public static function scan_files($folder, $extension)
    {
        $files = [];
        $len = 1 + strlen($extension);
        foreach (self::scan_folder($folder) as $file_name) {
            if (substr($file_name, 0 - $len) === '.' . $extension) {
                $files[] = $file_name;
            }
        }

        return $files;
    }

    /**
     * Returns an array with files and folders inside given $folder
     *
     * @param string $folder
     * @param bool   $recursive
     * @param array  $exclude
     *
     * @return array
     */
    public static function scan_folder($folder, $recursive = false, $exclude = ['.', '..', '.DS_Store', '.well-known'])
    {
        $scan = scandir($folder, SCANDIR_SORT_ASCENDING);
        if (!is_array($scan)) {
            return [];
        }

        $rootFolder = array_diff($scan, $exclude);
        natcasesort($rootFolder);
        if (!$recursive) {
            return $rootFolder;
        }

        $result = [];
        foreach ($rootFolder as $item) {
            $newItem = $folder . DIRECTORY_SEPARATOR . $item;
            if (is_file($newItem)) {
                $result[] = $item;
                continue;
            }
            $result[] = $item;
            foreach (static::scan_folder($newItem, true) as $item2) {
                $result[] = $item . DIRECTORY_SEPARATOR . $item2;
            }
        }

        return $result;
    }
}
