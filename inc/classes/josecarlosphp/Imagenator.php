<?php

namespace josecarlosphp;

/**
 * @author josecarlosphp.com
 */
class Imagenator extends \josecarlosphp\Lister
{
    protected $baseUrl;
    protected $dir = './rsc';
    protected $excluded = array();

    public function __construct($baseUrl = null, $filename = null)
    {
        $this->baseUrl($baseUrl);

        if (is_null($filename) || $filename == '') {
            $filename = $this->dir . '/list.csv';
        } else {
            $this->dir(dirname($filename));
        }

        parent::__construct($filename);
    }

    public function setConfig($config)
    {
        foreach ($config as $key => $val) {
            if (property_exists($this, $key) && method_exists($this, $key) && !is_null($val) && $val !== '') {
                $this->$key($val);
            }
        }
    }

    public function baseUrl($baseUrl = null)
    {
        return $this->p(__FUNCTION__, $baseUrl);
    }

    public function dir($dir = null)
    {
        return $this->p(__FUNCTION__, $dir);
    }

    public function filename($filename = null)
    {
        if (($return = parent::filename($filename))) {
            $this->dir(dirname($return));
        }

        return $return;
    }

    public function excluded($excluded = null)
    {
        return $this->p(__FUNCTION__, $excluded);
    }

    public function process($str, $props = array())
    {
        $this->msgDbg('Process start');

        $search = array();
        $replace = array();

        $aux = $str;
        while (($posA = mb_stripos($aux, '<img ')) !== false) {
            $this->msgDbg('img tag found');

            if (($posB = mb_strpos($aux, '>', $posA + 5)) !== false) {
                $imgHtml = mb_substr($aux, $posA, $posB + 1 - $posA);
                $document = new \DOMDocument();
                if ($document->loadHTML($imgHtml)) {
                    $imgs = $document->getElementsByTagName('img');
                    foreach ($imgs as $img) {
                        $src = trim($img->getAttribute('src'));
                        if ($this->checkSrc($src)) {
                            $arr = explode('/', $src);
                            $val = mb_strtolower(implode('/', array_slice($arr, 0, 3)));
                            if (strcasecmp(mb_substr($val, 0, 7), 'http://') === 0) {
                                $val = 'https://' . mb_substr($val, 7);
                            }
                            $this->msgDbg('$val = ' . $val);
                            if (($key = $this->add($val)) !== false) {
                                $this->msgDbg('$key = ' . $key);
                                $img->setAttribute('src', sprintf('%s%03s/%s', $this->baseUrl, $key, implode('/', array_slice($arr, 3))));
                            } else {
                                $this->msgDbg('$key = false', 'error');
                            }
                        } else {
                            $this->msgDbg('src value is NOT valid: ' . $src);
                        }

                        foreach ($props as $prop => $item) {
                            $val = $img->getAttribute($prop);
                            if ($val == '' || is_null($val)) {
                                $img->setAttribute($prop, $item);
                            }
                        }

                        $search[] = $imgHtml;
                        $replace[] = $img->ownerDocument->saveXML($img);

                        break;
                    }
                } else {
                    $this->msgDbg('DOM load HTML failed', 'error');
                }
            } else {
                $this->msgDbg('Closing img tag NOT found', 'error');
            }

            $aux = mb_substr($aux, $posB + 1);
        }

        return empty($search) ? $str : str_replace($search, $replace, $str);
    }

    protected function checkSrc($src)
    {
        $this->msgDbg('Check src: ' . $src);

        if (strcasecmp(mb_substr($src, 0, $aux = 8), 'https://') === 0) {
        } elseif (strcasecmp(mb_substr($src, 0, $aux = 7), 'http://') === 0) {
        } else {
            $this->msgDbg('Is not http:// nor https://');
            return false;
        }

        if (strcasecmp(mb_substr($src, 0, mb_strlen($this->baseUrl)), $this->baseUrl) === 0) {
            $this->msgDbg('Is baseUrl');
            return false;
        }

        foreach ($this->excluded as $item) {
            if ($item && strcasecmp(mb_substr($src, $aux, mb_strlen($item)), $item) === 0) {
                $this->msgDbg('Is excluded');
                return false;
            }
        }

        $this->msgDbg('src value is valid');

        return true;
    }

    public function ext2contentType($ext)
    {
        $ext = strtolower($ext);

        switch ($ext) {
            case 'gif':
            case 'png':
                $contentType = 'image/' . $ext;
                break;
            case 'jpeg':
            case 'jpg':
                $contentType = 'image/jpeg';
                break;
            case 'svg':
                $contentType = 'image/svg+xml';
                break;
            default:
                $contentType = '';
                break;
        }

        return $contentType;
    }

    public function displayFile($file)
    {
        $contentType = null;
        if (function_exists('exif_imagetype')) {
            $contentType = image_type_to_mime_type(exif_imagetype($file));
        }

        $this->display(basename($file), file_get_contents($file), $contentType);
    }

    public function display($name, $content, $contentType = null)
    {
        if (is_null($contentType)) {
            $contentType = $this->ext2contentType(substr(strrchr($name, '.'), 1));
        }

        if ($contentType) {
            header('Content-type: ' . $contentType);
        }

        echo $content;
        exit;
    }

    protected function makeDir($file)
    {
        $cwd = getcwd();
        if (mb_substr($file, 0, $len = mb_strlen($cwd)) == $cwd) {
            $file = mb_substr($file, $len);
        }

        $dir = '.';
        foreach (explode('/', dirname($file)) as $item) {
            $dir .= '/' . $item;
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function exit($http_response_code)
    {
        Http::exit($http_response_code);
    }

    public function view($i, $r)
    {
        if ($i === '' || $r === '' || !is_numeric($i) || mb_strpos($r, '..') !== false) {
            $this->exit(400);
        }

        $fileRel = sprintf('%s/%s', $i, $r);
        $file = sprintf('%s/%s', $this->dir(), $fileRel);

        if (is_file($file)) {
            $this->displayFile($file);
        }

        if (($val = $this->getVal((int) $i)) === false) {
            $this->exit(404);
        }

        $url = $val . '/' . $r;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_CAINFO, "/path/to/cacert.pem");

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($response !== false) {
            switch ($info['http_code']) {
                case 200:
                    //$info['content-type']; //'image/jpeg'

                    if ($this->makeDir($file)) {
                        if (file_put_contents($file, $response) !== false) {
                            $this->logRsc($info['http_code'], $url, $fileRel);
                            $this->displayFile($file);
                        } else {
                            $this->MsgDbg('Failed file_put_contents()', 'error');
                            $this->exit(500);
                        }
                    } else {
                        $this->MsgDbg('Failed makeDir()', 'error');
                        $this->exit(500);
                    }
                    break;
                case 404:
                    if ($this->makeDir($file)) {
                        if (copy($this->dir() . '/pixel.gif', $file)) {
                            $this->logRsc($info['http_code'], $url, $fileRel);
                            $this->displayFile($file);
                        } else {
                            $this->MsgDbg('Failed copy()', 'error');
                            $this->exit(404);
                        }
                    } else {
                        $this->MsgDbg('Failed makeDir()', 'error');
                        $this->exit(404);
                    }
                    break;
                default:
                    $this->exit($info['http_code']);
                    break;
            }
        }

        $this->exit(503);
    }

    protected function logRsc($q, $url, $fileRel)
    {
        if (($fp = fopen($this->dir() . '/' . $q . '.csv', 'a')) !== false) {
            $return = fputcsv($fp, array(date('Y-m-d H:i:s'), $url, $fileRel));
            fclose($fp);

            return $return;
        }

        return false;
    }
}
