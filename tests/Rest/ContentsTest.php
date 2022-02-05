<?php

namespace Copy2Cloud\Tests\Rest;

use Copy2Cloud\Core\Contents\Content;
use Copy2Cloud\Rest\Contents;
use PHPUnit\Framework\TestCase;

class ContentsTest extends TestCase
{
    /**
     * @return void
     */
    public function testPrepareResponse()
    {
        $content = new Content();
        $content->key = 'test';
        $content->content = 'test';
        $content->insert_time = time();
        $content->expire_time = time() + 100;
        $content->secret = '123456';
        $content->destroy_count = -1;
        $content->attributes = [];
        $content->acl = [];

        $response = Contents::prepareResponse($content, ['secret']);
        $this->assertIsArray($response);
        $this->assertArrayNotHasKey('secret', $response);
        $this->assertEquals('test', $response['content']);
    }
}
