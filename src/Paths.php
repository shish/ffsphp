<?php

declare(strict_types=1);

namespace FFSPHP;

class Paths
{
    public static function is_absolute(string $path): bool
    {
        // Check if the path starts with a slash (Unix-like systems)
        // or a drive letter followed by a colon and a slash (Windows)
        return $path !== "" && ($path[0] === '/' || (preg_match('/^[a-zA-Z]:\//', $path) === 1));
    }

    public static function abspath(string $path, ?string $cwd = null): string
    {
        $cwd = $cwd ?? getcwd();
        if ($cwd === false) {
            throw new \RuntimeException("Current working directory could not be determined.");
        }
        if (!self::is_absolute($cwd)) {
            throw new \InvalidArgumentException("Current working directory must be an absolute path.");
        }

        if (!self::is_absolute($path)) {
            $path = "$cwd/$path";
        }
        $parts = explode("/", $path);
        $res = [];
        foreach ($parts as $part) {
            if ($part == "..") {
                array_pop($res);
            } elseif ($part != ".") {
                $res[] = $part;
            }
        }
        return implode("/", $res);
    }

    public static function relative_path(string $path, string $reference, ?string $cwd = null): string
    {
        $path = self::abspath($path, $cwd);
        $reference = self::abspath($reference, $cwd);

        $path_parts = explode("/", $path);
        $reference_parts = explode("/", $reference);

        // Remove ".." and "." component

        // Now that we have absolute paths, we can just split them and compare

        // reference filename doesn't matter, only ref dir
        $reference_parts = array_slice($reference_parts, 0, count($reference_parts) - 1);

        $common = 0;
        while (
            $common < min(count($path_parts), count($reference_parts))
            && $path_parts[$common] == $reference_parts[$common]
        ) {
            $common++;
        }
        $res = [];
        for ($i = 0; $i < count($reference_parts) - $common; $i++) {
            $res[] = "..";
        }
        for ($i = $common; $i < count($path_parts); $i++) {
            $res[] = $path_parts[$i];
        }
        return implode("/", $res);
    }
}
