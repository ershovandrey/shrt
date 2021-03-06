<?php

namespace Drupal\shorty\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the shorty entity edit forms.
 */
class ShortyEditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New shorty %label has been created.', $message_arguments));
      $this->logger('shorty')->notice('Created new shorty %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The shorty %label has been updated.', $message_arguments));
      $this->logger('shorty')->notice('Updated new shorty %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.shorty.canonical', ['shorty' => $entity->id()]);
  }

}
