<?php

declare(strict_types=1);

require "vendor/autoload.php";

use FFSPHP\Paths;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

class PathsTest extends TestCase
{
    public function test_abspath(): void
    {
        $this->assertEquals("/foo/bar", Paths::abspath("/foo/bar", cwd: "/foo"));
        $this->assertEquals("/foo/bar", Paths::abspath("bar", cwd: "/foo"));
        $this->assertEquals("/foo/bar", Paths::abspath("./bar", cwd: "/foo"));
        $this->assertEquals("/bar", Paths::abspath("../bar", cwd: "/foo"));
        $this->assertEquals("/foo/qux/cake", Paths::abspath("../qux/cake", cwd: "/foo/bar"));
        $this->assertEquals("/foo/qux/cake", Paths::abspath("../blah/../qux/./cake", cwd: "/foo/bar"));
    }

    #[Depends("test_abspath")]
    public function test_relative_path(): void
    {
        // same dir
        $this->assertEquals("foo.css", Paths::relative_path("foo.css", "bar.css"));
        // parent dir
        $this->assertEquals("../foo.css", Paths::relative_path("foo.css", "gen/bar.css"));
        // child dir
        $this->assertEquals("source/foo.css", Paths::relative_path("source/foo.css", "bar.css"));
        // sibling dir
        $this->assertEquals("../source/foo.css", Paths::relative_path("source/foo.css", "gen/bar.css"));
        // absolute paths
        $this->assertEquals("../source/foo.css", Paths::relative_path("/app/source/foo.css", "/app/gen/bar.css", cwd: "/app"));
        $this->assertEquals("../source/foo.css", Paths::relative_path("/app/source/foo.css", "gen/bar.css", cwd: "/app"));
    }
}
