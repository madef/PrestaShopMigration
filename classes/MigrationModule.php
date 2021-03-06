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

require _PS_MODULE_DIR_.'/migration/classes/MigrationInterface.php';

class MigrationModule
{
	const NOT_APPLIED = 'NOT APPLIED';
	const APPLIED = 'APPLIED';

	static $instances = array();
	static $module_list = array();
	protected $module;
	protected $versions;
	protected $status;
	protected $installed;

	public static function getInstance($module)
	{
		if (!isset(self::$instances[$module]))
			self::$instances[$module] = new MigrationModule($module);

		return self::$instances[$module];
	}

	protected function __construct($module)
	{
		$this->module = $module;
		$this->versions = array();
		$this->installed = Module::isInstalled($module);

		if (!file_exists(_PS_MODULE_DIR_.$this->module))
			throw new Exception('Unknow module "'.$module.'"');

		$this->loadVersions();
		$this->loadStatus();
	}

	/**
	 * Load status of each versions using file .migration.status
	 */
	protected function loadStatus()
	{
		$this->status = array();
		$file = _PS_MODULE_DIR_.$this->module.'/.migration.status';
		if (file_exists($file))
		{
			$content = Tools::file_get_contents($file);
			$this->status = Tools::jsonDecode($content, true);
		}

		foreach ($this->getVersions() as $version)
		{
			if (!isset($this->status[$version]))
				$this->updateStatus($version, self::NOT_APPLIED);
		}
	}

	/**
	 * Get the status of each version
	 *
	 * @return array
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Update a status into file .migration.status
	 *
	 * @param string $version version
	 * @param string $status NOT APPLIED' or APPLIED
	 */
	protected function updateStatus($version, $status)
	{
		$this->status[$version] = $status;
		$content = Tools::jsonEncode($this->status);

		$file = _PS_MODULE_DIR_.$this->module.'/.migration.status';
		file_put_contents($file, $content);
		@chmod($file, 0777);
	}

	/**
	 * Load all versions available from directory migration
	 */
	protected function loadVersions()
	{
		$directory = _PS_MODULE_DIR_.$this->module.'/migration';
		if (!file_exists($directory))
			return;

		$files = glob("{$directory}/version*.php");

		// Order versions files
		sort($files);

		$this->versions = array_map(array($this, 'extractVersion'), $files);
	}

	/**
	 * Form file name, retrieve the version
	 *
	 * @param string $file filename
	 *
	 * @return string version
	 */
	protected function extractVersion($file)
	{
		$directory = _PS_MODULE_DIR_.$this->module.'/migration';
		$length = strlen($directory) + 8;
		return substr($file, $length, -4);
	}

	/**
	 * Get all versions
	 */
	public function getVersions()
	{
		return $this->versions;
	}

	/**
	 * Has version not applied?
	 *
	 * @return bool true if at least one version is not applied
	 */
	public function hasVersionNotApplied()
	{
		foreach ($this->getStatus() as $status)
			if ($status === self::NOT_APPLIED)
				return true;

		return false;
	}

	/**
	 * Check if a version is applied
	 *
	 * @param string $version version
	 *
	 * @return bool true if the version is applied
	 */
	protected function isVersionApplied($version)
	{
		return $this->status[$version] === self::APPLIED;
	}

	/**
	 * Check if a version exists
	 *
	 * @param string $version version
	 *
	 * @return bool true if the version exists
	 */
	protected function versionExists($version)
	{
		return in_array($version, $this->getVersions());
	}

	/**
	 * Get the last version available
	 *
	 * @return string|null last version available
	 */
	public function getLastVersion()
	{
		$versions = $this->getVersions();
		$last_version = end($versions);
		return $last_version;
	}

	/**
	 * Apply all version not applied
	 */
	public function upgradeToLastVersion()
	{
		$last_version = $this->getLastVersion();

		if (!$last_version)
			return;

		$this->upgradeToVersion($last_version);
	}

	/**
	 * Apply all version not applied and lower or equal
	 * than the version specify
	 *
	 * @param string $limit maximal version
	 */
	public function upgradeToVersion($limit)
	{
		if (!$this->versionExists($limit))
			throw new Exception('Unknow version "'.$limit.'"');

		foreach ($this->getVersions() as $version)
		{
			if ($version > $limit)
				return;

			$this->apply($version);
		}
	}

	/**
	 * Unapply all version applied and greater or equal
	 * than the version specify
	 *
	 * @param string $limit minimal version
	 */
	public function downgradeToVersion($limit)
	{
		if (!$this->versionExists($limit) && $limit != null)
			throw new Exception('Unknow version "'.$limit.'"');

		foreach (array_reverse($this->getVersions()) as $version)
		{
			if ($version < $limit)
				return;

			$this->unapply($version);
		}
	}

	/**
	 * Load the version class using version
	 *
	 * @param string $version version
	 *
	 * @return object instance of the version class
	 */
	protected function loadVersionClass($version)
	{
		$class = ucfirst($this->module).'Version'.$version;
		if (!class_exists($class))
			require _PS_MODULE_DIR_.$this->module.'/migration/version'.$version.'.php';

		return new $class();
	}

	/**
	 * Apply a specific version
	 *
	 * @param string $version version
	 */
	public function apply($version)
	{
		if ($this->isVersionApplied($version))
			return;

		$object = $this->loadVersionClass($version);
		$module = Module::getInstanceByName($this->module);
		$object->up($module);
		$this->updateStatus($version, self::APPLIED);
	}

	/**
	 * Unapply a specific version
	 *
	 * @param string $version version
	 */
	public function unapply($version)
	{
		if (!$this->isVersionApplied($version))
			return;

		$object = $this->loadVersionClass($version);
		$module = Module::getInstanceByName($this->module);
		$object->down($module);
		$this->updateStatus($version, self::NOT_APPLIED);
	}

	/**
	 * Return modules directory list
	 *
	 * @return array Modules Directory List
	 */
	public static function getModuleList()
	{
		if (empty(self::$module_list))
		{
			$modules = scandir(_PS_MODULE_DIR_);
			foreach ($modules as $name)
			{
				if (is_file(_PS_MODULE_DIR_.$name))
					continue;
				if (!Validate::isModuleName($name))
					continue;
				self::$module_list[] = $name;
			}
		}
		return self::$module_list;
	}

	/**
	 * Get module name
	 *
	 * @return string module name
	 */
	public function getModuleName()
	{
		return $this->module;
	}
}

