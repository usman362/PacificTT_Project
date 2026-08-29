<?php

namespace App\Support;

class Asset
{
    /**
     * A URL for a file in public/ that changes whenever the file does.
     *
     * Without this the browser holds on to whatever copy it cached first,
     * so a CSS or JS fix we deploy is invisible until someone thinks to
     * hard-refresh. Stamping the file's own modification time onto the
     * query string means a changed file is simply a different URL.
     */
    public static function url(string $path): string
    {
        $url = asset($path);
        $file = public_path($path);

        if (is_file($file)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($file);
        }

        return $url;
    }
}
