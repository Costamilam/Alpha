<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\App;
use Costamilam\Alpha\Auth;
use Costamilam\Alpha\Response;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;

class Token
{
    private static $algorithm;
    private static $key;
    private static $issuer;
    private static $audience;
    private static $expire;
    private static $signer = array(
		'hs256' => Signer\Hmac\Sha256::class,
        'hs384' => Signer\Hmac\Sha384::class,
        'hs512' => Signer\Hmac\Sha512::class,
        'rs256' => Signer\Rsa\Sha256::class,
        'rs384' => Signer\Rsa\Sha384::class,
        'rs512' => Signer\Rsa\Sha512::class,
        'es256' => Signer\Ecdsa\Sha256::class,
        'es384' => Signer\Ecdsa\Sha384::class,
        'es512' => Signer\Ecdsa\Sha512::class
	);

    public static function configure($algorithm, $key, $issuer, $audience, $expireInMinutes)
    {
        self::$algorithm = strtolower($algorithm);
        self::$issuer = $issuer;
        self::$audience = $audience;
        self::$expire = $expireInMinutes;

        if (in_array($algorithm, array('hs256', 'hs384', 'hs512'))) {
            self::$key = $key;
        } elseif (in_array($algorithm, array('rs256', 'rs384', 'rs512', 'es256', 'es384', 'es512'))) {
            $keychain = new Signer\Keychain();

            self::$key['public'] = is_file($key['public']) ? $keychain->getPublicKey($key['public']) : $key['public'];
            self::$key['private'] = is_file($key['private']) ? $keychain->getPublicKey($key['private']) : $key['private'];
        }
    }

    public static function expire()
    {
        return self::$expire;
    }

    public static function create($subject, $role, $data = null)
    {
        $signer = new self::$signer[self::$algorithm];

        $token = (new Builder())
            ->setIssuer(self::$issuer)
            ->setAudience(self::$audience)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration(time() + 60 * self::$expire)
            ->setSubject($subject)
            ->set('role', $role)
            ->set('data', $data)
            ->sign(
                $signer,
				is_array(self::$key) ? self::$key['private'] : self::$key
			)
            ->getToken()
            ->__toString();

        Auth::callStatus('created', $token);

        return $token;
    }

    public static function verify($token, $subject, $role)
    {
        if ($token === null) {
            Auth::callStatus('empty');

            return false;
        }

        try {
            $token = (new Parser())->parse($token);
        } catch (\Exception $exception) {
            Auth::callStatus('invalid');

            return false;
        }

        $validator = new ValidationData();
        $validator->setIssuer(self::$issuer);
        $validator->setAudience(self::$audience);

        if ($token->validate($validator)) {
            if (
                (
                    $subject === null ||
                    $subject == $token->getClaim('sub')
                ) && (
                    $role === null ||
                    $role == $token->getClaim('role')
                )
            ) {
                Auth::callStatus('authorized', self::parsePayload($token));

                return true;
            } else {
                Auth::callStatus('forbidden', self::parsePayload($token));

                return false;
            }
        } else {
            if ($token->isExpired()) {
                Auth::callStatus('expired', self::parsePayload($token));
            } else {
                Auth::callStatus('invalid');
            }

            return false;
        }
    }

    public static function payload($token)
    {
        if ($token === null) {
            return false;
        }

        try {
            $token = (new Parser())->parse($token);
        } catch (\Exception $exception) {
            return false;
        }

        return self::parsePayload($token);
    }

    private function parsePayload($token) {
        $payload = array();
        $name = array(
            'sub' => 'subject',
            'iss' => 'issuer',
            'aud' => 'audience',
            'iat' => 'issuedAt',
            'nbf' => 'notBefore',
            'exp' => 'expiration'
        );

        foreach ($token->getClaims() as $object) {
            $key = isset($name[$object->getName()]) ? $name[$object->getName()] : $object->getName();

            $value = is_object($object->getValue()) ? json_decode(json_encode($object->getValue()), true) : $object->getValue();

            $payload[$key] = $value;
        }

        return $payload;
    }
}
