<?php

declare(strict_types=1);

require "vendor/autoload.php";

use FFSPHP\Paths;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

class PathsTest extends TestCase
{
    public function test_abspath_unix(): void
    {
        $this->assertEquals("/foo/bar", Paths::abspath("/foo/bar", cwd: "/foo"));
        $this->assertEquals("/foo/bar", Paths::abspath("bar", cwd: "/foo"));
        $this->assertEquals("/foo/bar", Paths::abspath("./bar", cwd: "/foo"));
        $this->assertEquals("/bar", Paths::abspath("../bar", cwd: "/foo"));
        $this->assertEquals("/foo/qux/cake", Paths::abspath("../qux/cake", cwd: "/foo/bar"));
        $this->assertEquals("/foo/qux/cake", Paths::abspath("../blah/../qux/./cake", cwd: "/foo/bar"));
    }

    public function test_abspath_windows(): void
    {
        $this->assertEquals("C:/foo/bar", Paths::abspath("C:/foo/bar", cwd: "C:/foo"));
        $this->assertEquals("C:/foo/bar", Paths::abspath("bar", cwd: "C:/foo"));
        $this->assertEquals("C:/foo/bar", Paths::abspath("./bar", cwd: "C:/foo"));
        $this->assertEquals("C:/bar", Paths::abspath("../bar", cwd: "C:/foo"));
        $this->assertEquals("C:/foo/qux/cake", Paths::abspath("../qux/cake", cwd: "C:/foo/bar"));
        $this->assertEquals("C:/foo/qux/cake", Paths::abspath("../blah/../qux/./cake", cwd: "C:/foo/bar"));
    }

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
    }

    #[Depends("test_abspath_unix")]
    public function test_relative_path_unix(): void
    {
        $this->assertEquals("../source/foo.css", Paths::relative_path("/app/source/foo.css", "/app/gen/bar.css", cwd: "/app"));
        $this->assertEquals("../source/foo.css", Paths::relative_path("/app/source/foo.css", "gen/bar.css", cwd: "/app"));
    }

    #[Depends("test_abspath_windows")]
    public function test_relative_path_windows(): void
    {
        $this->assertEquals("../source/foo.css", Paths::relative_path("C:/app/source/foo.css", "C:/app/gen/bar.css", cwd: "C:/app"));
        $this->assertEquals("../source/foo.css", Paths::relative_path("C:/app/source/foo.css", "gen/bar.css", cwd: "C:/app"));
    }
}
