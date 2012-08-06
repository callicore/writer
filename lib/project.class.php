<?php
/**
 * db.class.php - wrapper for pdo and sqlite to manage open project
 *
 * extends pdo class, forces singleton instance
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: project.class.php 46 2006-12-01 19:30:38Z emsmith $
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   writer
 * @category     lib
 * @filesource
 */

/**
 * Db - pdo wrapper class for easy pdo management
 *
 * forces pdo as a singleton
 */
class Db extends PDO
{
	/**
	 * sql definition
	 * @var $sql string
	 */
	protected $sql =
'CREATE TABLE "character" (
  "id" INTEGER PRIMARY KEY,
  "name" TEXT UNIQUE,
  "order" INTEGER,
  "date_created" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "date_edited" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE "character_meta" (
  "id" INTEGER PRIMARY KEY,
  "name" TEXT UNIQUE,
  "display" TEXT,
  "default" TEXT,
  "order" INTEGER,
  "character_meta_type_id_fk" INTEGER NOT NULL DEFAULT 0,
  "date_created" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "date_edited" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE "character_meta_type" (
  "id" INTEGER PRIMARY KEY,
  "name" TEXT UNIQUE,
  "list" TEXT
);

INSERT INTO "character_meta_type"("name", "list") VALUES (\'entry\', \'TEXT\');
INSERT INTO "character_meta_type"("name", "list") VALUES (\'text\', NULL);
INSERT INTO "character_meta_type"("name", "list") VALUES (\'toggle\', \'TOGGLE\');
INSERT INTO "character_meta_type"("name", "list") VALUES (\'choice\', NULL);
INSERT INTO "character_meta_type"("name", "list") VALUES (\'image\', \'PIXBUF\');

CREATE TABLE "character_meta_option" (
  "id" INTEGER PRIMARY KEY,
  "name" TEXT UNIQUE,
  "display" TEXT,
  "value" TEXT,
  "character_meta_id_fk" INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE "character_has_character_meta" (
  "id" INTEGER PRIMARY KEY,
  "value" TEXT,
  "character_id_fk" INTEGER NOT NULL DEFAULT 0,
  "character_meta_id_fk" INTEGER NOT NULL DEFAULT 0,
  "character_meta_option_id_fk" INTEGER
);

INSERT INTO "character"("name", "order") VALUES (\'NONE\', 1);
';

	/**
	 * public function __construct
	 *
	 * constructor will throw an exception if a class already exists - use
	 * instance to create the class
	 *
	 * @param string $file file to open
	 * @return void
	 */
	public function __construct($file)
	{
		if(self::$check == FALSE)
		{
			throw new Exception(Writer::i18n(
			'%1$s is a singleton class - use %1$s::instance() to retrieve the current object',
			'Db'));
		}
		self::$singleton = $this;

		$new = file_exists($file) ? FALSE : TRUE;
		parent::__construct('sqlite:' . $file);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if($new == TRUE)
		{
			$this->beginTransaction();
			try
			{
				$create = $this->exec($this->sql);
			}
			catch(PDOException $e)
			{
				$this->rollback();
				$info = $this->errorInfo();
				unset($file, $new, $e);
				throw new Exception(Writer::i18n('Creating character storage failed: "%s"', $info[2]));
			}
			$this->commit();
		}
		unset($file, $new);
		return;
	}

	/**
	 * public function identify
	 *
	 * quote identifier
	 *
	 * @param string $string string to quote
	 * @return string
	 */
	public function identify($string)
	{
		return '"' . $string . '"';
	}
}
?>