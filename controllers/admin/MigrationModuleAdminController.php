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

if (!class_exists('MigrationModule'))
	require _PS_MODULE_DIR_.'migration/classes/MigrationModule.php';

class MigrationModuleAdminController extends ModuleAdminController
{
	public function __construct()
	{
		$this->table = '';
		$this->className = 'MigrationModule';
		$this->identifier = 'id';

		$this->module = 'migration';

		$this->bootstrap = true;

		$this->list_no_link = true;

		$this->filters = array();
		$this->filter = true;
		$this->applyFilter = true;
		if (isset($_POST['submitReset']))
		{
			$this->applyFilter = false;
		}

		if (Tools::getIsset('detail'))
		{
			$this->addRowAction('apply');
			$this->addRowAction('unapply');
		}
		else
		{
			$this->addRowAction('detail');
			$this->addRowAction('migrate');
		}

		$this->initFieldsList();

		parent::__construct();

		if (Tools::getIsset('migrate'))
			$this->migrateAction();
		elseif (Tools::getIsset('apply'))
			$this->applyAction();
		elseif (Tools::getIsset('unapply'))
			$this->unapplyAction();
	}

	protected function initFieldsList()
	{
		if (Tools::getIsset('detail'))
			$this->fields_list = array(
				'id' => array(
					'title' => $this->l('Name'),
					'align' => 'left',
					'width' => 200,
					'orderby' => false,
					'search' => false,
				),
				'applied' => array(
					'title' => $this->l('Is applied?'),
					'align' => 'left',
					'width' => 200,
					'orderby' => false,
					'type' => 'bool',
					'active' => 'status',
					'search' => false,
				),
			);
		else
			$this->fields_list = array(
				'id' => array(
					'title' => $this->l('Name'),
					'align' => 'left',
					'width' => 200,
					'orderby' => false,
				),
				'has_version_not_applied' => array(
					'title' => $this->l('Is up to date?'),
					'align' => 'left',
					'width' => 200,
					'orderby' => false,
					'type' => 'bool',
					'active' => 'status',
				),
			);
	}

	public function getList($id_lang, $order_by = NULL, $order_way = NULL, $start = 0, $limit = NULL, $id_lang_shop = false)
	{
		$this->_list = array();

		if (Tools::getIsset('detail'))
		{
			$module_name = Tools::getValue('id');
			$module = MigrationModule::getInstance($module_name);
			foreach ($module->getStatus() as $version => $status)
			{
				$row = array(
					'id' => $version,
					'applied' => $status == MigrationModule::APPLIED,
				);

				$this->_list[$version] = $row;
			}
		}
		else
			foreach (MigrationModule::getModuleList() as $module)
			{
				$module_object = MigrationModule::getInstance($module);

				$row = array(
					'id' => $module_object->getModuleName(),
					'has_version_not_applied' => !$module_object->hasVersionNotApplied(),
				);

				// Check filters
				if (!$this->matchFilters($row))
					continue;

				$this->_list[$module_object->getModuleName()] = $row;
			}
	}

	public function matchFilters($data)
	{
		if (!$this->applyFilter)
			return true;

		foreach ($this->filters as $key => $value)
		{
			switch ($key)
			{
				case 'has_version_not_applied':
					if ($value != $data[$key])
						return false;
				break;
				default:
					if (!preg_match("/$value/Usi", $data[$key]))
					return false;
			}
		}
		return true;
	}

	/**
	 * Set the filters used for the list display
	 */
	public function processFilter()
	{
		if (!isset($this->list_id))
			$this->list_id = $this->table;

		$prefix = str_replace(array('admin', 'controller'), '', Tools::strtolower(get_class($this)));

		if (isset($this->list_id))
		{
			foreach ($_POST as $key => $value)
			{
				if ($value === '')
					unset($this->context->cookie->{$prefix.$key});
				elseif (stripos($key, $this->list_id.'Filter_') === 0)
					$this->context->cookie->{$prefix.$key} = !is_array($value) ? $value : serialize($value);
				elseif (stripos($key, 'submitFilter') === 0)
					$this->context->cookie->$key = !is_array($value) ? $value : serialize($value);
			}

			foreach ($_GET as $key => $value)
				if (stripos($key, $this->list_id.'OrderBy') === 0 && Validate::isOrderBy($value))
				{
					if ($value === '' || $value == $this->_defaultOrderBy)
						unset($this->context->cookie->{$prefix.$key});
					else
						$this->context->cookie->{$prefix.$key} = $value;
				}
				elseif (stripos($key, $this->list_id.'Orderway') === 0 && Validate::isOrderWay($value))
				{
					if ($value === '' || $value == $this->_defaultOrderWay)
						unset($this->context->cookie->{$prefix.$key});
					else
						$this->context->cookie->{$prefix.$key} = $value;
				}
		}

		$filters = $this->context->cookie->getFamily($prefix.$this->list_id.'Filter_');

		$this->filters = array();
		foreach ($filters as $key => $value)
		{
			$key = str_replace($this->module->name.'moduleFilter_', '', $key);
			$this->filters[$key] = $value;
		}
	}

