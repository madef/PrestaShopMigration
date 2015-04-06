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

class MigrationVersion201504061207
	implements MigrationInterface
{
	public function up($module)
	{
		$langs = Language::getLanguages();
		$id_lang = (int)Configuration::getGlobalValue('PS_LANG_DEFAULT');

		// Get parent id tab
		$id_parent = Db::getInstance()->getValue('SELECT id_tab from '._DB_PREFIX_.'tab WHERE class_name = "AdminParentModules"');

		// Create tab crosssell
		$tab = new Tab();
		$tab->class_name = 'MigrationModuleAdmin';
		$tab->module = 'migration';
		$tab->id_parent = $id_parent;
		foreach ($langs as $l)
			$tab->name[$l['id_lang']] = $module->l('Migration');

		$tab->save();

		// Right management
		Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'access WHERE `id_tab` = '.(int)$tab->id);
		Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'module_access WHERE `id_module` = '.(int)$module->id);

		$profiles = Profile::getProfiles($id_lang);

		if (count($profiles))
			foreach ($profiles as $p)
			{
				Db::getInstance()->Execute('INSERT IGNORE INTO `'._DB_PREFIX_.'access`(`id_profile`,`id_tab`,`view`,`add`,`edit`,`delete`)
											VALUES ('.$p['id_profile'].', '.(int)$tab->id.',1,1,1,1)');
				Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'module_access(`id_profile`, `id_module`, `configure`, `view`)
											VALUES ('.$p['id_profile'].','.(int)$module->id.',1,1)');
			}
	}
	public function down($module)
	{
		Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'tab
									WHERE module = "'.pSql($module->name).'"');
		Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'module_access WHERE `id_module` = '.(int)$module->id);

	}
}
