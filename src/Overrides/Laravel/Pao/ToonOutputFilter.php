<?php

namespace Jayi\Toon\Overrides\Laravel\Pao;

use Jayi\Toon\Toon;
use php_user_filter;

/**
 * PHP stream filter that intercepts PAO's JSON output and re-encodes it as TOON.
 *
 * Attached to STDOUT by the Pao Plugin. Detects JSON objects with a `result`
 * key (PAO's test result format) and converts them to TOON on the fly.
 */
class ToonOutputFilter extends php_user_filter
{
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $data = $bucket->data;
            $decoded = json_decode(trim($data), true);

            if (is_array($decoded) && isset($decoded['result'])) {
                $bucket->data = Toon::encode($decoded).PHP_EOL;
            }

            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
