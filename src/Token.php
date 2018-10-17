<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\App;
use Costamilam\Alpha\Response;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;

abstract class Token
{
    public static $currentToken;
    public static $regeneratedToken;

    private static $algorithm;
    private static $key;
    private static $issuer;
    private static $audience;
    protected static $expire;
    private static $signer;

    private static $callback;
    private static $executed = array();

    public static function configure($algorithm, $key, $issuer, $audience, $expireInMinutes)
    {
        self::$algorithm = $algorithm;
        self::$issuer = $issuer;
        self::$audience = $audience;
        self::$expire = time()+60*$expireInMinutes;

        if (in_array($algorithm, array("hs256", "hs384", "hs512"))) {
            self::$key = $key;
        } elseif (in_array($algorithm, array("rs256", "rs384", "rs512", "es256", "es384", "es512"))) {
            $keychain = new Signer\Keychain();

            self::$key["public"] = $keychain->getPublicKey($key["public"]);
            self::$key["private"] = $keychain->getPrivateKey($key["private"]);
        }
    }

    protected static function create($subject, $data = null)
    {
        switch (strtolower(self::$algorithm)) {
            case "hs256":
                self::$signer = new Signer\Hmac\Sha256();
                break;

            case "hs384":
                self::$signer = new Signer\Hmac\Sha384();
                break;

            case "hs512":
                self::$signer = new Signer\Hmac\Sha512();
                break;

            case "rs256":
                self::$signer = new Signer\Rsa\Sha256();
                break;

            case "rs384":
                self::$signer = new Signer\Rsa\Sha384();
                break;

            case "rs512":
                self::$signer = new Signer\Rsa\Sha512();
                break;

            case "es256":
                self::$signer = new Signer\Ecdsa\Sha256();
                break;

            case "es384":
                self::$signer = new Signer\Ecdsa\Sha384();
                break;

            case "es512":
                self::$signer = new Signer\Ecdsa\Sha512();
                break;

            default:
                self::executeCallback("failure");

                return false;
                break;
        }

        $token = (new Builder())
            ->setIssuer(self::$issuer)
            ->setAudience(self::$audience)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration(self::$expire)
            ->setSubject($subject)
            ->set("data", $data)    
            ->sign(self::$signer, is_array(self::$key) ? self::$key["private"] : self::$key)
            ->getToken();

        self::executeCallback("created", $token);

        return $token;
    }

    protected static function verify($token, $callback)
    {
        if ($token === null) {
            self::executeCallback("empty");

            return false;
        }

        try {
            $token = (new Parser())->parse($token);
        } catch (\Exception $exception) {
            self::executeCallback("invalid", $token);

            return false;
        }

        $validator = new ValidationData();
        $validator->setIssuer(self::$issuer);
        $validator->setAudience(self::$audience);

        if ($token->validate($validator)) {
            if ($callback === null || is_callable($callback) && call_user_func($callback, $token) === true) {
                self::executeCallback("authorized", $token);
            } else {
                self::executeCallback("forbidden", $token);
            }

            return $token;
        } else {
            if ($token->isExpired()) {
                self::executeCallback("expired", $token);
            } else {
                self::executeCallback("invalid", $token);
            }

            return false;
        }
    }

    public static function onStatus($status, $callback)
    {
        if (is_array($status)) {
            foreach ($status as $value) {
                self::$callback["$value"] = $callback;
            }
        } else {
            self::$callback["$status"] = $callback;
        }
    }

    private static function executeCallback($status, ...$param)
    {
        if (in_array($status, self::$executed)) {
            return;
        }

        self::$executed[] = $status;

        if (isset(self::$callback["$status"]) && is_callable(self::$callback["$status"])) {
            call_user_func(self::$callback["$status"], ...$param);

            return true;
        } else {
            $response = array(
                "empty" => 401,
                "expired" => 401,
                "invalid" => 401,
                "forbidden" => 403,
                "failure" => 500
            );

            if (isset($response[$status])) {
                Response::status($response[$status]);
            }

            App::finish();
        }

        return false;
    }
}
