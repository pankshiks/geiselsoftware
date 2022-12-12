<?php

namespace Drupal\Tests\page_manager_ui\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the page parameters capabilities.
 *
 * @group page_manager_ui
 */
class PageParametersTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'page_manager_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('system_branding_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalLogin($this->drupalCreateUser([
      'administer pages',
      'access administration pages',
      'view the administration theme',
      'create article content',
    ]));
  }

  /**
   * Tests page parameters when adding a page and when editing it.
   */
  public function testParameters() {
    $node = $this->drupalCreateNode(['type' => 'article']);

    // Create a page.
    $this->drupalGet('admin/structure');
    $this->clickLink('Pages');
    $this->clickLink('Add page');
    $edit = [
      'id' => 'foo',
      'label' => 'Foo',
      'path' => 'admin/foo/{node}',
      'variant_plugin_id' => 'block_display',
      'use_admin_theme' => TRUE,
      'description' => 'Sample test page.',
    ];
    $this->submitForm($edit, 'Next');

    // Test the 'Parameters' step.
    $this->assertSession()->titleEquals('Page parameters | Drupal');
    $access_path = 'admin/structure/page_manager/add/foo/parameters';
    $this->assertSession()->addressEquals($access_path . '?js=nojs');
    $this->assertSession()->pageTextNotContains('There are no parameters defined for this page.');

    // Edit the node parameter.
    $this->clickLink('Edit');
    $this->assertSession()->titleEquals('Edit parameter | Drupal');
    $edit = [
      'type' => 'entity:node',
    ];
    $this->submitForm($edit, 'Update parameter');
    $this->assertSession()->pageTextContains('The node parameter has been updated.');

    // Skip the variant General configuration step.
    $this->submitForm([], 'Next');

    // Add the Node block to the top region.
    $this->submitForm([], 'Next');
    $this->clickLink('Add new block');
    $this->clickLink('Entity view (Content)');
    $edit = [
      'region' => 'top',
    ];
    $this->submitForm($edit, 'Add block');

    // Finish the wizard.
    $this->submitForm([], 'Finish');
    $this->assertSession()->responseContains(new FormattableMarkup('The page %label has been added.', ['%label' => 'Foo']));

    // Check that the node's title is visible at the page.
    $this->drupalGet('admin/foo/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($node->getTitle());
  }

}
