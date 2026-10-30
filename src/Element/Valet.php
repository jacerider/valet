<?php

namespace Drupal\valet\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for Valet.
 *
 * @RenderElement("valet")
 */
class Valet extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = [
      '#theme' => 'valet',
      '#attached' => [
        'library' => [
          'valet/valet',
        ],
      ],
      '#pre_render' => [
        [$class, 'preRenderValet'],
      ],
      // Metadata for the valet wrapping element.
      '#attributes' => [
        'id' => 'valet',
        'class' => ['valet'],
        'role' => 'group',
        'aria-label' => $this->t('Valet quick navigation'),
      ],
    ];
    return $info;
  }

  /**
   * Prepares a #type 'valet' render element for input.html.twig.
   *
   * The element is render cached per set of permissions, so it carries
   * nothing about the user: script adds the user's id and permissions hash
   * from drupalSettings to the cache id itself, which keeps one user's cached
   * results from being served to another in the same browser.
   */
  public static function preRenderValet($element) {
    $element['#attributes']['data-cache-id'] = (string) \Drupal::state()->get('valet.cache_id');
    // Icons arrive rendered by neo_icon; an inline SVG one needs only the
    // module's sizing rules, which no library may have put on this page.
    if (\Drupal::moduleHandler()->moduleExists('neo_icon')) {
      $element['#attached']['library'][] = 'neo_icon/icon-inline';
    }
    return $element;
  }

}
