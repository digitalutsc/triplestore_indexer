<?php

namespace Drupal\Tests\triplestore_indexer\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group triplestore_indexer
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'comment',
    'image',
    'file',
    'text',
    'node_test',
    'menu_ui',
    'rest',
    /*'islandora_defaults',*/
    'jsonld',
    'advancedqueue',
    'triplestore_indexer',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Fixture authenticated user with no permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'Basic Page',
      ]);
    $type->save();

    // Create an article content type that we will use for testing.
    /*$type = $this
    ->container
    ->get('entity_type.manager')
    ->getStorage('node_type')
    ->create([
    'type' => 'islandora_object',
    'name' => 'Repository Item',
    ]);
    $type->save();*/

    $this->container->get('router.builder')->rebuild();

    // Create users.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      "create article content",
      "edit any article content",
      "create page content",
      "edit any page content",
      // "create islandora_object content",
      // "edit any islandora_object content",
    ]);
    $this->drupalLogin($this->adminUser);
    $this->client = \Drupal::httpClient();
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Jsonld Rest endpoint existed.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testJsonldForArticle() : void {
    global $base_url;
    // Login with with admin user.
    $this->drupalLogin($this->adminUser);

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test if Jsonld effect for all content types
    // Get the page that lets us add new content.
    $this->drupalGet('/node/add/article');
    // Use the WebAssert object to assert the HTTP status code.
    $assert->statusCodeEquals(200);

    // Create an Article node.
    $nodeArticleTitle = "Test an Article";
    $articleNode = [
      'title[0][value]' => $nodeArticleTitle,
      // 'body[0][value]' => 'Body of test Article'
    ];
    $this->submitForm($articleNode, 'op');
    $assert->statusCodeEquals(200);
    $assert->linkExists($nodeArticleTitle);
    $createdArticle = $this->drupalGetNodeByTitle($nodeArticleTitle);

    $url = $createdArticle->toUrl();
    $jsonld_string = $base_url . $url->toString() . "?_format=jsonld";
    $request = $this->client->get($jsonld_string, ['verify' => TRUE]);
    $this->assertEquals(200, $request->getStatusCode());
  }

  /**
   * Test Jsonld Rest endpoint existed.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testJsonldForPage(): void {
    global $base_url;
    // Login with a authenticated user.
    $this->drupalLogin($this->adminUser);

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test if Jsonld effect for all content types
    // Get the page that lets us add new content.
    $this->drupalGet('/node/add/page');
    // Use the WebAssert object to assert the HTTP status code.
    $assert->statusCodeEquals(200);

    $nodePageTitle = "Test a Page";
    $pageNode = [
      'title[0][value]' => $nodePageTitle,
      // 'body[0][value]' => 'Body of test Basic Page'
    ];

    $this->submitForm($pageNode, 'op');
    $assert->statusCodeEquals(200);
    $assert->linkExists($nodePageTitle);

    $createdArticle = $this->drupalGetNodeByTitle($nodePageTitle);
    $url = $createdArticle->toUrl();
    $jsonld_string = $base_url . $url->toString() . "?_format=jsonld";
    $request = $this->client->get($jsonld_string, ['verify' => TRUE]);

    $this->assertEquals(200, $request->getStatusCode());
  }

}
