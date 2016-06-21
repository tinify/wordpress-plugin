<?php

require_once dirname(__FILE__) . "/TinyTestCase.php";

class Tiny_Compress_Test extends TinyTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function testEstimateCostFree() {
        $this->assertEquals(150 * 0, Tiny_Compress::estimate_cost(150, 0));
    }

    public function testEstimateCostNormalAndFree() {
        $this->assertEquals(350 * 0 + 2650 * 0.009, Tiny_Compress::estimate_cost(3000, 150));
    }

    public function testEstimateCostCheapAndNormalAndFree() {
        $this->assertEquals(500 * 0 + 9500 * 0.009 + 40000 * 0.002, Tiny_Compress::estimate_cost(50000, 0));
    }
}
