<?php

namespace App\Service\base;

use App\Service\base\TestHelper;
use PHPUnit\Framework\TestCase;
use DOMDocument;
use DOMAttr;

class TestHelperFunctionsTest extends TestCase
{


    public function setup(): void
    {
        $dom = new DOMDocument("1.0");
        $a = $dom->createElement('a');
        $a->nodeValue = 'test';
        $this->link = $a;
    }
    public function testtrue(): void
    {
        $this->link->setAttribute('href', 'http://google.com');
        $this->assertTrue(TestHelper::pass($this->link, ['#', 'mailto:', 'tel:']));
    }
    public function testTrueForDieze(): void
    {
        $this->link->setAttribute('href', '#');
        $this->assertTrue(TestHelper::pass($this->link, []));
    }
    public function testFalseForDieze(): void
    {
        $this->link->setAttribute('href', '#');
        $this->assertFalse(TestHelper::pass($this->link, ['#']));
    }
    public function testFalseFormailto(): void
    {
        $this->link->setAttribute('href', 'mailto:a@aa.aa');
        $this->assertFalse(TestHelper::pass($this->link, ['mailto:']));
    }
    public function testFalseHttp(): void
    {
        $this->link->setAttribute('href', 'http://google.com');
        $this->assertFalse(TestHelper::pass($this->link, ['http:']));
    }
}
