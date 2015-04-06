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

if (!defined('_PS_VERSION_'))
	exit;

class Migration extends Module
{
	public static $processModuleUpdateLock = false;
	public function __construct()
	{
		$this->name = 'migration';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'MADEF IT';
		$this->need_instance = 0;
		$this->module_key = '';

		parent::__construct();

		$this->displayName = $this->l('Migration');
		$this->description = $this->l('Manage data migration of PrestaShop modules.');

		$this->processModuleUpdate();

	}

	public function processModuleUpdate()
	{
		if (self::$processModuleUpdateLock)
			return;

		self::$processModuleUpdateLock = true;

		if (!($this->context->controller instanceof AdminModulesController))
			return;

		if (Tools::getIsset('module_name'))
			return;

		if (!class_exists('MigrationModule'))
			require _PS_MODULE_DIR_.'migration/classes/MigrationModule.php';

		$modules = MigrationModule::getModuleList();
		foreach ($modules as $module)
		{
			if (!Module::isInstalled($module))
				continue;

			if (!MigrationModule::getInstance($module)->hasVersionNotApplied())
				continue;

			try
			{
				MigrationModule::getInstance($module)->upgradeToLastVersion();
				$this->context->controller->success[] = $this->l('Module "'.$module.'" was update');
			}
			catch (Exception $e)
			{
				$this->context->controller->errors[] = $this->l('Error on upgrading "'.$module.'": '.$e->getMessage());
			}
		}
	}

	public function install()
	{
		if (!file_exists(_PS_ROOT_DIR_.'/shell') && !mkdir(_PS_ROOT_DIR_.'/shell'))
		{
			$this->context->controller->errors[] = $this->l('The module cannot create the directory "'._PS_ROOT_DIR_.'/shell". Add the directory and try again.');
			return false;
		}
		if (!file_exists(_PS_ROOT_DIR_.'/shell/migration.php') && !copy(_PS_MODULE_DIR_.'migration/shell/migrate.link.php', _PS_ROOT_DIR_.'/shell/migration.php'))
		{
			$this->context->controller->errors[] = $this->l('The module cannot copy "'._PS_MODULE_DIR_.'migration/shell/migrate.link.php" in "'._PS_ROOT_DIR_.'/shell/migration.php". Copy it manually and try again.');
			return false;
		}

		return parent::install();
	}

	public function uninstall()
	{
		if (file_exists(_PS_ROOT_DIR_.'/shell/migration.php'))
			unlink(_PS_ROOT_DIR_.'/shell/migration.php');
		if (file_exists(_PS_ROOT_DIR_.'/shell'))
			rmdir(_PS_ROOT_DIR_.'/shell');

		return parent::uninstall();
	}

}
