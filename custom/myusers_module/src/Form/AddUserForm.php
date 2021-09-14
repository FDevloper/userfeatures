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
 *  Add Users Form.
 */
class AddUserForm extends FormBase {

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
    return 'myusers_module_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(){
    return [
      'myusers_module.add_users_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['info'] = [
      '#type'   => 'markup',
      '#prefix' => '<div id="info">',
      '#suffix' => '</div>',
    ];

    $form['user_form'] = [
      '#type' => 'container',
      '#prefix' => '<div id="form_wrapper">',
      '#suffix' => '</div>',
    ];

    $form['user_form']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['user_form']['actions'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::addCustomUser',
      ],
    ];
    $form['#theme'] = 'myusers_module_add_users_form';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  function addCustomUser(array $form, FormStateInterface &$form_state) {
    $response = new AjaxResponse();
    // Check form errors.
    if ($form_state->getErrors()) {
      // $content = 'The form contains the next errors: ';
      // foreach ($form_state->getErrors() as $name => $message) {
      //   \Drupal::logger('some_channel_name')->warning('<pre><code>' . print_r($message, TRUE) . '</code></pre>');
      // }
      // $title = 'Warning';
      // $response = new AjaxResponse();
      // $response->addCommand(
      //   new OpenModalDialogCommand($this->t($title), $this->t($content),
      //   array('width' => '700')
      // ));
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10
      ];
      $response->addCommand(new HtmlCommand('#form_wrapper', $form));
    }
    else {
      // Add user.
      $values = $form_state->getValues();
      $result = $this->connection->insert('custom_users')
        ->fields([
          'name' => $values['name'],
        ])
        ->execute();
      $content = "The user {$values['name']} was registered successfully ".
      $content .= "with id {$result}. ";
      $title = 'Success.';
      unset($form['status_messages']);
      $response = new AjaxResponse();
      $response->addCommand(
        new OpenModalDialogCommand($this->t($title), $this->t($content), 
        array('width'=>'700')
      ));
    }
    return $response;
  }

  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state){
    $values = $form_state->getValues();
    // Check input.
    if (empty($values['name'])) {
      $form_state->setErrorByName('name', $this->t("Username cannot be empty."));
    }
    // Check string length.
    if (strlen($values['name']) < 5) {
      $form_state->setErrorByName('name', $this->t("Username must contain at least five characters."));
    }
    // Check illegal characters.
    if (!ctype_alpha($values['name'])) {
      $form_state->setErrorByName('name', $this->t("Username contains illegal characters."));
    }
    // Check user name.
    $query = $this->connection->select('custom_users', 'users');
    $query->fields('users', ['name']);
    $query->condition('users.name', $values['name']);
    $query->range(0, 1);

    if (!empty($query->execute()->fetchField())) {
      $form_state->setErrorByName('name', $this->t("The user '{$values['name']}' is already registered."));
    }
  }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state){
  }
}
