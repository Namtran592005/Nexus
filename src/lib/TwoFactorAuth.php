<?php
/**
 * This is a bundled version of the RobThree/TwoFactorAuth library
 * for easy inclusion in projects without Composer.
 * It combines all necessary classes into a single file.
 * 
 * @version 2.5.0
 * @see https://github.com/RobThree/TwoFactorAuth
 * @license BSD-2-Clause
 */

namespace RobThree\Auth\Providers\Rng;

interface IRngProvider
{
    /**
     * Return a string of $bytecount length of random bytes
     *
     * @param int $bytecount The number of random bytes to return
     * @return string
     */
    public function getRandomBytes($bytecount);
}

class CSRNGProvider implements IRngProvider
{
    /**
     * {@inheritdoc}
     */
    public function getRandomBytes($bytecount)
    {
        return random_bytes($bytecount);
    }
}

namespace RobThree\Auth\Providers\Qr;

interface IQRCodeProvider
{
    /**
     * Returns the mimetype of the generated QR code
     *
     * @return string
     */
    public function getMimeType();

    /**
     * Returns the QR code image data
     *
     * @param string $qrtext The text to encode
     * @param int $size The desired image size (width and height are the same)
     * @return string
     */
    public function getQRCodeImage($qrtext, $size);
}

class GoogleQrCodeProvider implements IQRCodeProvider
{
    public $errorcorrectlevel;
    public $margin;
    
    public function __construct($errorcorrectlevel = 'L', $margin = 4)
    {
        $this->errorcorrectlevel = $errorcorrectlevel;
        $this->margin = $margin;
    }

    public function getMimeType()
    {
        return 'image/png';
    }

    public function getQRCodeImage($qrtext, $size)
    {
        $url = 'https://chart.googleapis.com/chart?cht=qr&chs=' . $size . 'x' . $size . '&chld=' . $this->errorcorrectlevel . '|' . $this->margin . '&chl=' . rawurlencode($qrtext);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    public function getQRCodeImageAsDataUri($qrtext, $size)
    {
        return 'data:' . $this->getMimeType() . ';base64,' . base64_encode($this->getQRCodeImage($qrtext, $size));
    }
}


namespace RobThree\Auth;

use RobThree\Auth\Providers\Rng\IRngProvider;
use RobThree\Auth\Providers\Rng\CSRNGProvider;
use RobThree\Auth\Providers\Qr\IQRCodeProvider;
use Exception;

class TwoFactorAuthException extends Exception {}

class TwoFactorAuth
{
    protected $algorithm;
    protected $period;
    protected $digits;
    protected $issuer;
    protected $qrcodeprovider;
    protected $rngprovider;
    
    public function __construct($issuer = null, $digits = 6, $period = 30, $algorithm = 'sha1', IQRCodeProvider $qrcodeprovider = null, IRngProvider $rngprovider = null)
    {
        $this->issuer = $issuer;

        if (!is_int($digits) || $digits <= 0)
            throw new TwoFactorAuthException('Digits must be a positive integer');
        $this->digits = $digits;

        if (!is_int($period) || $period <= 0)
            throw new TwoFactorAuthException('Period must be a positive integer');
        $this->period = $period;

        if (!in_array($algorithm, $this->getAllowedAlgorithms()))
            throw new TwoFactorAuthException('Unsupported algorithm specified');
        $this->algorithm = $algorithm;
        
        $this->qrcodeprovider = $qrcodeprovider;
        $this->rngprovider = $rngprovider ?: new CSRNGProvider();
    }
    
    public function createSecret($bits = 160)
    {
        if (!is_int($bits) || $bits < 8 || $bits % 8 !== 0) {
            throw new TwoFactorAuthException('Bits must be a multiple of 8');
        }
        return $this->encodeBase32($this->rngprovider->getRandomBytes($bits / 8));
    }

    public function getCode($secret, $time = null)
    {
        $time = ($time === null) ? $this->getTime() : (int)$time;
        
        $secretkey = $this->decodeBase32($secret);
        $timestamp = pack('N*', 0) . pack('N*', floor($time / $this->period));
        $hash = hash_hmac($this->algorithm, $timestamp, $secretkey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncatedhash = substr($hash, $offset, 4);
        $code = unpack('N', $truncatedhash)[1] & 0x7FFFFFFF;
        return str_pad($code % (10 ** $this->digits), $this->digits, '0', STR_PAD_LEFT);
    }

    public function verifyCode($secret, $code, $discrepancy = 1, $time = null)
    {
        $time = ($time === null) ? $this->getTime() : (int)$time;
        $code = str_replace(' ', '', $code);

        if (strlen($code) > $this->digits) {
            return false;
        }

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            if (hash_equals($this->getCode($secret, $time + ($i * $this->period)), $code)) {
                return true;
            }
        }
        return false;
    }

    public function getQRCodeImageAsDataUri($label, $secret, $size = 200, IQRCodeProvider $provider = null)
    {
        $provider = $provider ?: $this->qrcodeprovider;
        if (!$provider) throw new TwoFactorAuthException('No QR code provider specified.');
        return 'data:' . $provider->getMimeType() . ';base64,' . base64_encode($provider->getQRCodeImage($this->getQRText($label, $secret), $size));
    }

    public function getQRText($label, $secret)
    {
        return 'otpauth://totp/' . rawurlencode($this->getlabel($label)) . '?secret=' . rawurlencode($secret) . '&issuer=' . rawurlencode($this->getissuer($label)) . '&period=' . $this->period . '&algorithm=' . strtoupper($this->algorithm) . '&digits=' . $this->digits;
    }

    protected function getTime()
    {
        return time();
    }

    protected function getAllowedAlgorithms()
    {
        return array('sha1', 'sha256', 'sha512');
    }

    protected function getlabel($label)
    {
        return ($this->issuer !== null) ? $this->issuer . ':' . $label : $label;
    }
    
    protected function getissuer($label)
    {
        return ($this->issuer !== null) ? $this->issuer : $label;
    }
    
    protected function decodeBase32($value)
    {
        $value = strtoupper($value);
        if (!preg_match('/^[A-Z2-7]+=*$/', $value)) {
            throw new TwoFactorAuthException('Invalid base32 string');
        }
        
        static $map = [
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        ];
        
        $value = str_replace('=', '', $value);
        $len = strlen($value);
        $binary = '';
        for ($i = 0; $i < $len; $i++) {
            $binary .= str_pad(decbin($map[$value[$i]]), 5, '0', STR_PAD_LEFT);
        }
        
        $binary = substr($binary, 0, floor(strlen($binary) / 8) * 8);
        $result = '';
        for ($i = 0; $i < strlen($binary); $i += 8) {
            $result .= chr(bindec(substr($binary, $i, 8)));
        }
        
        return $result;
    }
    
    protected function encodeBase32($value)
    {
        if (empty($value)) {
            return '';
        }

        static $map = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z', '2', '3', '4', '5', '6', '7'
        ];

        $binary = '';
        foreach (str_split($value) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $len = strlen($binary);
        $result = '';
        for ($i = 0; $i < $len; $i += 5) {
            $chunk = substr($binary, $i, 5);
            $result .= $map[bindec(str_pad($chunk, 5, '0'))];
        }

        $padding = 8 - ceil($len / 8);
        if ($padding > 0 && $padding < 8) {
            $result = str_pad($result, ceil(strlen($result) / 8) * 8, '=');
        }

        return $result;
    }
}