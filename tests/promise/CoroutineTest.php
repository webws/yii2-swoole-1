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
use yii\swoole\promise\Coroutine;
use ReflectionClass;
use yii\swoole\promise\PromiseInterface;

class CoroutineTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->mockApplication();
    }

    /**
     * @dataProvider promiseInterfaceMethodProvider
     *
     * @param string $method
     * @param array $args
     */
    public function testShouldProxyPromiseMethodsToResultPromise($method, $args = [])
    {
        $coroutine = new Coroutine(function () { yield 0; });
        $mockPromise = $this->getMockForAbstractClass(PromiseInterface::class);
        call_user_func_array([$mockPromise->expects($this->once())->method($method), 'with'], $args);

        $resultPromiseProp = (new ReflectionClass(Coroutine::class))->getProperty('result');
        $resultPromiseProp->setAccessible(true);
        $resultPromiseProp->setValue($coroutine, $mockPromise);

        call_user_func_array([$coroutine, $method], $args);
    }

    public function promiseInterfaceMethodProvider()
    {
        return [
            ['then', [null, null]],
            ['otherwise', [function () {}]],
            ['wait', [true]],
            ['getState', []],
            ['resolve', [null]],
            ['reject', [null]],
        ];
    }

    public function testShouldCancelResultPromiseAndOutsideCurrentPromise()
    {
        $coroutine = new Coroutine(function () { yield 0; });

        $mockPromises = [
            'result' => $this->getMockForAbstractClass(PromiseInterface::class),
            'currentPromise' => $this->getMockForAbstractClass(PromiseInterface::class),
        ];
        foreach ($mockPromises as $propName => $mockPromise) {
            /**
             * @var $mockPromise \PHPUnit_Framework_MockObject_MockObject
             */
            $mockPromise->expects($this->once())
                ->method('cancel')
                ->with();

            $promiseProp = (new ReflectionClass(Coroutine::class))->getProperty($propName);
            $promiseProp->setAccessible(true);
            $promiseProp->setValue($coroutine, $mockPromise);
        }

        $coroutine->cancel();
    }

    public function testWaitShouldResolveChainedCoroutines()
    {
        $promisor = function () {
            return \yii\swoole\promise\coroutine(function () {
                yield $promise = new Promise(function () use (&$promise) {
                    $promise->resolve(1);
                });
            });
        };

        $promise = $promisor()->then($promisor)->then($promisor);

        $this->assertSame(1, $promise->wait());
    }

    public function testWaitShouldHandleIntermediateErrors()
    {
        $promise = Promise\coroutine(function () {
            yield $promise = new Promise(function () use (&$promise) {
                $promise->resolve(1);
            });
        })
            ->then(function () {
                return Promise\coroutine(function () {
                    yield $promise = new Promise(function () use (&$promise) {
                        $promise->reject(new \Exception);
                    });
                });
            })
            ->otherwise(function (\Exception $error = null) {
                if (!$error) {
                    self::fail('Error did not propagate.');
                }
                return 3;
            });

        $this->assertSame(3, $promise->wait());
    }

    function fetch($url) {
        $dns_lookup = Promise\promisify('swoole_async_dns_lookup');
//        $writefile = Promise\promisify('swoole_async_writefile');
        $url = parse_url($url);
        list($host, $ip) = (yield $dns_lookup($url['host']));
        $cli = new \swoole_http_client($ip, isset($url['port']) ? $url['port'] : 80);
        $cli->setHeaders([
            'Host' => $host,
            "User-Agent" => 'Chrome/49.0.2587.3',
        ]);
        $get = Promise\promisify([$cli, 'get']);
        yield $get($url['path']);
//        list($filename) = (yield $writefile(basename($url['path']), $cli->body));
        //echo $cli->body."\r\n";
        $cli->close();
    }

    function testCo(){
        $urls = array(
//            'http://b.hiphotos.baidu.com/baike/c0%3Dbaike116%2C5%2C5%2C116%2C38/sign=5f4519ba037b020818c437b303b099b6/472309f790529822434d08dcdeca7bcb0a46d4b6.jpg',
//            'http://f.hiphotos.baidu.com/baike/c0%3Dbaike116%2C5%2C5%2C116%2C38/sign=1c37718b3cc79f3d9becec62dbc8a674/38dbb6fd5266d016dc2eaa5c902bd40735fa358a.jpg',
//            'http://h.hiphotos.baidu.com/baike/c0%3Dbaike116%2C5%2C5%2C116%2C38/sign=edd05c9c502c11dfcadcb771024e09b5/d6ca7bcb0a46f21f3100c52cf1246b600c33ae9d.jpg',
//            'http://a.hiphotos.baidu.com/baike/c0%3Dbaike92%2C5%2C5%2C92%2C30/sign=4693756e8094a4c21e2eef796f9d70b0/54fbb2fb43166d22df5181f5412309f79052d2a9.jpg',
            'http://a.hiphotos.baidu.com/baike/c0%3Dbaike92%2C5%2C5%2C92%2C30/sign=9388507144a98226accc2375ebebd264/faf2b2119313b07eb2cc820c0bd7912397dd8c45.jpg',
        );

        foreach ($urls as $url) {
            $p = Promise\coroutine($this->fetch($url));
            $ret = $p->wait();
        }
    }
}
