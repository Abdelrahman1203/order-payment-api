<?php

namespace App\Support;

class NullToEmptyArray
{
    public static function convert(mixed $value): mixed
    {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            return $value;
        }

        $result = [];

        foreach ($value as $key => $item) {
            $result[$key] = self::convert($item);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function convertPaginationEnvelope(array $payload): array
    {
        if (isset($payload['links']) && is_array($payload['links'])) {
            $payload['links'] = self::convert($payload['links']);
        }

        if (isset($payload['meta']) && is_array($payload['meta'])) {
            $payload['meta'] = self::convert($payload['meta']);
        }

        return $payload;
    }
}
