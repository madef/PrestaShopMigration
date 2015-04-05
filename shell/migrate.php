<?php
/**
 * 2013-2015 MADEF IT
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@madef.fr so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    MADEF IT <contact@madef.fr>
 *  @copyright 2013-2015 MADEF IT
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Migration script: Migrate the data of your modules
 *
 * @usage php shell/migrate.php: display help
 * @usage php shell/migrate.php list: list modules with migration to do
 * @usage php shell/migrate.php listall: list all modules
 * @usage php shell/migrate.php show <module>: display all version of a module
 * @usage php shell/migrate.php migrate <module>: migrate a module to the last version
 * @usage php shell/migrate.php migrate <module> <version>: migrate a module to the version specified
 * @usage php shell/migrate.php apply <module> <version>: apply a version
 * @usage php shell/migrate.php unapply <module> <version>: unapply a version
 * @usage php shell/migrate.php install <module>: alias of php shell/migrate.php migrate <module>
 * @usage php shell/migrate.php uninstall <module>: uninstall a module
 * @usage php shell/migrate.php activate <module>: active a module
 * @usage php shell/migrate.php disable <module>: disable a module
 * @usage php shell/migrate.php version: version of the module migration
 * @usage php shell/migrate.php about: information about Migration
 * @usage php shell/migrate.php about <module>: information about the module
 */

if (!class_exists('MigrationModule'))
	require _PS_MODULE_DIR_.'migration/classes/MigrationModule.php';

if (!isset($argv[1]))
	$argv[1] = 'help';

