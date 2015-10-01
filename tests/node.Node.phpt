<?php

/**
 * @author Andrej Rypak <xrypak@gmail.com>
 */

namespace Oliva\Test;

require_once __DIR__ . '/bootstrap.php';

use Tester,
	Tester\Assert;
use Oliva\Utils\Tree\Node\Node;


/**
 * @see \Oliva\Utils\Tree\Node\NodeBase
 */
class NodeTest extends Tester\TestCase
{


	protected function setUp()
	{
		parent::setUp();
	}


	protected function tearDown()
	{
		parent::tearDown();
	}


	public function testScalar()
	{
		$node = new Node(100);
		Assert::same(100, $node->getObject());

		// cannot call a function on a scalar
		Assert::exception(function() use ($node) {
			$node->foo();
		}, 'BadMethodCallException');

		// cannot get/set a member of a scalar
		Assert::exception(function() use ($node) {
			$node->foo;
		}, 'RuntimeException');
	}


	public function testArray()
	{
		$array = [1, 2, 3];
		$node = new Node($array);
		Assert::same($array, $node->getObject());

		// cannot call a function on an array
		Assert::exception(function() use ($node) {
			$node->foo();
		}, 'BadMethodCallException');

		// requesting $array['foo'] should raise E_NOTICE
		Assert::error(function() use ($node) {
			$node->foo;
		}, E_NOTICE);

		foreach (array_keys($array) as $key) {
			Assert::same($array[$key], $node->{$key});
		}

		$clone = $array;
		$node[] = 5;
		$clone[] = 5;
		Assert::same($clone, $node->getObject());

		$node[100] = 'foo';
		$clone[100] = 'foo';
		Assert::same($clone, $node->getObject());

		$node[''] = 6;
		$clone[''] = 6;
		Assert::same($clone, $node->getObject());

		// this case is a known bug!
		// cannot be solved without breaking the ability to add by calling $node[] = foo; (PHP 5.6)
		$node[NULL] = 7;
		$clone[NULL] = 7;
		$buggy = [1, 2, 3, 5, 100 => 'foo', '' => 6, 7];
		Assert::notSame($clone, $node->getObject()); // Assert::same will fail - see below
		Assert::same($buggy, $node->getObject()); // $clone !== $buggy
	}


	public function testObject()
	{
		$dataObject = new DataWrapper(10, 'foobar');
		$node = new Node($dataObject);
		Assert::same($dataObject, $node->getObject());

		Assert::same('foobar', $node->title);
		Assert::same('bar', $node->foo);

		$node->scalar = 4;
		$node->array = $array = [1, 2, 3, [10, 20, 30]];
		$node->object = $object = new DataWrapper('fooId');

		Assert::same(4, $node->scalar);
		Assert::same($array, $node->array);
		Assert::same($object, $node->object);

		// modifying an index of $node->array should raise E_NOTICE - indirect modification
		Assert::error(function() use ($node) {
			$node->array[] = 'foo';
		}, E_NOTICE, 'Indirect modification of overloaded property Oliva\Utils\Tree\Node\Node::$array has no effect');
		Assert::error(function() use ($node) {
			$node->array['foo'] = 'bar';
		}, E_NOTICE, 'Indirect modification of overloaded property Oliva\Utils\Tree\Node\Node::$array has no effect');

		// it works on objects though...
		$object->scalar = 'success';
		Assert::same('success', $node->object->scalar);

		// test function calls
		Assert::same($dataObject, $node->setAttribute('h1', '20px')); // oh, well... one would probably expect setAttribute() to return $node, but...
		Assert::same($node->getAttribute('h1'), '20px');

		// __call test
		Assert::same('Calling "foobar" on an instance of "Oliva\Test\DataWrapper" with 0 arguments.', $node->foobar());
		Assert::same('Calling "foobar2" on an instance of "Oliva\Test\DataWrapper" with 3 arguments.', $node->foobar2(1, 2, 6));
	}

}

// run the test
(new NodeTest)->run();


