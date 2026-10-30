<?php

namespace Drupal\valet_test\Plugin\ValetResource;

use Drupal\valet\Plugin\ValetItem;
use Drupal\valet\Plugin\ValetResourceBase;

/**
 * Results with icons, for the Valet tests.
 *
 * @ValetResource(
 *   id = "valet_test",
 *   label = @Translation("Valet test"),
 * )
 */
class IconResults extends ValetResourceBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareResults() {
    return [
      new ValetItem(['label' => 'One', 'url' => '/one', 'icon' => $this->getIcon('valet test alpha')]),
      new ValetItem(['label' => 'Two', 'url' => '/two', 'icon' => $this->getIcon('valet test alpha')]),
      new ValetItem(['label' => 'Three', 'url' => '/three', 'icon' => $this->getIcon('valet test beta')]),
      new ValetItem(['label' => 'Four', 'url' => '/four']),
    ];
  }

}
