<?php
/**
 * Vonage Client Library for PHP
 *
 * @copyright Copyright (c) 2017 Vonage, Inc. (http://vonage.com)
 * @license   https://github.com/vonage/vonage-php/blob/master/LICENSE MIT License
 */

namespace VonageTest\Call;

use Vonage\Call\Talk;
use VonageTest\Psr7AssertionTrait;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\Response;
use PHPUnit\Framework\TestCase;

class TalkTest extends TestCase
{
    use Psr7AssertionTrait;

    protected $id;

    /**
     * @var Talk
     */
    protected $entity;

    /**
     * @var Talk
     */
    protected $new;

    protected $class;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    protected $vonageClient;

    public function setUp(): void
    {
        $this->id = '3fd4d839-493e-4485-b2a5-ace527aacff3';
        $this->class = Talk::class;

        $this->entity = @new Talk('3fd4d839-493e-4485-b2a5-ace527aacff3');
        $this->new = @new Talk();

        $this->vonageClient = $this->prophesize('Vonage\Client');
        $this->vonageClient->getApiUrl()->willReturn('https://api.nexmo.com');
        $this->entity->setClient($this->vonageClient->reveal());
        $this->new->setClient($this->vonageClient->reveal());
    }

    public function testHasId()
    {
        $this->assertSame($this->id, $this->entity->getId());
    }

    /**
     * @param $value
     * @param $param
     * @param $setter
     * @param $expected
     * @dataProvider setterParameters
     */
    public function testSetParams($value, $param, $setter, $expected)
    {
        $this->entity->$setter($value);
        $data = $this->entity->jsonSerialize();
        $this->assertEquals($expected, $data[$param]);
    }

    /**
     * @param $value
     * @param $param
     * @dataProvider setterParameters
     */
    public function testArrayParams($value, $param)
    {
        $this->entity[$param] = $value;

        $data = $this->entity->jsonSerialize();
        $this->assertEquals($value, $data[$param]);
    }

    public function setterParameters()
    {
        return [
            ['something I want to say', 'text', 'setText', 'something I want to say'],
            ['Ivy', 'voice_name', 'setVoiceName', 'Ivy'],
            [0, 'loop', 'setLoop', '0'],
            [1, 'loop', 'setLoop', '1'],
        ];
    }

    public function testPutMakesRequest()
    {
        $this->entity->setText('Bingo!');

        $callId = $this->id;
        $entity = $this->entity;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($callId, $entity) {
            $this->assertRequestUrl('api.nexmo.com', '/v1/calls/' . $callId . '/talk', 'PUT', $request);
            $expected = json_decode(json_encode($entity), true);

            $request->getBody()->rewind();
            $body = json_decode($request->getBody()->getContents(), true);
            $request->getBody()->rewind();

            $this->assertEquals($expected, $body);
            return true;
        }))->willReturn($this->getResponse('talk', '200'));

        $event = @$this->entity->put();

        $this->assertInstanceOf('Vonage\Call\Event', $event);
        $this->assertSame('ssf61863-4a51-ef6b-11e1-w6edebcf93bb', $event['uuid']);
        $this->assertSame('Talk started', $event['message']);
    }

    public function testPutCanReplace()
    {
        $class = $this->class;

        $entity = @new $class;
        $entity->setText('Ding!');

        $callId = $this->id;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($callId, $entity) {
            $this->assertRequestUrl('api.nexmo.com', '/v1/calls/' . $callId . '/talk', 'PUT', $request);
            $expected = json_decode(json_encode($entity), true);

            $request->getBody()->rewind();
            $body = json_decode($request->getBody()->getContents(), true);
            $request->getBody()->rewind();

            $this->assertEquals($expected, $body);
            return true;
        }))->willReturn($this->getResponse('talk', '200'));

        $event = @$this->entity->put($entity);

        $this->assertInstanceOf('Vonage\Call\Event', $event);
        $this->assertSame('ssf61863-4a51-ef6b-11e1-w6edebcf93bb', $event['uuid']);
        $this->assertSame('Talk started', $event['message']);
    }

    public function testInvokeProxiesPutWithArgument()
    {
        $object = $this->entity;

        $this->vonageClient->send(Argument::any())->willReturn($this->getResponse('talk', '200'));
        $test = $object();
        $this->assertSame($this->entity, $test);

        $this->vonageClient->send(Argument::any())->shouldNotHaveBeenCalled();

        $class = $this->class;
        $entity = @new $class();
        $entity->setText('Hello!');

        $event = @$object($entity);

        $this->assertInstanceOf('Vonage\Call\Event', $event);
        $this->assertSame('ssf61863-4a51-ef6b-11e1-w6edebcf93bb', $event['uuid']);
        $this->assertSame('Talk started', $event['message']);

        $this->vonageClient->send(Argument::any())->shouldHaveBeenCalled();
    }

    public function testDeleteMakesRequest()
    {
        $callId = $this->id;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($callId) {
            $this->assertRequestUrl('api.nexmo.com', '/v1/calls/' . $callId . '/talk', 'DELETE', $request);
            return true;
        }))->willReturn($this->getResponse('talk-delete', '200'));

        $event = @$this->entity->delete();

        $this->assertInstanceOf('Vonage\Call\Event', $event);
        $this->assertSame('ssf61863-4a51-ef6b-11e1-w6edebcf93bb', $event['uuid']);
        $this->assertSame('Talk stopped', $event['message']);
    }

    /**
     * Get the API response we'd expect for a call to the API.
     *
     * @param string $type
     * @return Response
     */
    protected function getResponse($type = 'success', $status = 200)
    {
        return new Response(fopen(__DIR__ . '/responses/' . $type . '.json', 'r'), $status);
    }
}
