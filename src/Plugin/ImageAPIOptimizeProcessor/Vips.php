<?php

namespace Drupal\imageapi_optimize_vips\Plugin\ImageAPIOptimizeProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\imageapi_optimize_binaries\ImageAPIOptimizeProcessorBinaryBase;

/**
 * Uses the Vips binary to optimize images.
 *
 * @ImageAPIOptimizeProcessor(
 *   id = "vips",
 *   label = @Translation("Vips"),
 *   description = @Translation("Uses the Vips binary to optimize images.")
 * )
 */
class Vips extends ImageAPIOptimizeProcessorBinaryBase {

  /**
   * {@inheritdoc}
   */
  protected function executableName() {
    return 'vips';
  }

  public function applyToImage($imageUri) {
    if ($cmd = $this->getFullPathToBinary()) {
      $dst = $this->sanitizeFilename($imageUri);

      $options = array();

      if ($this->configuration['colorspace']) {
        $options[] = 'colourspace';
      }

      $options[] = $dst;

      // use accept header quality?
      $GLOBALS["request"]->headers->get('accept');

      $webp = true;
      if ($this->configuration['webp_enable']) {
        if (is_numeric($this->configuration['webp_quality'])) {
          $webpOptions = $options;
          $webpOptions[] = $dst . '.webp[Q=' . $this->configuration['webp_quality'] .',strip]';
          if ($this->configuration['colorspace']) {
            $webpOptions[] = $this->configuration['colorspace'];
          }
        }
        $webp = $this->execShellCommand($cmd, $webpOptions, []);
      }

      $avif = true;
      if ($this->configuration['avif_enable']) {
        if (is_numeric($this->configuration['avif_quality'])) {
          $avifOptions = $options;
          $avifOptions[] = $dst . '.avif[Q=' . $this->configuration['avif_quality'] .',strip]';
          if ($this->configuration['colorspace']) {
            $avifOptions[] = $this->configuration['colorspace'];
          }
        }
        $avif = $this->execShellCommand($cmd, $avifOptions, []);
      }

      $options[] = $dst . '[Q=' . $this->configuration['quality'] .',strip]';

      if ($this->configuration['colorspace']) {
        $options[] = $this->configuration['colorspace'];
      }

      $orginal = $this->execShellCommand($cmd, $options, []);

      return $webp && $avif && $orginal;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'quality' => 80,
      'colorspace' => 'srgb',
      'webp_enable' => TRUE,
      'webp_quality' => 60,
      'avif_enable' => TRUE,
      'avif_quality' => 40,
    ];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form = parent::buildConfigurationForm($form, $formState);

    $form['quality'] = array(
      '#title' => $this->t('Quality'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#description' => $this->t('Optionally enter a JPEG quality setting to use, 0 - 100. WARNING: LOSSY'),
      '#default_value' => $this->configuration['quality'],
    );

    $form['colorspace'] = [
      '#type' => 'select',
      '#title' => $this->t('Convert colorspace'),
      '#default_value' => $this->configuration['colorspace'],
      '#options' => [
        'rgb' => $this->t('RGB'),
        'srgb' => $this->t('sRGB'),
        'grey16' => $this->t('Gray'),
      ],
      '#empty_value' => 0,
      '#empty_option' => $this->t('- Original -'),
      '#description' => $this->t("Converts processed images to the specified colorspace. The color profile option overrides this setting."),
    ];

    $form['webp'] = [
      '#type' => 'details',
      '#title' => $this->t('WebP'),
    ];

    $form['webp']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('enable'),
      '#default_value' => $this->configuration['webp_enable'],
    ];

    $form['webp']['quality'] = array(
      '#title' => $this->t('Quality'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#description' => $this->t('Optionally enter a WebP quality setting to use, 0 - 100. WARNING: LOSSY'),
      '#default_value' => $this->configuration['webp_quality'],
    );

    $form['avif'] = [
      '#type' => 'details',
      '#title' => $this->t('Avif'),
    ];

    $form['avif']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('enable'),
      '#default_value' => $this->configuration['avif_enable'],
    ];

    $form['avif']['quality'] = array(
      '#title' => $this->t('Quality'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#description' => $this->t('Optionally enter a Avif quality setting to use, 0 - 100. WARNING: LOSSY'),
      '#default_value' => $this->configuration['avif_quality'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::submitConfigurationForm($form, $formState);

    // @todo make form config.
    $this->configuration['quality'] = $formState->getValue('quality');
    $this->configuration['colorspace'] = $formState->getValue('colorspace');
    $this->configuration['webp_enable'] = $formState->getValue('webp')['enable'];
    $this->configuration['webp_quality'] = $formState->getValue('webp')['quality'];
    $this->configuration['avif_enable'] = $formState->getValue('avif')['enable'];
    $this->configuration['avif_quality'] = $formState->getValue('avif')['quality'];
  }
}
