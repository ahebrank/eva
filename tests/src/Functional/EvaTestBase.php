<?php

namespace Drupal\Tests\eva\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Browser testing for Eva.
 *
 */
abstract class EvaTestBase extends BrowserTestBase {

	/**
	 * Modules to install.
	 *
	 * @var array
	 */
	public static $modules = [
		'eva',
		'eva_test',
		'node',
		'views',
		'user',
	];

	/**
	 * Number of Articles to generate.
	 */
	protected $article_count = 20;

	/**
	 * Hold the page NID.
	 */
	protected $page_nid = 0;

	/**
	* {@inheritdoc}
	*/
	protected function setUp() {
		parent::setUp();

		$this->makeNodes();
	}

	/**
	* Create some example nodes.
	*/
	protected function makeNodes() {
		$node = $this->createNode([
			'title' => 'Test Page',
			'type' => 'page',
		]);
		$this->page_nid = $node->id();

		for ($i = 0; $i < $this->article_count; $i++) {
			$this->createNode([
				'title' => sprintf('Article %d', $i + 1),
				'type' => 'article',
			]);
		}
	}

}
