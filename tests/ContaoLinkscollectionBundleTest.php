<?php

namespace Samson\ContaoLinkscollectionBundle\Tests;

use Samson\ContaoLinkscollectionBundle\ContaoLinkscollectionBundle;
use PHPUnit\Framework\TestCase;

class ContaoLinkscollectionBundleTest extends TestCase
{
	public function testCanBeInstantiated()
	{
		$bundle = new ContaoLinkscollectionBundle();
	
		$this->assertInstanceOf('Samson\ContaoLinkscollectionBundle\ContaoLinkscollectionBundle', $bundle);
	}
}
