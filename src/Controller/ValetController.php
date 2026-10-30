<?php

namespace Drupal\valet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\valet\Plugin\ValetItems;
use Drupal\valet\Plugin\ValetResourceBase;
use Drupal\valet\ValetResourceManagerInterface;

/**
 * Instance of ValetController.
 */
class ValetController extends ControllerBase {

  /**
   * The CSRF token generator to validate the form token.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Valet resource manager.
   *
   * @var \Drupal\valet\ValetResourceManagerInterface
   */
  protected $valetResourceManager;

  /**
   * The neo_icon markup map, where neo_icon is installed.
   *
   * @var \Drupal\neo_icon\IconMarkupMapInterface|null
   */
  protected $iconMarkupMap;

  /**
   * {@inheritdoc}
   */
  public function __construct(CsrfTokenGenerator $csrf_token, ModuleHandlerInterface $module_handler, ValetResourceManagerInterface $valet_resource_manager, $icon_markup_map = NULL) {
    $this->csrfToken = $csrf_token;
    $this->moduleHandler = $module_handler;
    $this->valetResourceManager = $valet_resource_manager;
    $this->iconMarkupMap = $icon_markup_map;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token'),
      $container->get('module_handler'),
      $container->get('plugin.manager.valet_resource'),
      $container->has('neo_icon.markup_map') ? $container->get('neo_icon.markup_map') : NULL
    );
  }

  /**
   * Return the data for Valet consumption.
   *
   * The response is `{items, icons}`. Each item's `icon` is a selector; where
   * neo_icon is installed, `icons` maps each selector the items use to its
   * rendered markup, once per icon. Script cannot draw an icon from its
   * selector once the icon's library renders inline SVG.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A json object.
   */
  public function data() {
    $cid = 'valet:' . $this->csrfToken->get('/api/valet');
    if ($cache = \Drupal::cache()->get($cid)) {
      return new JsonResponse($cache->data);
    }
    $items = new ValetItems();
    $cacheable_metadata = new BubbleableMetadata();
    $cacheable_metadata->addCacheTags([
      'valet',
      'valet_resources',
      'config:core.extension',
    ]);

    $icons = [];
    foreach ($this->valetResourceManager->getEnabledInstances() as $instance) {
      foreach ($instance->getResults() as $item) {
        $items->appendItem($item);
      }
      if ($instance instanceof ValetResourceBase) {
        $icons += $instance->getIcons();
      }
      $cacheable_metadata->addCacheableDependency($instance);
    }

    // Invoke alter hook.
    $this->moduleHandler->alter('valet_results', $items);
    $list = array_values($items->toArray());
    $data = [
      'items' => $list,
      // An object even when empty, which is what script reads it as.
      'icons' => (object) $this->buildIcons($list, $icons, $cacheable_metadata),
    ];

    // Cache.
    \Drupal::cache()->set($cid, $data, Cache::PERMANENT, $cacheable_metadata->getCacheTags());

    return new JsonResponse($data);
  }

  /**
   * Renders the icons the items use, keyed by selector.
   *
   * @param array[] $items
   *   The items, as arrays, after the alter hook.
   * @param \Drupal\neo_icon\IconInterface[] $icons
   *   The icons the resources matched, keyed by selector. An item an alter
   *   hook added names its icon by selector alone, and is looked up.
   * @param \Drupal\Core\Render\BubbleableMetadata $metadata
   *   Receives the icons' cache tags, so saving an icon library invalidates
   *   the cached data.
   *
   * @return string[]
   *   The rendered icons, keyed by selector; empty without neo_icon.
   */
  protected function buildIcons(array $items, array $icons, BubbleableMetadata $metadata): array {
    if (!$this->iconMarkupMap) {
      return [];
    }
    $used = [];
    foreach ($items as $item) {
      $selector = $item['icon'] ?? '';
      if (is_string($selector) && $selector !== '') {
        $used[$selector] = $icons[$selector] ?? $selector;
      }
    }
    return $this->iconMarkupMap->build($used, $metadata);
  }

}
