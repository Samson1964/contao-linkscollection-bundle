<?php

namespace Schachbulle\ContaoLinkscollectionBundle\Tests;

use Schachbulle\ContaoLinkscollectionBundle\ContaoLinkscollectionBundle;
use PHPUnit\Framework\TestCase;

class ContaoLinkscollectionBundleTest extends TestCase
{
	public function testCanBeInstantiated()
	{
		$bundle = new ContaoLinkscollectionBundle();
	
		$this->assertInstanceOf('Schachbulle\ContaoLinkscollectionBundle\ContaoLinkscollectionBundle', $bundle);
	}
}
