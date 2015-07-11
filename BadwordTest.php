<?php

require 'Badword.php';

class BadwordTest extends PHPUnit_Framework_TestCase
{

    protected $badword;

    protected function setUp()
    {
        $this->badword = new Badword('badword.tree');
    }

    public function existProvider()
    {
        return array(
            array(false, '*'),
            array(false, '**'),
            array(true, 'sm'),
            array(true, ' sm'),
            array(true, 'sm '),
            array(true, ' sm '),
            array(true, '*sm'),
            array(true, 'sm-'),
            array(true, '-sm'),
            array(false, '_sm'),
            array(false, 'sm_'),
            array(false, 'sms'),
            array(false, 'smart'),
            array(true, 'sm art'),
        );
    }


    /**
     * @dataProvider existProvider
     */
    public function testExist($expected, $str)
    {
        $this->assertSame($expected, $this->badword->exist($str));
    }


    public function replaceProvider()
    {
        return array(
            array('*', '*'),
            array('**', '**'),
            array(' **', ' sm'),
            array('** ', 'sm '),
            array(' ** ', ' sm '),
            array('***', '*sm'),
            array('**-', 'sm-'),
            array('-**', '-sm'),
            array('_sm', '_sm'),
            array('sm_', 'sm_'),
            array('sms', 'sms'),
            array('smart', 'smart'),
            array('** art', 'sm art'),
            array('********', '甲乙甲乙丙丁丙丁'),
            array('****', '张三李四'),
        );
    }


    /**
     * @dataProvider replaceProvider
     */
    public function testReplace($expected, $str)
    {
        $this->assertSame($expected, $this->badword->replace($str));
    }
}