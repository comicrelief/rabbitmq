<?php

/**
 * @file
 * Contains \Drupal\rabbitmq_example\Form\EsuForm.
 */

namespace Drupal\rabbitmq_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * ESU form.
 */
class EsuForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'esu_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your .com email address.'),
    ];
    $form['show'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Your email address is @email', ['@email' => $form_state->getValue('email')]));

    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('rabbitmq');
    $item = new \stdClass();
    $item->nid = $form_state->getValue('email');
    $queue->createItem($item);
  }

}
