<?php

namespace Swaggest\JsonDiff\Tests;

use Swaggest\JsonDiff\Exception;
use Swaggest\JsonDiff\JsonDiff;
use Swaggest\JsonDiff\JsonPatch;

class JsonPatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @throws Exception
     */
    public function testImportExport()
    {
        $data = json_decode(<<<'JSON'
[
  { "op": "replace", "path": "/baz", "value": "boo" },
  { "op": "add", "path": "/hello", "value": ["world"] },
  { "op": "remove", "path": "/foo"}
]
JSON
        );
        $patch = JsonPatch::import($data);

        $exported = JsonPatch::export($patch);

        $diff = new JsonDiff($data, $exported);
        $this->assertSame(0, $diff->getDiffCnt());
    }


    public function testNull()
    {
        $originalJson = <<<'JSON'
{
    "key2": 2,
    "key3": null,
    "key4": [
        {"a":1, "b":true}, {"a":2, "b":false}, {"a":3}
    ]
}
JSON;

        $newJson = <<<'JSON'
{
    "key3": null
}
JSON;

        $expected = <<<'JSON'
{
    "key2": 2,
    "key4": [
        {"a":1, "b":true}, {"a":2, "b":false}, {"a":3}
    ]
}
JSON;

        $r = new JsonDiff(json_decode($originalJson), json_decode($newJson), JsonDiff::JSON_URI_FRAGMENT_ID);
        $this->assertSame(array(
            '#/key2',
            '#/key4',
        ), $r->getRemovedPaths());

        $this->assertSame(2, $r->getRemovedCnt());

        $this->assertSame(
            json_encode(json_decode($expected), JSON_PRETTY_PRINT),
            json_encode($r->getRemoved(), JSON_PRETTY_PRINT)
        );

    }


    public function testInvalidPatch()
    {
        $this->setExpectedException(get_class(new \TypeError()),
            'Argument 1 passed to Swaggest\JsonDiff\JsonPatch::import() must be of the type array, integer given');
        JsonPatch::import(123);
    }

    public function testMissingOp()
    {
        $this->setExpectedException(get_class(new Exception()), 'Missing "op" in operation data');
        JsonPatch::import(array((object)array('path' => '/123')));
    }

    public function testMissingPath()
    {
        $this->setExpectedException(get_class(new Exception()), 'Missing "path" in operation data');
        JsonPatch::import(array((object)array('op' => 'wat')));
    }

    public function testInvalidOp()
    {
        $this->setExpectedException(get_class(new Exception()), 'Unknown "op": wat');
        JsonPatch::import(array((object)array('op' => 'wat', 'path' => '/123')));
    }

    public function testMissingFrom()
    {
        $this->setExpectedException(get_class(new Exception()), 'Missing "from" in operation data');
        JsonPatch::import(array((object)array('op' => 'copy', 'path' => '/123')));
    }

    public function testMissingValue()
    {
        $this->setExpectedException(get_class(new Exception()), 'Missing "value" in operation data');
        JsonPatch::import(array(array('op' => 'add', 'path' => '/123')));
    }

    public function testApply()
    {
        $p = JsonPatch::import(array(array('op' => 'copy', 'path' => '/1', 'from' => '/0')));
        $original = array('AAA');
        $p->apply($original);
        $this->assertSame(array('AAA', 'AAA'), $original);
    }

}