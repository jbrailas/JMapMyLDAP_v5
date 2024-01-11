<?php
namespace PPCR\Plugin\Console\Ldap_Cron\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Factory;
use PPCR\Plugin\Console\Ldap_Cron\CliCommand\RunLdap_CronCommand;

class Ldap_CronConsolePlugin extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            \Joomla\Application\ApplicationEvents::BEFORE_EXECUTE => 'registerCommands',
        ];
    }

    public function registerCommands(): void
    {
        $app = Factory::getApplication();
        $app->addCommand(new RunLdap_CronCommand());
    }
}












