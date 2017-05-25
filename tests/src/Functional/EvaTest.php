<?php

namespace Drupal\Tests\eva\Functional;

/**
 * Preliminary tests for Eva.
 *
 * @group eva
 */
class EvaTest extends EvaTestBase {

    /**
     * Assert that the Eva of Articles appears on a Page.
     */
    function testEvaOnPage() {
        $assert = $this->assertSession();

        $this->drupalGet('/node/' . $this->page_nid);
        $assert->statusCodeEquals(200);

        $this->assertEquals(
            $this->article_count,
            \count($this->xpath('//div[contains(@class, "view-eva")]//div[contains(@class, "views-row")]')),
            sprintf('Found %d articles in Eva.', $this->article_count)
        );
    }

}