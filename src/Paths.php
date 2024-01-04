<?php

declare(strict_types=1);

namespace FFSPHP;

class Paths
{
    public static function abspath(string $path, ?string $cwd = null): string
    {
        $cwd = $cwd ?? getcwd();
        assert($cwd, "cwd must be set");
        assert($cwd[0] == "/", "cwd must be absolute");
        if($path[0] != "/") {
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
        while ($common < count($path_parts) && $common < count($reference_parts) && $path_parts[$common] == $reference_parts[$common]) {
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
