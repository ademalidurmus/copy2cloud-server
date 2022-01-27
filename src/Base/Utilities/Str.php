<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Utilities;

use Copy2Cloud\Base\Enums\StrCharacters;
use Copy2Cloud\Base\Enums\StrTypes;

class Str
{
    /**
     * @param int $length
     * @param StrTypes $type
     * @param StrCharacters $character
     * @param string $additionalCharacters
     * @return string
     */
    public static function generateRandomStr(
        int           $length = 40,
        StrTypes      $type = StrTypes::mixed,
        StrCharacters $character = StrCharacters::mixed,
        string        $additionalCharacters = ''
    ): string
    {
        $numbers = '0123456789';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $characters = match ($character) {
            StrCharacters::uppercase => match ($type) {
                StrTypes::number => $numbers,
                StrTypes::string => $uppercase,
                default => $numbers . $uppercase,
            },
            StrCharacters::lowercase => match ($type) {
                StrTypes::number => $numbers,
                StrTypes::string => $lowercase,
                default => $numbers . $lowercase,
            },
            default => match ($type) {
                StrTypes::number => $numbers,
                StrTypes::string => $lowercase . $uppercase,
                default => $numbers . $lowercase . $uppercase,
            },
        };

        $characters .= $additionalCharacters;

        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
