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
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  // phpcs:ignore -- Do not disable strict config schema checking in tests.
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
    $this->assertSession()->fieldExists('server_url');
    $this->assertSession()->fieldValueEquals(
      'server_url',
      $config->get('server_url')
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
      $config->get('method_of_auth') ?? '-1'
    );

    if ($config->get("method_of_auth") === "digest") {
      $this->assertSession()->fieldExists('admin_username');
      $this->assertSession()->fieldValueEquals(
        'admin_username',
        $config->get('admin_password')
      );

      $this->assertSession()->fieldExists('admin_password');
      $this->assertSession()->fieldValueEquals(
        'admin_password',
        $config->get('admin_password')
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

    $this->assertSession()->fieldExists('advancedqueue_id');
    $this->assertSession()->fieldValueEquals(
      'advancedqueue_id',
      $config->get('advancedqueue_id') ?? 'default'
    );

    $this->assertSession()->fieldExists('number-of-retries');
    $this->assertSession()->fieldValueEquals(
      'number-of-retries',
      $config->get('aqj_max_retries') ?? 5
    );

    $this->assertSession()->fieldExists('retries-delay');
    $this->assertSession()->fieldValueEquals(
      'retries-delay',
      $config->get('aqj_retry_delay') ?? 100
    );
  }

}
