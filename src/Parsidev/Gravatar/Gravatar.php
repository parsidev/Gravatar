<?php

namespace Parsidev\Gravatar;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\HTML;
use \InvalidArgumentException;

class Gravatar
{

    private $defaultSize = null;
    protected $size = 80;
    protected $default_image = false;
    protected $max_rating = 'g';
    protected $use_secure_url = false;
    protected $param_cache = null;

    const HTTP_URL = 'http://www.gravatar.com/avatar/';
    const HTTPS_URL = 'https://secure.gravatar.com/avatar/';

    public function getSize()
    {
        return $this->size;
    }

    public function setSize($size)
    {
        $this->param_cache = null;
        if (!is_int($size) && !ctype_digit($size)) {
            throw new InvalidArgumentException('Avatar size specified must be an integer');
        }
        $this->size = (int)$size;
        if ($this->size > 512 || $this->size < 0) {
            throw new InvalidArgumentException('Avatar size must be within 0 pixels and 512 pixels');
        }

        return $this;
    }

    public function getDefaultImage()
    {
        return $this->default_image;
    }

    public function setDefaultImage($image)
    {
        if ($image === false) {
            $this->default_image = false;
            return $this;
        }
        $this->param_cache = null;
        $_image = strtolower($image);
        $valid_defaults = array('404' => 1, 'mm' => 1, 'identicon' => 1, 'monsterid' => 1, 'wavatar' => 1, 'retro' => 1);
        if (!isset($valid_defaults[$_image])) {
            if (!filter_var($image, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('The default image specified is not a recognized gravatar "default" and is not a valid URL');
            } else {
                $this->default_image = rawurlencode($image);
            }
        } else {
            $this->default_image = $_image;
        }
        return $this;
    }

    public function getMaxRating()
    {
        return $this->max_rating;
    }

    public function setMaxRating($rating)
    {
        $this->param_cache = null;
        $rating = strtolower($rating);
        $valid_ratings = array('g' => 1, 'pg' => 1, 'r' => 1, 'x' => 1);
        if (!isset($valid_ratings[$rating])) {
            throw new InvalidArgumentException(sprintf('Invalid rating "%s" specified, only "g", "pg", "r", or "x" are allowed to be used.', $rating));
        }
        $this->max_rating = $rating;
        return $this;
    }

    public function usingSecureImages()
    {
        return $this->use_secure_url;
    }

    public function enableSecureImages()
    {
        $this->use_secure_url = true;
        return $this;
    }

    public function disableSecureImages()
    {
        $this->use_secure_url = false;
        return $this;
    }

    public function buildGravatarURL($email, $hash_email = true)
    {
        if ($this->usingSecureImages()) {
            $url = static::HTTPS_URL;
        } else {
            $url = static::HTTP_URL;
        }

        if ($hash_email == true && !empty($email)) {
            $url .= $this->getEmailHash($email);
        } elseif (!empty($email)) {
            $url .= $email;
        } else {
            $url .= str_repeat('0', 32);
        }

        if ($this->param_cache === null) {
            $params = array();
            $params[] = 's=' . $this->getSize();
            $params[] = 'r=' . $this->getMaxRating();
            if ($this->getDefaultImage()) {
                $params[] = 'd=' . $this->getDefaultImage();
            }
            $this->params_cache = (!empty($params)) ? '?' . implode('&', $params) : '';
        }

        $tail = '';
        if (empty($email)) {
            $tail = !empty($this->params_cache) ? '&f=y' : '?f=y';
        }

        return $url . $this->params_cache . $tail;
    }

    public function getEmailHash($email)
    {
        return md5(strtolower(trim($email)));
    }

    public function get($email, $hash_email = true)
    {
        return $this->buildGravatarURL($email, $hash_email);
    }

    public function __construct(Config $config)
    {
        $this->setDefaultImage($config->get('gravatar.default'));
        $this->defaultSize = $config->get('gravatar.size');
        $this->setMaxRating($config->get('gravatar.maxRating', 'g'));
        /*$this->enableSecureImages();*/
    }

    public function src($email, $size = null, $rating = null)
    {
        if (is_null($size)) {
            $size = $this->defaultSize;
        }
        $size = max(1, min(512, $size));
        $this->setSize($size);
        if (!is_null($rating)) {
            $this->setMaxRating($rating);
        }
        return htmlentities($this->buildGravatarURL($email));
    }

    public function saveImage($email, $destination, $size = null, $rating = null)
    {
        if ($this->exists($email)) {
            if (is_null($size)) {
                $size = $this->defaultSize;
            }
            $this->setSize($size);
            if (!is_null($rating)) {
                $this->setMaxRating($rating);
            }
            if ($this->usingSecureImages()) {
                $this->downloadImage(
                    static::HTTPS_URL . $this->getEmailHash($email) . '?s=' . $this->getSize() . '&r=' . $this->getMaxRating(),
                    $destination);
            } else {
                $this->downloadImage(
                    static::HTTP_URL . $this->getEmailHash($email) . '?s=' . $this->getSize() . '&r=' . $this->getMaxRating(),
                    $destination);
            }
        }
    }

    public function downloadImage($url, $destination)
    {
        $ch = curl_init($url);
        $fp = fopen($destination, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    public function image($email, $alt = null, $attributes = array(), $rating = null)
    {
        $dimensions = array();
        if (array_key_exists('width', $attributes)) $dimensions[] = $attributes['width'];
        if (array_key_exists('height', $attributes)) $dimensions[] = $attributes['height'];
        $max_dimension = (count($dimensions)) ? min(512, max($dimensions)) : $this->defaultSize;
        $src = $this->src($email, $max_dimension, $rating);
        if (!array_key_exists('width', $attributes) && !array_key_exists('height', $attributes)) {
            $attributes['width'] = $this->size;
            $attributes['height'] = $this->size;
        }
        return $this->formatImage($src, $alt, $attributes);
    }

    public function exists($email)
    {
        $this->setDefaultImage('404');
        $url = $this->buildGravatarURL($email);
        $headers = get_headers($url, 1);
        return strpos($headers[0], '200') ? true : false;
    }

    private function formatImage($src, $alt, $attributes)
    {
        return '<img src="' . $src . '" alt="' . $alt . '" height="' . $attributes['height'] . '" width="' . $attributes['width'] . '">';
    }
}