	public function applyAction()
	{
		$module_name = Tools::getValue('id');
		$module = MigrationModule::getInstance($module_name);
		$module->apply(Tools::getValue('version'));

		return Tools::redirectAdmin('?controller=MigrationModuleAdmin&id='.$module_name.'&detail=1&token='.$this->token);
	}

	public function unapplyAction()
	{
		$module_name = Tools::getValue('id');
		$module = MigrationModule::getInstance($module_name);
		$module->unapply(Tools::getValue('version'));

		return Tools::redirectAdmin('?controller=MigrationModuleAdmin&id='.$module_name.'&detail=1&token='.$this->token);
	}

	public function migrateAction()
	{
		$module_name = Tools::getValue('id');
		$module = MigrationModule::getInstance($module_name);
		$module->upgradeToLastVersion();

		return Tools::redirectAdmin('?controller=MigrationModuleAdmin&token='.$this->token);
	}

	/**
	 * Custom action icon "detail"
	 */
	public function displayDetailLink($token = null, $id)
	{
		if (!array_key_exists('detail', self::$cache_lang))
			self::$cache_lang['detail'] = $this->l('Details');

		$this->context->smarty->assign(array(
			'module_dir' => __PS_BASE_URI__.'modules/migration/',
			'href' => self::$currentIndex.
				'&'.$this->identifier.'='.$id.
				'&detail=1&token='.($token != null ? $token : $this->token),
			'action' => self::$cache_lang['detail'],
		));

		if (version_compare(_PS_VERSION_, '1.6', '>'))
			return $this->context->smarty->fetch(_PS_MODULE_DIR_.'migration/views/templates/admin/list_action/detail.tpl');
		else
			return $this->context->smarty->fetch(_PS_MODULE_DIR_.'migration/views/templates/admin/list_action/detail-1.5.tpl');
	}

	/**
	 * Custom action icon "apply"
	 */
	public function displayApplyLink($token = null, $id)
	{
		if (!array_key_exists('apply', self::$cache_lang))
			self::$cache_lang['apply'] = $this->l('Apply');

		$this->context->smarty->assign(array(
			'module_dir' => __PS_BASE_URI__.'modules/migration/',
			'href' => self::$currentIndex.
				'&'.$this->identifier.'='.Tools::getValue('id').
				'&version='.$id.
				'&apply=1&token='.($token != null ? $token : $this->token),
			'action' => self::$cache_lang['apply'],
		));

		if (version_compare(_PS_VERSION_, '1.6', '>'))
			return $this->context->smarty->fetch(_PS_MODULE_DIR_.'migration/views/templates/admin/list_action/apply.tpl');
		else
			return $this->context->smarty->fetch(_PS_MODULE_DIR_.'migration/views/templates/admin/list_action/apply-1.5.tpl');
	}

	/**
	 * Custom action icon "unapply"
	 */
	public function displayUnapplyLink($token = null, $id)
	{
		if (!array_key_exists('unapply', self::$cache_lang))
			self::$cache_lang['unapply'] = $this->l('Unapply');

		$this->context->smarty->assign(array(
			'module_dir' => __PS_BASE_URI__.'modules/migration/',
			'href' => self::$currentIndex.
				'&'.$this->identifier.'='.Tools::getValue('id').
				'&version='.$id.
				'&unapply=1&token='.($token != null ? $token : $this->token),
			'action' => self::$cache_lang['unapply'],
		));

		if (version_compare(_PS_VERSION_, '1.6', '>'))
			return $this->context->smarty->fetch(_PS_MODULE_DIR_.'migration/views/templates/admin/list_action/unapply.tpl');
		else
			return $this->context->smarty->fetch(_PS_MODULE_DIR_.'migration/views/templates/admin/list_action/unapply-1.5.tpl');
	}

	/**
	 * Custom action icon "migrate"
	 */
	public function displayMigrateLink($token = null, $id)
	{
		if (!array_key_exists('migrate', self::$cache_lang))
			self::$cache_lang['migrate'] = $this->l('Apply all versions');

		$this->context->smarty->assign(array(
			'module_dir' => __PS_BASE_URI__.'modules/migration/',
			'href' => self::$currentIndex.
				'&'.$this->identifier.'='.$id.
				'&migrate=1&token='.($token != null ? $token : $this->token),
			'action' => self::$cache_lang['migrate'],
		));

		if (version_compare(_PS_VERSION_, '1.6', '>'))
			return $this->context->smarty->fetch(_PS_MODULE_DIR_.'migration/views/templates/admin/list_action/migrate.tpl');
		else
			return $this->context->smarty->fetch(_PS_MODULE_DIR_.'migration/views/templates/admin/list_action/migrate-1.5.tpl');
	}

	public function initToolbar()
	{
		parent::initToolbar();
		unset($this->toolbar_btn['new']);

		if (Tools::getIsset('detail'))
			$this->toolbar_btn['gotolist'] = array(
				'href' => $this->context->link->getAdminLink('MigrationModuleAdmin'),
				'desc' => $this->l('Back'),
				'imgclass' => 'back',
			);
	}
}
