<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
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
 * Description of fs_ip_filter
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_ip_filter
{

    const BAN_SECONDS = 600;
    const MAX_ATTEMPTS = 5;

    /**
     *
     * @var string
     */
    private $filePath;

    /**
     *
     * @var array
     */
    private $ipList;

    public function __construct()
    {
        $this->filePath = FS_FOLDER . '/tmp/' . FS_TMP_NAME . 'ip.log';
        $this->ipList = [];

        if (file_exists($this->filePath)) {
            /// Read IP list file
            $file = fopen($this->filePath, 'rb');
            if ($file) {
                while (!feof($file)) {
                    $line = explode(';', trim(fgets($file)));
                    $this->read_line($line);
                }

                fclose($file);
            }
        }
    }

    /**
     * 
     * @param string $ip
     *
     * @return boolean
     */
    public function in_white_list($ip)
    {
        if (FS_IP_WHITELIST === '*' || FS_IP_WHITELIST === '') {
            return TRUE;
        }

        $aux = explode(',', FS_IP_WHITELIST);
        return in_array($ip, $aux);
    }

    /**
     * 
     * @param string $ip
     *
     * @return boolean
     */
    public function is_banned($ip)
    {
        foreach ($this->ipList as $line) {
            if ($line['ip'] == $ip && $line['count'] > self::MAX_ATTEMPTS) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * 
     * @param string $ip
     */
    public function set_attempt($ip)
    {
        $found = FALSE;
        foreach ($this->ipList as $key => $line) {
            if ($line['ip'] == $ip) {
                $this->ipList[$key]['count'] ++;
                $this->ipList[$key]['expire'] = time() + self::BAN_SECONDS;
                $found = TRUE;
                break;
            }
        }

        if (!$found) {
            $this->ipList[] = [
                'ip' => $ip,
                'count' => 1,
                'expire' => time() + self::BAN_SECONDS
            ];
        }

        $this->save();
    }

    /**
     * 
     * @param array $line
     */
    private function read_line($line)
    {
        /// if not expired
        if (count($line) == 3 && intval($line[2]) > time()) {
            $this->ipList[] = [
                'ip' => $line[0],
                'count' => (int) $line[1],
                'expire' => (int) $line[2]
            ];
        }
    }

    private function save()
    {
        $file = fopen($this->filePath, 'wb');
        if ($file) {
            foreach ($this->ipList as $line) {
                fwrite($file, $line['ip'] . ';' . $line['count'] . ';' . $line['expire'] . "\n");
            }

            fclose($file);
        }
    }
}
