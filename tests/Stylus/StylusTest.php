<?php

namespace Stylus\Test;

use Stylus\Stylus;

require('src/Stylus/Exception.php');
require('src/Stylus/Stylus.php');

class StylusTest extends \PHPUnit_Framework_TestCase {

    function testRenderingOfString() {

        $stylus = new Stylus();

        $in = "body\n  color black";
        $out = $stylus->fromString($in)->toString();

        $correct = "body {\n\tcolor: black;\n}\n";

        $this->assertEquals(str_replace("\r", '', $correct), str_replace("\r", '', $out));

    }

    function testBlockParseColonNoSpace(){

        $stylus = new Stylus();

        $in = "body\n  color:black";
        $out = $stylus->fromString($in)->toString();

        $correct = "body {\n\tcolor: black;\n}\n";

        $this->assertEquals(str_replace("\r", '', $correct), str_replace("\r", '', $out));
    }

    function testBlockParseColonWithSpace(){

        $stylus = new Stylus();

        $in = "body\n  color: black";
        $out = $stylus->fromString($in)->toString();

        $correct = "body {\n\tcolor: black;\n}\n";

        $this->assertEquals(str_replace("\r", '', $correct), str_replace("\r", '', $out));
    }

    function testBlockParseSpaceWithColon(){

        $stylus = new Stylus();

        $in = "body\n  color :black";
        $out = $stylus->fromString($in)->toString();

        $correct = "body {\n\tcolor: black;\n}\n";

        $this->assertEquals(str_replace("\r", '', $correct), str_replace("\r", '', $out));
    }

    function testRenderingFromFile() {

        $stylus = new Stylus();

        $stylus->setReadDir('tests/data');

        $in = 'test.styl';
        $out = $stylus->fromFile($in)->toString();

        $correct = file_get_contents('tests/data/test.css');

        $this->assertEquals($correct, $out);

    }

    function testAnimationsRenderingFromFile() {

        $stylus = new Stylus();

        $stylus->setReadDir('tests/data');

        $in = 'animations.styl';
        $out = $stylus->fromFile($in)->toString();

        $correct = file_get_contents('tests/data/animations.css');

        $this->assertEquals($correct, $out);

    }

}
