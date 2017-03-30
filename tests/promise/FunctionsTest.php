<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/2/22
 * Time: 下午6:01
 */

namespace swooleunit\controllers;


use swooleunit\TestCase;
use yii\swoole\promise\Promise;

class FunctionsTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->mockApplication();
    }

    public function testSleep()
    {
        $time = microtime(true);
        $el = \Yii::$app->get('eventloop');
        Promise\sleep(0.2, $el->loop);
        $time = microtime(true) - $time;

        $this->assertEquals(0.2, $time, '', 0.1);
    }
}
