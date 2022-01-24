<?php

namespace Copy2Cloud\Tests\Base\Utilities;

use Copy2Cloud\Base\Utilities\Json;
use PHPUnit\Framework\TestCase;

final class JsonTest extends TestCase
{
    const TEST_PAYLOAD = [
        'test_key' => 'test_val',
        1 => 'one',
        '2nd' => 'second',
        'parent_key' => [
            'child_item',
            'another_child_item' => [1, 'two'],
            'special_characters' => ["\n", '$', '"', '|', '\''],
        ],
    ];

    const MOCK_JSON_FILE_PATH = __DIR__ . '/../../.mock/base_json_encoded_data.json';

    public function testEncode()
    {
        $encodedData = Json::encode(self::TEST_PAYLOAD);

        $this->assertJson($encodedData);
        $this->assertJsonStringEqualsJsonFile(self::MOCK_JSON_FILE_PATH, $encodedData);
    }

    public function testDecode()
    {
        $decodedData = Json::decode(file_get_contents(self::MOCK_JSON_FILE_PATH));
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('test_key', $decodedData);
    }

    public function testDecodeAsObject()
    {
        $decodedData = Json::decode(file_get_contents(self::MOCK_JSON_FILE_PATH), false);
        $this->assertIsObject($decodedData);
        $this->assertObjectHasAttribute('test_key', $decodedData);
    }
}