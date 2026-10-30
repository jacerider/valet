<?php

declare(strict_types=1);

namespace Drupal\Tests\valet\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * The Valet element carries nothing about the user who first rendered it.
 *
 * The element is render cached per set of permissions, so whatever it says is
 * served to every user sharing them. It used to put the user's id in the
 * cache id browsers keep results under, so a second user was handed the first
 * user's id, and with it the first user's kept results in a shared browser.
 * Script now adds the user's id and permissions hash from drupalSettings.
 */
#[Group('valet')]
final class ValetCacheIdTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'valet'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['system']);
    $this->container->get('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
    // The anonymous user takes uid 0, so the users below get ids of their own.
    $this->createUser();
  }

  /**
   * Two users with the same permissions get a cache id naming neither.
   */
  public function testCacheIdIsTheSameForEveryUser(): void {
    $role = $this->createRole(['access valet']);
    $first = $this->createUser([], NULL, FALSE, ['roles' => [$role]]);
    $second = $this->createUser([], NULL, FALSE, ['roles' => [$role]]);
    // Creating the role moved the cache id; pin it for the assertion.
    $this->container->get('state')->set('valet.cache_id', 'abc');

    $this->setCurrentUser($first);
    $this->assertSame('abc', $this->renderCacheId());
    // Rendered from the render cache the first user filled.
    $this->setCurrentUser($second);
    $this->assertSame('abc', $this->renderCacheId());
  }

  /**
   * Flushing Valet's caches moves the cache id on.
   */
  public function testCacheFlushMovesTheCacheId(): void {
    $this->setCurrentUser($this->createUser(['access valet']));
    $state = $this->container->get('state');
    $state->set('valet.cache_id', 'abc');
    // The id moves once per request; creating the user's role already did.
    drupal_static_reset('valet_reset_cache_id');

    valet_cache_flush();

    $this->assertNotSame('abc', $state->get('valet.cache_id'));
    $this->assertSame($state->get('valet.cache_id'), $this->renderCacheId());
  }

  /**
   * Renders the element Valet puts on a page, through the render cache.
   *
   * @return string
   *   The element's data-cache-id.
   */
  private function renderCacheId(): string {
    $page_bottom = [];
    valet_page_bottom($page_bottom);
    $this->assertArrayHasKey('valet', $page_bottom);
    $html = (string) $this->container->get('renderer')->renderInIsolation($page_bottom);
    $this->assertSame(1, preg_match('/data-cache-id="([^"]*)"/', $html, $matches));
    return $matches[1];
  }

}
