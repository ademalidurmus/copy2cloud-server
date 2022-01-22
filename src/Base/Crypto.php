<?php

namespace Copy2Cloud\Base;

use Defuse\Crypto\Crypto as DefuseCrypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Respect\Validation\Validator as v;

class Crypto extends DefuseCrypto
{
    /**
     * @param array $fields
     * @param array $data
     * @param string $secret
     * @return array
     * @throws EnvironmentIsBrokenException
     */
    public static function encryptContext(array $fields, array $data, string $secret): array
    {
        foreach ($fields as $field) {
            if (v::key($field)->validate($data)) {
                $data[$field] = self::encryptWithPassword(serialize($data[$field]), $secret);
            }
        }

        return $data;
    }

    /**
     * @param array $fields
     * @param array $data
     * @param string $secret
     * @return array
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public static function decryptContext(array $fields, array $data, string $secret): array
    {
        foreach ($fields as $field) {
            if (v::key($field)->validate($data)) {
                $data[$field] = unserialize(self::decryptWithPassword($data[$field], $secret));
            }
        }

        return $data;
    }
}