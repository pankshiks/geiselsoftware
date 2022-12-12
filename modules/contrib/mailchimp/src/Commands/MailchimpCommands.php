<?php

namespace Drupal\mailchimp\Commands;

use Drush\Commands\DrushCommands;

/**
 * Class MailchimpCommands provides Drush commands.
 *
 * @package Drupal\mailchimp\Commands
 */
class MailchimpCommands extends DrushCommands {

  /**
   * This command will trigger Mailchimp Cron jobs.
   *
   * @param int $temp_batchsize
   *   Optional max size of batch. If ommitted, will use configuration.
   *
   * @command mailchimp:cron
   * @aliases mcc mailchimp-cron
   *
   * @usage drush mailchimp:cron
   *  Trigger mailchimp cron jobs with configured batch size
   *
   * @usage drush mailchimp:cron 50
   *  Trigger up to 50 mailchimp cron jobs
   */
  public function cron(int $temp_batchsize): string {
    $result = mailchimp_cron($temp_batchsize);
    return ('Mailchimp cron jobs triggered: ' . $result);
  }

}
