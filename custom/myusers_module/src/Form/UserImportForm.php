<?php

namespace Drupal\myusers_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;


/**
 *  User Impor Form.
 */
class UserImportForm extends FormBase {

  /**
   * @var Connection $connection
   */
  protected $connection;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A connection instance.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(){
    return 'myusers_module_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(){
    return [
      'myusers_module.import_users_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attributes'] = ['enctype' => 'multipart/form-data'];

    $form['upload_file'] = [
      '#type' => 'managed_file',
      '#name' => 'upload_file',
      '#title' => t('File'),
      '#size' => 20,
      '#description' => t('CSV format only'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv']
      ],
      '#upload_location' => 'public://csv_upload/',
    ];
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    
    return $form;
  }

  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state){
    if ($form_state->getValue('upload_file') == NULL) {
      $form_state->setErrorByName('upload_file', $this->t('Put file.'));
    }
  }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state){
    // Load file entity.
    $file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->load($form_state->getValue('upload_file')[0]);
    $csvFile = fopen($file->getFileUri(), "r");
    $csvRows = $operations =[];
    // Group csv info.
    while(!feof($csvFile)) {
      $data = fgetcsv($csvFile);

      if ($data[0] != 'name') {
        $csvRows[] = $data[0];
      }
    }
    // Group rows each 25.
    $csvRows = array_chunk($csvRows, 25);
    foreach ($csvRows as $rowGroup) {
      $operations[] = [
        '\Drupal\myusers_module\ImportCSVFileService::insertGroupRows',
        [$rowGroup]
      ];
    }
    // Set batch process.
    if (!empty($operations)) {
      batch_set(array(
        'title' => $this->t('Processing File'),
        'operations' => $operations,
        'finished' => '\Drupal\myusers_module\ImportCSVFileService::insertFinishedCallback',
        'init_message' => $this->t('Starting.'),
        'error_message' => $this->t('Error execution.'),
      ));
    }else{
      drupal_set_message($this->t("Error."));
    }
  }
}
