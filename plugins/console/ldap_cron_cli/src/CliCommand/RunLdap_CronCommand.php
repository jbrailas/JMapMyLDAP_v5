<?php
namespace PPCR\Plugin\Console\Ldap_Cron\CliCommand;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Joomla\Console\Command\AbstractCommand;

// Load Library language
$lang = Factory::getLanguage();

// Try the Shmanic LDAP file in the current language (without allowing the loading of the file in the default language)
$lang->load('files_cli_shmanic_ldap', JPATH_SITE, null, false, false)
// Fallback to the Shmanic LDAP file in the default language
|| $lang->load('files_cli_shmanic_ldap', JPATH_SITE, null, true);

// Get the Shmanic platform and Ldap libraries.
require_once JPATH_PLATFORM . DIRECTORY_SEPARATOR . 'shmanic/import.php';
//require_once JPATH_PLATFORM . DIRECTORY_SEPARATOR . 'shmanic/ldap/helper.php';
shImport('ldap');

class RunLdap_CronCommand extends AbstractCommand {
	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected static $defaultName = 'ldap:cron';

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   4.0.0
	 */
	 protected function doExecute(InputInterface $input, OutputInterface $output): int {
		$symfonyStyle = new SymfonyStyle($input, $output);

		$symfonyStyle->title('Elegxos Ldap_Cron');

		// You might want to do some stuff here in Joomla
		// Setup some stats
		$failed 	= 0;
		$success 	= 0;
		$errors		= array();
		$messages = "";
		$error_messages = "";

		// It appears we have to tell the system we are running with the site otherwise bad things happen
		Factory::getApplication('site');
		
		$messages .= Text::_('CLI_SHMANIC_LDAP_INFO_13001');
		$messages .= '\n' . date('d-m-Y H-i-s') . '\n';

		// Get all the valid configurations
		if (!$configs = \SHLdapHelper::getConfig()) {
			// Failed to find any Ldap configs
			$error_messages .=  Text::_('CLI_SHMANIC_LDAP_ERR_13003');
			$this->close(1);
		}

		// Check if only a single config was found
		if ($configs instanceof Registry) {
			/*
			 * To make things easier, we pretend we returned multiple Ldap configs
			 * by casting the single entry into an array.
			 */
			$configs = array($configs);
		}

		$count = count($configs);
		$messages .= Text::sprintf('CLI_SHMANIC_LDAP_INFO_13002', $count);

		// Loop around each LDAP configuration
		foreach ($configs as $config) {
			try {
				// Get a new Ldap object
				$ldap = new \SHLdap($config);

				// Bind with the proxy user
				if (!$ldap->authenticate(\SHLdap::AUTH_PROXY)) {
					// Something is wrong with this LDAP configuration - cannot bind to proxy user
					$errors[] = new \Exception(Text::sprintf('CLI_SHMANIC_LDAP_ERR_13011', $ldap->info), 13011);
					unset($ldap);

					continue;
				}

				// Get all the Ldap users in the directory
				if (!$result = $ldap->search(null, $ldap->allUserFilter, array('dn', $ldap->keyUid))) {
					// Failed to search for all users in the directory
					$errors[] = new \Exception(Text::sprintf('CLI_SHMANIC_LDAP_ERR_13012', $ldap->getErrorMsg()), 13012);
					unset($ldap);

					continue;
				}

				// Loop around each Ldap user
				for ($i = 0; $i < $result->countEntries(); ++$i) {
					// Get the Ldap username (case insensitive)
					if (!$username = strtolower($result->getValue($i, $ldap->keyUid, 0))) {
						continue;
					}
					echo '\nldap username is: ' . $username . '\n';

					try {
						// Check if this user is in the blacklist
						if ($blacklist = (array) json_decode(\SHFactory::getConfig()->get('user.blacklist'))) {
							if (in_array($username, $blacklist)) {
								throw new \RuntimeException(Text::_('CLI_SHMANIC_LDAP_ERR_13025'), 13025);
							}
						}

						// Create the new user adapter
						$adapter = new \SHUserAdaptersLdap(array('username' => $username), $config);

						// Get the Ldap DN
						if (!$dn = $adapter->getId(false)) {
							echo "\n !dn = adapter->getId(false).\n";
							continue;
						}

						$messages .= Text::sprintf('CLI_SHMANIC_LDAP_INFO_13020', $username);

						// Get the Ldap user attributes
						$source = $adapter->getAttributes();
						
						//print_r($source);

						//and where we use the source variable??

						// Get the core mandatory J! user fields
						$username = $adapter->getUid();
						$fullname = $adapter->getFullname();
						$email = $adapter->getEmail();

						if (empty($fullname)) {
							// Full name doesnt exist; use the username instead
							echo "\nFull name doesnt exist; use the username instead: " . $fullname . "\n";
							$fullname = $username;
						}

						if (empty($email)) {
							// Email doesnt exist; cannot proceed
							echo "\nemail doesn't exist. cannot proceed.\n";
							throw new \Exception(Text::_('CLI_SHMANIC_LDAP_ERR_13022'), 13022);
						}
						
						$enabled_disabled = $adapter->getEnabledDisabled();
						echo "\nfullname is " . $fullname . " and enabled_disabled is " . $enabled_disabled . "\n";
						
						if ($enabled_disabled == 514 || $enabled_disabled == 66050)
							//if ldap userAccountControl attribute has value 514 or 66050 then block the user!
							$block = 1;
						else
							$block = 0;

						// Create the user array to enable creating a User object
						$user = array(
							'fullname' => $fullname,
							'username' => $username,
							'password_clear' => null,
							'email' => $email,
							'block' => $block
						);

						// Create a User object from the Ldap user
						$options = array('adapter' => &$adapter);
						$instance = \SHUserHelper::getUser($user, $options);

						if ($instance === false || is_null($instance)) {
							// Failed to get the user either due to save error or autoregister
							echo "failed to get the user";
							throw new \Exception(Text::_('CLI_SHMANIC_LDAP_ERR_13024'), 13024);
						}

						// Fire the Ldap specific on Sync feature
						if (\SHLdapHelper::triggerEvent('onLdapSync', array(&$instance, $options)) !== false){
							
							// Even if the sync does not need a save, do it anyway as Cron efficiency doesnt matter too much
							\SHUserHelper::save($instance);

							// Above should throw an exception on error so therefore we can report success
							$messages .= Text::sprintf('CLI_SHMANIC_LDAP_INFO_13029', $username);
							++$success;
						}
						else {
							throw new \Exception(Text::_('CLI_SHMANIC_LDAP_ERR_13026'), 13026);
						}

						unset($adapter);
					}
					catch (\Exception $e) {
						unset($adapter);
						++$failed;
						$errors[] = new \Exception(Text::sprintf('CLI_SHMANIC_LDAP_ERR_13028', $username, $e->getMessage()), $e->getCode());
					}
				}
			}
			catch (\Exception $e) {
				$errors[] = new \Exception(Text::_('CLI_SHMANIC_LDAP_ERR_13004'), 13004);
			}
		}

		// Print out some results and stats
		$messages .= Text::_('CLI_SHMANIC_LDAP_INFO_13032');

		$messages .= Text::_('CLI_SHMANIC_LDAP_INFO_13038');

		foreach ($errors as $error) {
			if ($error instanceof \Exception) {
				$error_messages .= ' ' . $error->getCode() . ': ' . $error->getMessage();
			}
			else {
				$error_messages .= ' ' . (string) $error;
			}
		}

		$messages .= Text::sprintf('CLI_SHMANIC_LDAP_INFO_13034', $success);
		$messages .= "\n" . Text::sprintf('CLI_SHMANIC_LDAP_INFO_13036', $failed);
		
		$success_msg = date("d-m-Y h:i:sa") . ". Cron ldap_cron_cli has completed successfuly! | ";
		$success_msg .= $messages . "\n";
		$symfonyStyle->success($success_msg);

		return 0;
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function configure(): void {
		$this->setDescription('If you call this command then it runs ldap_cron.');
		$this->setHelp(
						<<<EOF
			If you call <info>%command.name%</info> then it runs ldap_cron
			<info>php %command.full_name%</info>
			EOF
		);
	}
}











