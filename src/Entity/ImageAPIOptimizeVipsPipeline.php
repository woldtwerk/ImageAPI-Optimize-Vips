<?php

namespace Drupal\imageapi_optimize_vips\Entity;

use Drupal\imageapi_optimize\Entity\ImageAPIOptimizePipeline;
use Drupal\Core\File\FileSystemInterface;

/**
 * Wrap ImageAPIOptimizePipeline to copy vips derivative to proper directory.
 *
 * This wrapper allows for .vips image derivatives to be copied
 * to the correct directory after the vips image_api handler takes place.
 *
 * Class ImageAPIOptimizeVipsPipeline
 *
 * @package Drupal\imageapi_optimize_vips\Entity
 *
 * @param \Drupal\Core\File\FileSystemInterface $filesystem
 */
class ImageAPIOptimizeVipsPipeline extends ImageAPIOptimizePipeline {

  /**
   * {@inheritdoc}
   */
  public function applyToImage($imageUri) {
    parent::applyToImage($imageUri);
    // If the source file doesn't exist, return FALSE.
    $image = \Drupal::service('image.factory')->get($imageUri);

    if (!$image->isValid()) {
      return FALSE;
    }

    if (count($this->getProcessors())) {
      foreach ($this->temporaryFiles as $tempImageUri) {
        // @todo Check if TRUE from config.
        if(TRUE) {
          $this->copyDerivative($imageUri, '.webp', $tempImageUri);
        }
        // @todo Check if TRUE from config.
        if(TRUE) {
          $this->copyDerivative($imageUri, '.avif', $tempImageUri);
        }
      }
    }
  }

  /**
   * Create webp/avif derivative.
   *
   * @param String imageuri
   * @param String type webp or avif
   * @param String temp imageuri
   */
  public function copyDerivative($imageUri, $type, $tempImageUri) {
    try {
      $tempWebpImageUri = \Drupal::service('file_system')->copy($tempImageUri . $type, $imageUri . $type, FileSystemInterface::EXISTS_RENAME);
      if ($tempWebpImageUri) {
        $this->temporaryFiles[] = $tempImageUri . $type;
      }
    } catch (\Exception $e) {
    }
  }
}