try {
	switch ($argv[1])
	{
		case 'help':
			$commandes = array(
				'' => 'display help',
				' list' => 'list modules with migration to do',
				' listall' => 'list all modules',
				' show <module>' => 'display all version of a module',
				' migrate <module>' => 'migrate a module to the last version',
				' migrate <module> <version>' => 'migrate a module to the version specified',
				' apply <module> <version>' => 'apply a version',
				' unapply <module> <version>' => 'unapply a version',
				' install <module>' => 'alias of php shell/migrate.php migrate <module>',
				' uninstall <module>' => 'uninstall a module',
				' activate <module>' => 'active a module',
				' disable <module>' => 'disable a module',
				' version' => 'version of the module migration',
				' about' => 'information about Migration',
				' about <module>' => 'information about the module',
			);
			foreach ($commandes as $commande => $message)
				Message::note($argv[0].str_pad($commande, 30).': '.str_pad($message, 47));
			break;
		case 'list':
			$modules = MigrationModule::getModuleList();
			foreach ($modules as $module)
			{
				if (Module::isInstalled($module) && MigrationModule::getInstance($module)->hasVersionNotApplied())
					Message::warning(str_pad($module, 35).': has one or more versions files not applied');
			}
			break;
		case 'listall':
			$modules = MigrationModule::getModuleList();
			foreach ($modules as $module)
				if (Module::isInstalled($module))
				{
					if(MigrationModule::getInstance($module)->hasVersionNotApplied())
						Message::warning(str_pad($module, 35).': has one or more versions files not applied');
					else
						Message::success(str_pad($module, 35).': all versions applied                      ');
				}
				else
					Message::normal(str_pad($module, 35).': not installed');
			break;
		case 'show':
			if (!isset($argv[2]))
			{
				Message::error('Missing module name');
				break;
			}
			foreach (MigrationModule::getInstance($argv[2])->getStatus() as $version => $status)
				if ($status === MigrationModule::NOT_APPLIED)
					Message::warning("$version: not applied");
				else
					Message::success("$version: applied");
			break;
		case 'migrate':
			if (!isset($argv[2]))
			{
				Message::error('Missing module name');
				break;
			}

			if (isset($argv[3]))
				MigrationModule::getInstance($argv[2])->upgradeToVersion($argv[3]);
			else
				MigrationModule::getInstance($argv[2])->upgradeToLastVersion();
			break;
		case 'apply':
			if (!isset($argv[2]))
			{
				Message::error('Missing module name');
				break;
			}

			if (!isset($argv[3]))
			{
				Message::error('Missing version');
				break;
			}

			MigrationModule::getInstance($argv[2])->apply($argv[3]);
			break;
		case 'unapply':
			if (!isset($argv[2]))
			{
				Message::error('Missing module name');
				break;
			}

			if (!isset($argv[3]))
			{
				Message::error('Missing version');
				break;
			}

			MigrationModule::getInstance($argv[2])->unapply($argv[3]);
			break;
		case 'install':
			if (!isset($argv[2]))
			{
				Message::error('Missing module name');
				break;
			}
			$module_name = $argv[2];
			if (Module::isInstalled($module_name))
			{
				Message::warning('Module "'.$module_name.'" is already installed');
				break;
			}

			if (!class_exists($module_name))
				require _PS_MODULE_DIR_.$module_name.'/'.$module_name.'.php';
			$module = new $module_name();
			$module->install();
			break;
		case 'uninstall':
			if (!isset($argv[2]))
			{
				Message::error('Missing module name');
				break;
			}
			$module_name = $argv[2];
			if (!Module::isInstalled($module_name))
			{
				Message::warning('Module "'.$module_name.'" is already uninstalled');
				break;
			}

			if (!class_exists($module_name))
				require _PS_MODULE_DIR_.$module_name.'/'.$module_name.'.php';
			$module = new $module_name();
			$module->uninstall();
			break;
		case 'activate':
			if (!isset($argv[2]))
			{
				Message::error('Missing module name');
				break;
			}
			$module_name = $argv[2];
			if (!Module::isInstalled($module_name))
			{
				Message::warning('Module "'.$module_name.'" is not installed');
				break;
			}

			if (!class_exists($module_name))
				require _PS_MODULE_DIR_.$module_name.'/'.$module_name.'.php';
			$module = new $module_name();
			$module->enable(true);
			break;
		case 'disable':
			if (!isset($argv[2]))
			{
				Message::error('Missing module name');
				break;
			}
			$module_name = $argv[2];
			if (!Module::isInstalled($module_name))
			{
				Message::warning('Module "'.$module_name.'" is not installed');
				break;
			}

			if (!class_exists($module_name))
				require _PS_MODULE_DIR_.$module_name.'/'.$module_name.'.php';
			$module = new $module_name();
			$module->disable(true);
			break;
		case 'version':
			if (!class_exists('migration'))
				require _PS_MODULE_DIR_.'migration/migration.php';
			$migration = new Migration();
			Message::note($migration->version);
			break;
		case 'about':
			if (!isset($argv[2]))
				$module_name = 'migration';
			else
				$module_name = $argv[2];
			if (!class_exists($module_name))
				require _PS_MODULE_DIR_.$module_name.'/'.$module_name.'.php';
			$module = new $module_name();

			$titles = array(
				'Author' => $module->author,
				'Version' => $module->version,
				'Description' => $module->description,
				'Active' => ($module->active) ? 'yes' : 'no',
				'Installed' => Module::isInstalled($module_name) ? 'yes' : 'no',
				'Up To Date' => !MigrationModule::getInstance($module->name)->hasVersionNotApplied() ? 'yes' : 'no',
			);
			foreach ($titles as $title => $message)
				Message::normal(str_pad($title, 20).': '.$message);
			break;
		default:
			Message::error('Unknow action "'.$argv[1].'"');
			Message::note('Try "'.$argv[0].' help"');
			break;
	}
}
catch (Exception $e)
{
	Message::error('Fatal Error: '.$e->getMessage());
}

class Message
{
	const SUCCESS = '[42m';
	const FAILURE = '[41m';
	const WARNING = '[43m';
	const NOTE = '[44m';
	const NORMAL = '';

	public static function success($message)
	{
		self::display($message, self::SUCCESS);
	}

	public static function warning($message)
	{
		self::display($message, self::WARNING);
	}

	public static function note($message)
	{
		self::display($message, self::NOTE);
	}

	public static function error($message)
	{
		self::display($message, self::FAILURE);
	}

	public static function normal($message)
	{
		self::display($message, self::NORMAL);
	}

	public static function display($message, $code)
	{
		if ($code === self::NORMAL)
			echo $message."\n";
		else
			echo chr(27).$code.$message.chr(27).'[0m'."\n";
	}
}
