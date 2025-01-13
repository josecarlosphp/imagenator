<?php

namespace josecarlosphp;

/**
 * @author josecarlosphp.com
 */
class Lister
{
    protected $debug = false;
    protected $filename = './list.csv';
    protected $map = array();

    public function __construct($filename = null)
    {
        $this->filename($filename);
    }

    protected function p($p, $val)
    {
        if (!is_null($val) && $val != '') {
            $this->$p = $val;
        }

        return $this->$p;
    }

    public function debug($debug = null)
    {
        return $this->p(__FUNCTION__, $debug);
    }

    public function filename($filename = null)
    {
        return $this->p(__FUNCTION__, $filename);
    }

    public function read($filename = null)
    {
        $this->msgDbg('Read ' . $this->filename($filename));

        if (is_file($this->filename)) {
            $this->msgDbg('Is a file');
            if (($fp = fopen($this->filename, 'r')) !== false) {
                $this->map = array();

                while (!feof($fp)) {
                    $data = fgetcsv($fp);
                    if (is_array($data) && count($data) == 2) {
                        list($key, $val) = $data;
                        $this->map[$key] = $val;
                    }
                }

                fclose($fp);

                return $this->map;            }
        } else {
            $this->msgDbg('Is NOT a file');
        }

        return false;
    }

    public function write($filename = null)
    {
        $this->filename($filename);

        if (($fp = fopen($this->filename, 'w')) !== false) {
            $ok = true;
            foreach ($this->map as $key => $val) {
                $ok &= fputcsv($fp, array($key, $val)) !== false;
            }

            fclose($fp);

            return $ok;
        }

        return false;
    }

    public function add($val, $write = true)
    {
        $this->msgDbg('Add ' . $val);

        if (in_array($val, $this->map)) {
            $this->msgDbg('Yet mapped');

            return $this->getKey($val);
        }

        $count = count($this->map);
        do {
            $key = $count;
            $count++;
        } while (array_key_exists($key, $this->map));

        if ($write) {
            if (($fp = fopen($this->filename, 'a')) !== false) {
                $ok = fputcsv($fp, array($key, $val)) !== false;

                fclose($fp);

                if (!$ok) {
                    return false;
                }
            } else {
                $this->msgDbg('Failed open file ' . $this->filename);

                return false;
            }
        }

        $this->map[$key] = $val;

        return $key;
    }

    public function getKey($search)
    {
        foreach ($this->map as $key => $val) {
            if ($search == $val) {
                return $key;
            }
        }

        return false;
    }

    public function getVal($key)
    {
        return array_key_exists($key, $this->map) ? $this->map[$key] : false;
    }

    protected function msg($text, $class='info')
    {
        printf('<div>%s - %s</div>', strtoupper($class), $text);
    }

    protected function msgDbg($text, $class='info')
    {
        if ($this->debug) {
            $this->msg($text, $class);
        }
    }
}
