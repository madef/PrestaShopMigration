Migration
=========

Migration is a module for PrestaShop. It helps to manage migration of the application data.
Whereas most version manager choose to use the "version", we prefer to use the creation date of the migration file.
There are multiple advantages of using this system :

 - No version conflictsif two developers work on the same module
 - As data migration do not follows the versions of the module, the developpers can maintain retro-compatibility more easily
 - Migration can be done manually and not some migration can be not done
 - Possibility of downgrade the data


How to use in my module
-----------------------

Migration can be implemented to your module simply by adding these lines in the "install" and "uninstall" method of your module:

```
public function install()
{
	if (!$this->installMigrationModule())
		return false;

	return parent::install();
}

public function uninstall()
{
	if (!$this->installMigrationModule())
		return false;

	return parent::uninstall();
}

public function installMigrationModule()
{
	try
	{
		// Install Migration module
		if (!file_exists(_PS_MODULE_DIR_.'migration'))
		{
			if (!Tools::copy('https://raw.githubusercontent.com/madef/PrestaShopMigration/master/installer.php', _PS_ROOT_DIR_.'/download/migration_installer.php'))
				throw new Exception('Installer was not copied');
			require _PS_ROOT_DIR_.'/download/migration_installer.php';
		}
	}
	catch (Exception $e)
	{
		$this->context->controller->errors[] = sprintf($this->l('The module "%1s" require the module "migration". Please, <a href=%2s>donwload</a> and install it first.'), $this->name, '"http://migration.prestashop.madef.fr/last_release.zip"');
		return false;
	}
	return true;
}
```

How to create version
---------------------

First, you have to add a directory "migration" in your module.
Choose a name for your version. We recommand to use the date. For example 201504041453 (2015-04-04 14:53).
Create the file "version201504041453.php" in the directory "migration" of your module.

In it add this code:

```
if (!defined('_PS_VERSION_'))
	exit;

class MyModuleVersion201504041453
	implements MigrationInterface
{
	public function up()
	{
		// Add your code for upgrade
	}
	public function down()
	{
		// Add your code for downgrade
	}
}
```


How to use shell
----------------

Shell scripts are installed in the directory "shell" on the root of PrestaShop. From the root execute :
```
$ php shell/migrate.php help
```

![ScreenShot](/doc/shell.png)

And for human
--------------

If you are a simple human, update will be done automatically on viewing the section "module" of your backoffice.


