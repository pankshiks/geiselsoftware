<?php

namespace Drupal\Tests\ludwig\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Checks if admin functionality works correctly.
 *
 * @group ludwig
 */
class AdminFunctionalityTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'ludwig'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->container->get('router.builder')->rebuild();

    // Log in an admin user.
    $account = $this->drupalCreateUser([
      'access site reports',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Make sure the main packages page loads correctly.
   */
  public function testLudwigAdmin() {
    // Load the main packages page.
    $this->drupalGet('admin/reports/packages');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains('Packages');
    $session->pageTextContains('PACKAGE');
    $session->pageTextContains('NAMESPACE');
    $session->pageTextContains('PATHS');
    $session->pageTextContains('RESOURCE');
    $session->pageTextContains('VERSION');
    $session->pageTextContains('REQUIRED BY');
    $session->pageTextContains('STATUS');

    // Load the 'skip download' packages page tab.
    $this->drupalGet('admin/reports/packages_skip');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains('Packages (skip download)');
    $session->pageTextContains('PACKAGE');
    $session->pageTextContains('NAMESPACE');
    $session->pageTextContains('PATHS');
    $session->pageTextContains('RESOURCE');
    $session->pageTextContains('VERSION');
    $session->pageTextContains('REQUIRED BY');
    $session->pageTextContains('STATUS');
  }

}
