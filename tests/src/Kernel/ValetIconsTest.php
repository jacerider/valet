<?php

declare(strict_types=1);

namespace Drupal\Tests\valet\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\neo_icon\IconElement;
use Drupal\neo_icon\IconRepository;
use Drupal\Tests\neo_icon\Kernel\ClassicPackageTrait;
use Drupal\valet\Controller\ValetController;
use PHPUnit\Framework\Attributes\Group;

/**
 * Valet's results carry their icons rendered, once per icon.
 *
 * Script that draws an icon from its selector shows nothing once the icon's
 * library renders inline SVG, so the endpoint renders each icon the results
 * use through neo_icon and ships the markup beside them.
 *
 * Needs neo_icon, whose test package this borrows.
 */
#[Group('valet')]
final class ValetIconsTest extends KernelTestBase {

  use ClassicPackageTrait;

  /**
   * {@inheritdoc}
   *
   * The set neo_icon's own kernel tests enable, plus Valet and its test
   * resource.
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'path_alias',
    'linkit',
    'neo_build',
    'neo_color',
    'neo',
    'neo_config_file',
    'neo_favicon',
    'neo_tooltip',
    'neo_settings',
    'neo_modal',
    'neo_icon',
    'valet',
    'valet_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    $this->container->get('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
    $this->config('valet.admin')->set('plugins', [
      'valet_test' => ['status' => TRUE, 'settings' => []],
    ])->save();
  }

  /**
   * An inline SVG library's icons arrive as SVG, once each, by selector.
   */
  public function testSvgIconsAreShippedOncePerIcon(): void {
    $this->importClassicLibrary('test', ['render' => 'svg']);
    $data = $this->fetch();

    // Items keep the selector, which is the key into the map.
    $this->assertSame(['icon-test-chevron', 'icon-test-chevron', 'icon-test-grid', '', 'icon-test-duo'], array_column($data['items'], 'icon'));
    // One entry per icon, the alter hook's selector-only item included.
    $this->assertEqualsCanonicalizing(['icon-test-chevron', 'icon-test-grid', 'icon-test-duo'], array_keys($data['icons']));
    $this->assertSame('<i class="neo-icon neo-icon-svg icon-test-chevron" aria-hidden="true"><svg viewBox="0 0 640 1024" width="0.625em" height="1em" fill="currentColor"><path d="M0 0h640v1024h-640z"/></svg></i>', $data['icons']['icon-test-chevron']);
    foreach ($data['icons'] as $markup) {
      $this->assertStringContainsString('<svg', $markup);
    }
  }

  /**
   * A font library's icons arrive as the font glyphs they always were.
   */
  public function testFontIconsAreShippedAsFontGlyphs(): void {
    $this->importClassicLibrary('test');
    $data = $this->fetch();

    $this->assertSame('<i class="neo-icon neo-icon-font icon-test-chevron" aria-hidden="true"></i>', $data['icons']['icon-test-chevron']);
    foreach ($data['icons'] as $markup) {
      $this->assertStringNotContainsString('<svg', $markup);
    }
  }

  /**
   * Saving a library rebuilds the cached results and moves the cache id.
   *
   * The server cache is invalidated by the library's tag, which the rendered
   * icons carry; browsers keep the results under the page's cache id, which
   * the save moves on so they fetch them again.
   */
  public function testSavingTheLibraryInvalidatesTheResults(): void {
    $library = $this->importClassicLibrary('test');
    $this->assertStringNotContainsString('<svg', $this->fetch()['icons']['icon-test-chevron']);

    $cid = 'valet:' . $this->container->get('csrf_token')->get('/api/valet');
    $cached = $this->container->get('cache.default')->get($cid);
    $this->assertNotFalse($cached);
    $this->assertContains('config:neo_icon.neo_icon_library.test', $cached->tags);

    $state = $this->container->get('state');
    $state->set('valet.cache_id', 'before');
    // The id moves once per request; installing the modules already did.
    drupal_static_reset('valet_reset_cache_id');
    $library->setRender('svg')->save();

    $this->assertFalse($this->container->get('cache.default')->get($cid));
    $this->assertNotSame('before', $state->get('valet.cache_id'));
    $this->assertStringContainsString('<svg', $this->fetch()['icons']['icon-test-chevron']);
  }

  /**
   * The page carries the inline icons' sizing rules wherever Valet is.
   */
  public function testElementAttachesTheInlineIconRules(): void {
    $build = ['#type' => 'valet'];
    $this->container->get('renderer')->renderInIsolation($build);

    $this->assertContains('neo_icon/icon-inline', $build['#attached']['library']);
  }

  /**
   * Fetches the endpoint's data, as a request would.
   *
   * @return array
   *   The decoded response.
   */
  private function fetch(): array {
    // A request starts with no icon library loaded and no icon looked up.
    $this->container->get('entity_type.manager')->getStorage('neo_icon_library')->resetCache();
    $this->container->set('neo_icon.repository', new IconRepository(
      $this->container->get('entity_type.manager'),
      $this->container->get('plugin.manager.neo_icon'),
    ));
    (new \ReflectionProperty(IconElement::class, 'iconRepository'))->setValue(NULL, NULL);
    $response = $this->container->get('renderer')->executeInRenderContext(
      new RenderContext(),
      fn () => ValetController::create($this->container)->data(),
    );
    return json_decode((string) $response->getContent(), TRUE);
  }

}
