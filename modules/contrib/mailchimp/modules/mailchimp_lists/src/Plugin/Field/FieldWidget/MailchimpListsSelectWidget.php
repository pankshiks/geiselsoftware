<?php

namespace Drupal\mailchimp_lists\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mailchimp_lists\Plugin\Field\FieldType\MailchimpListsSubscription;

/**
 * Plugin implementation of the 'mailchimp_lists_select' widget.
 *
 * @FieldWidget (
 *   id = "mailchimp_lists_select",
 *   label = @Translation("Subscription form"),
 *   field_types = {
 *     "mailchimp_lists_subscription"
 *   },
 *   settings = {
 *     "placeholder" = "Select a Mailchimp List."
 *   }
 * )
 */
class MailchimpListsSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /* @var $instance \Drupal\mailchimp_lists\Plugin\Field\FieldType\MailchimpListsSubscription */
    $instance = $items[0];

    $email = $this->getEmail($instance);
    $mailchimp_list_id = $this->fieldDefinition->getSetting('mc_list_id');
    $at_least_one_interest_group = !empty(mailchimp_get_list($mailchimp_list_id)->intgroups);
    $hide_subscribe_checkbox = $this->fieldDefinition->getSetting('hide_subscribe_checkbox');

    $element += [
      '#title' => Html::escape($element['#title']),
      '#type' => 'fieldset',
    ];

    $element = $this->setupSubscribeCheckbox($element, $form_state, $instance, $email, $hide_subscribe_checkbox, $at_least_one_interest_group, $mailchimp_list_id);
    $element = $this->setupInterestGroups($element, $form_state, $instance, $email, $hide_subscribe_checkbox, $at_least_one_interest_group, $mailchimp_list_id);
    $element = $this->setupUnsubscribeCheckbox($element, $form_state, $instance, $email, $hide_subscribe_checkbox, $at_least_one_interest_group, $mailchimp_list_id);
    $element = $this->setupSubscriptionPendingMessage($element, $instance, $email);

    // Make a distinction between whether the field is edited by the system or
    // the user. This is important to prevent unwanted subscription overwrites.
    $build_info = $form_state->getBuildInfo();
    if ($build_info['callback_object'] instanceof EntityFormInterface &&  $build_info['callback_object']->getOperation() == 'edit') {

      // The field is set from an edited via the UI.
      $element['allow_unsubscribe'] = [
        '#type' => 'value',
        '#value' => TRUE,
      ];
    }
    else {
      // The field is NOT set from an edit.
      $element['allow_unsubscribe'] = [
        '#type' => 'value',
        '#value' => FALSE,
      ];
    }

    return ['value' => $element];
  }

  /**
   * @param $instance
   * @return bool|null|string
   */
  protected function getEmail($instance) {
    $email = NULL;
    if (!empty($instance->getEntity())) {
      $email = mailchimp_lists_load_email($instance, $instance->getEntity(), FALSE);
    }
    return $email;
  }

  /**
   * @param array $element
   * @param FormStateInterface $form_state
   * @param $instance MailchimpListsSubscription
   * @param $email
   * @param $hide_subscribe_checkbox
   * @param $at_least_one_interest_group
   * @param $mailchimp_list_id
   *
   * @return array
   */
  protected function setupSubscribeCheckbox(array $element, FormStateInterface $form_state, MailchimpListsSubscription $instance, $email, $hide_subscribe_checkbox, $at_least_one_interest_group, $mailchimp_list_id) {
    $memberStatus = $this->GetMemberStatus($instance, $email);
    if ($memberStatus == 'pending') {
      // If member status is pending then don't show the subscribe
      // checkbox. The user has already attempted to subscribe and
      // must check their email to complete the subscription process.
      // That means they are neither fully subscribed nor unsubscribed.
      return $element;
    }

    $subscribe_default = $this->getSubscribeDefault($instance, $email);
    $subscribe_checkbox_label = $this->fieldDefinition->getSetting('subscribe_checkbox_label');
    $element['subscribe'] = [
      '#title' => $subscribe_checkbox_label ?: $this->t('Subscribe'),
      '#type' => 'checkbox',
      '#default_value' => ($subscribe_default) ? TRUE : $this->fieldDefinition->isRequired(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#disabled' => $this->fieldDefinition->isRequired(),
    ];
    $showSubscribeCheckbox = $this->subscribeCheckboxShown($form_state, $hide_subscribe_checkbox, $at_least_one_interest_group, $email, $mailchimp_list_id);
    if ($showSubscribeCheckbox) {
      $element['subscribe']['#access'] = TRUE;
    } else {
      $element['subscribe']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * @param array $element
   * @param FormStateInterface $form_state
   * @param $instance
   * @param $email
   * @param $hide_subscribe_checkbox
   * @param $at_least_one_interest_group
   * @param $mailchimp_list_id
   *
   * @return array
   */
  protected function setupInterestGroups(array $element, FormStateInterface $form_state, $instance, $email, $hide_subscribe_checkbox, $at_least_one_interest_group, $mailchimp_list_id) {
    $interest_groups_label = $instance->getFieldDefinition()->getSetting('interest_groups_label');
    $instance_name = $instance->getFieldDefinition()->getName();
    $instance_list_id = $instance->getFieldDefinition()->getSetting('mc_list_id');
    $mc_instance_list = mailchimp_get_list($instance_list_id);

    // TRUE if interest groups are enabled for this list.
    $show_interest_groups = $this->fieldDefinition->getSetting('show_interest_groups');
    // TRUE if widget is being used to set default values via admin form.
    $is_default_value_widget = $this->isDefaultValueWidget($form_state);
    // TRUE if interest groups are enabled but hidden from the user.
    $interest_groups_hidden = $this->fieldDefinition->getSetting('interest_groups_hidden');

    $interest_group_element_type = 'fieldset';
    if (!$is_default_value_widget && $show_interest_groups && $hide_subscribe_checkbox && $at_least_one_interest_group && !$this->memberIsUnsubscribed($mailchimp_list_id, $email)) {
      $interest_group_element_type = 'container';
    }

    if ($show_interest_groups || $is_default_value_widget) {
      if ($interest_groups_hidden && !$is_default_value_widget) {
        $element['interest_groups'] = [];
      }
      else {
        $element['interest_groups'] = [
          '#type' => $interest_group_element_type,
          '#title' => Html::escape($interest_groups_label),
          '#weight' => 100,
        ];
        $element['interest_groups']['#states'] = [
          'invisible' => [
            ':input[name="' . $instance_name . '[0][value][subscribe]"]' => ['checked' => FALSE],
          ],
        ];
      }

      if ($is_default_value_widget) {
        $element['interest_groups']['#states']['invisible'] = [
          ':input[name="settings[show_interest_groups]"]' => ['checked' => FALSE],
        ];
      }

      $groups_default = $this->getInterestGroupsDefaults($instance);

      if (!empty($mc_instance_list->intgroups)) {
        $mode = $is_default_value_widget ? 'admin' : ($interest_groups_hidden ? 'hidden' : 'default');
        $element['interest_groups'] += mailchimp_interest_groups_form_elements($mc_instance_list, $groups_default, $email, $mode);
      }
    }

    return $element;
  }

  /**
   * @param array $element
   * @param FormStateInterface $form_state
   * @param $instance
   * @param $email
   * @param $hide_subscribe_checkbox
   * @param $mailchimp_list_id
   *
   * @return array
   */
  protected function setupUnsubscribeCheckbox(array $element, $form_state, $instance, $email, $hide_subscribe_checkbox, $at_least_one_interest_group, $mailchimp_list_id) {

    if ($this->subscribeCheckboxShown($form_state, $hide_subscribe_checkbox, $at_least_one_interest_group, $email, $mailchimp_list_id)) {
      // When the subscribe checkbox is shown, we don't need to show
      // the unsubscribe checkbox (unchecked subscribe means the
      // same thing as unsubscribed).
      return $element;
    }

    $memberStatus = $this->GetMemberStatus($instance, $email);
    if ($memberStatus == 'subscribed') {
      $element['unsubscribe'] = [
        '#title' => t("Unsubscribe"),
        '#type' => 'checkbox',
        '#weight' => 101,
        '#default_value' => FALSE,
      ];
    }

    return $element;
  }

  /**
   * @param array $element
   * @param $instance
   * @param $email
   *
   * @return array
   */
  protected function setupSubscriptionPendingMessage(array $element, $instance, $email) {
    $memberStatus = $this->GetMemberStatus($instance, $email);
    if ($memberStatus == 'pending') {
      $element['pending'] = [
        '#type' => 'markup',
        '#markup' => t("<b>Subscription is pending. Confirm by visiting your email.</b>"),
        '#weight' => 101,
      ];
    }
    return $element;
  }

  /**
   * @param $instance
   * @param $email
   * @return bool
   */
  protected function getSubscribeDefault($instance, $email) {
    $subscribe_default = $instance->getSubscribe();
    if (!empty($instance->getEntity()) && $email) {
      $instance_list_id = $instance->getFieldDefinition()->getSetting('mc_list_id');
      $subscribe_default = mailchimp_is_subscribed($instance_list_id, $email);
    }
    return $subscribe_default;
  }

  /**
   * @param $instance
   * @return array
   */
  protected function getInterestGroupsDefaults($instance) {
    $groups_default = $instance->getInterestGroups();

    if ($groups_default == NULL) {
      $groups_default = [];
    }
    return $groups_default;
  }

  /**
   * @param $instance
   * @param $email
   *
   * @return mixed
   */
  protected function GetMemberStatus($instance, $email) {
    $memberStatus = NULL;
    if (!empty($instance->getEntity()) && $email) {
      $instance_list_id = $instance->getFieldDefinition()->getSetting('mc_list_id');
      $memberinfo = mailchimp_get_memberinfo($instance_list_id, $email, TRUE);
      if (isset($memberinfo->status)) {
        $memberStatus = $memberinfo->status;
      }
    }
    return $memberStatus;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $hide_subscribe_checkbox
   * @param $at_least_one_interest_group
   * @param $email
   * @param $mailchimp_list_id
   *
   * @return bool
   */
  protected function subscribeCheckboxShown(FormStateInterface $form_state, $hide_subscribe_checkbox, $at_least_one_interest_group, $email, $mailchimp_list_id): bool {

    // TRUE if interest groups are enabled for this list.
    $show_interest_groups = $this->fieldDefinition->getSetting('show_interest_groups');
    // TRUE if widget is being used to set default values via admin form.
    $is_default_value_widget = $this->isDefaultValueWidget($form_state);

    // Hide the Subscribe checkbox if:
    // - The form is not being used to configure default values.
    // - The field is configured to show interest groups.
    // - The field is configured to hide the Subscribe checkbox.
    // - The list has at least one interest group.
    // This allows users to skip the redundant step of checking the Subscribe
    // checkbox when also checking interest group checkboxes.
    $showSubscribeCheckbox = TRUE;
    if (!$is_default_value_widget && $show_interest_groups && $hide_subscribe_checkbox && $at_least_one_interest_group && $this->memberIsUnsubscribed($mailchimp_list_id, $email)) {
      $showSubscribeCheckbox = FALSE;
    }
    return $showSubscribeCheckbox;
  }

  /**
   * @param $mailchimp_list_id
   * @param $email
   *
   * @return bool
   */
  protected function memberIsUnsubscribed($mailchimp_list_id, $email): bool {
    $member_info = mailchimp_get_memberinfo($mailchimp_list_id, $email);
    return (!isset($member_info->status) || $member_info->status !== "unsubscribed");
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $delta => $value) {
      $new_values[$delta] = $value['value'];
      $new_values[$delta]['subscribe'] = $this->getSubscribeFromInterests($new_values[$delta]);
    }

    return $new_values;
  }

  /**
   * Sets the subscribe value based on field settings and interest groups.
   *
   * @param $choices
   *   The value of the field.
   *
   * @return bool
   *   TRUE if there are interests chosen on a hidden subscribe list checkbox.
   */
  public function getSubscribeFromInterests($choices) {
    $subscribe_from_interest_groups = $choices['subscribe'];
    $field_settings = $this->getFieldSettings();

    // Automatically subscribe if the field is configured to hide the
    // subscribe checkbox and at least one interest group checkbox is checked.
    if ($field_settings['show_interest_groups'] && $field_settings['hide_subscribe_checkbox']) {
      if (!empty($choices['interest_groups'])) {
        $subscribe_from_interest_groups = FALSE;
        foreach ($choices['interest_groups'] as $group_id => $interests) {
          foreach ($interests as $interest_id => $value) {
            if (!empty($value)) {
              $subscribe_from_interest_groups = TRUE;
              break;
            }
          }
        }
      }
    }

    return $subscribe_from_interest_groups;
  }
}
