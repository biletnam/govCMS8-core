<?php

/**
 * @file
 * govCMS Custom Installer Form.
 */

namespace Drupal\govcms\Installer\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class govCMSInstallerForm
 *
 * @package Drupal\govcms\Installer\Form
 */
class govCMSInstallerForm extends FormBase {
  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * govCMSInstallerForm constructor.
   *
   * @param ModuleInstallerInterface $module_installer
   */
  public function __construct(ModuleInstallerInterface $module_installer) {
    $this->moduleInstaller = $module_installer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_installer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "govcms_installer_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = t('Optional modules');
    $install_state = $form_state->getBuildInfo()['args'][0];

    // If we have any configurable_dependencies in the profile then show them
    // to the user so they can be selected.
    if (!empty($install_state['profile_info']['dependencies_optional'])) {
      $form['modules_optional'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
      foreach ($install_state['profile_info']['dependencies_optional'] as $module_name) {
        $module_path = 'profiles/contrib/govcms/modules/custom/govcms_optional_modules/' . $module_name . '/' . $module_name . '.info.yml';
        if ($module_info_file = file_get_contents($module_path)) {
          $module_info = Yaml::parse($module_info_file);
          $form['modules_optional'][$module_name] = [
            '#title' => $module_info['name'],
            '#description' => !empty($module_info['description']) ? $module_info['description'] : '',
            '#type' => 'checkbox',
            '#default_value' => !empty($module_info['enabled']),
          ];
        }
        continue;
      }
    }
    else {
      $form['#suffix'] = $this->t('There are no available modules at this time.');
    }

    $form['warning'] = [
      '#markup' => "<p><strong>Warning:</strong> Don't install the optional modules if you're upgrading from Drupal 7 - you need to start from a blank site.</p>",
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#weight' => 99,
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $modules = array_filter($form_state->getValue('modules_optional'), function ($enabled) {
      return (bool) $enabled;
    });
    // Install optional modules.
    if (!empty($modules) && is_array($modules)) {
      $this->moduleInstaller->install(array_keys($modules));
    }
  }

}
