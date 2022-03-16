<?php

namespace Drupal\Tests\triplestore_indexer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group triplestore_indexer
 */
class ConfigTest extends BrowserTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'jsonld',
    'advancedqueue',
    'rest',
    'restui',
    'triplestore_indexer',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test config forms.
   */
  public function testConfigForm() {
    // Login.
    $this->drupalLogin($this->user);

    // Access config page.
    $this->drupalGet('admin/config/triplestore_indexer/configuration');
    $this->assertSession()->statusCodeEquals(200);

    // Test the form elements exist and have defaults.
    $config = $this->config('triplestore_indexer.triplestoreindexerconfig');

    // Page title field has the default value.
    $this->assertSession()->fieldExists('server-url');
    $this->assertSession()->fieldValueEquals(
      'server-url',
      $config->get('server-url')
    );

    // Source text field has the default value.
    $this->assertSession()->fieldExists('namespace');
    $this->assertSession()->fieldValueEquals(
      'namespace',
      $config->get('namespace')
    );

    $this->assertSession()->fieldExists('select-auth-method');
    $this->assertSession()->fieldValueEquals(
      'select-auth-method',
      $config->get('method-of-auth')
    );

    if ($config->get("method-of-auth") === "digest") {
      $this->assertSession()->fieldExists('admin-username');
      $this->assertSession()->fieldValueEquals(
        'admin-username',
        $config->get('admin-password')
      );

      $this->assertSession()->fieldExists('admin-password');
      $this->assertSession()->fieldValueEquals(
        'admin-password',
        $config->get('admin-password')
      );
    }
    elseif ($config->get("method-of-auth") === "oauth") {
      $this->assertSession()->fieldExists('client-id');
      $this->assertSession()->fieldValueEquals(
        'client-id',
        $config->get('client-id')
      );

      $this->assertSession()->fieldExists('client-secret');
      $this->assertSession()->fieldValueEquals(
        'client-secret',
        $config->get('client-secret')
      );
    }

    $this->assertSession()->fieldExists('advancedqueue-id');
    $this->assertSession()->fieldValueEquals(
      'advancedqueue-id',
      $config->get('advancedqueue-id')
    );

    $this->assertSession()->fieldExists('number-of-retries');
    $this->assertSession()->fieldValueEquals(
      'number-of-retries',
      $config->get('aqj-max-retries')
    );

    $this->assertSession()->fieldExists('retries-delay');
    $this->assertSession()->fieldValueEquals(
      'retries-delay',
      $config->get('aqj-retry_delay')
    );
  }

}
