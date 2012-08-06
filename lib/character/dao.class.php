<?php
/**
 * dao.class.php - data access object for characters
 *
 * characters have basic information but can also have meta information
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: dao.class.php 146 2007-06-11 13:19:18Z emsmith $
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   writer
 * @category     lib
 * @filesource
 */

/**
 * CharacterDao - character data access object
 *
 * This can get a little messy because of the meta mapping
 */
class CharacterDao implements Iterator
{
	/**
	 * stores character meta data
	 * @var $meta array
	 */
	protected $meta = array();

	/**
	 * name => id map for metadata
	 * @var $metamap array
	 */
	protected $metamap = array();

	/**
	 * id => name map for liststore
	 * @var $listmap array
	 */
	protected $listmap = array();

	/**
	 * stores basic data
	 * @var $data array
	 */
	protected $data = array();

	/**
	 * public function __construct
	 *
	 * creates empty object or finds object by id or name
	 *
	 * @param string|int $id id or name to find
	 * @return void
	 */
	public function __construct($id = NULL)
	{
		$db = Db::instance();

		try
		{
			$statement = $db->query('PRAGMA table_info("character")');
			foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $data)
			{
				$this->data[$data['name']] = $data['dflt_value'];
				$this->listmap[] = $data['name'];
			}
			$this->listmap[] = 'color';
			$this->listmap[] = 'saved';
			$this->listmap[] = 'deleted';
			// select all available metas and their default
			$statement = $db->query('SELECT "id", "name", "default" from "character_meta"');
			foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $data)
			{
				$this->meta[$data['name']] = $data['default'];
				$this->metamap[$data['name']] = $data['id'];
				$this->listmap[] = $data['name'];
			}
			if(!is_null($id))
			{
				$sql = 'SELECT ' . implode(', ', array_map(array($db, 'identify'), array_keys($this->data))) . ' from "character" ';
				$statement = $db->prepare($sql . 'WHERE id= :id');
				$statement->execute(array(':id' => $id));
				$data = $statement->fetch(PDO::FETCH_ASSOC);
				if($data == FALSE)
				{
					$statement = $db->prepare($sql . 'WHERE name= :name');
					$statement->execute(array(':name' => $id));
					$data = $statement->fetch(PDO::FETCH_ASSOC);
				}
				if($data !== FALSE)
				{
					foreach($data as $key => $value)
					{
						$this->data[$key] = $value;
					}
					$statement = $db->prepare('SELECT "t1"."value", "t2"."name" FROM "character_has_character_meta" AS "t1" LEFT JOIN "character_meta" AS "t2" ON "t1"."character_meta_id_fk" = "t2"."id" WHERE "t1"."character_id_fk" = :id');
					$statement->execute(array(':id' => $this->data['id']));
					foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $data)
					{
						$this->meta[$data['name']] = $data['value'];
					}
				}
			}
		}
		catch(PDOException $e)
		{
			$info = $db->errorInfo();
			unset($db, $statement, $e);
			throw new Exception(Writer::i18n('Cannot create character object: %s', $info[2]));
		}

		unset($statement, $db, $data, $sql, $id, $key, $value);
		return;
	}

	/**
	 * public function delete
	 *
	 * saves the changed information to the database
	 *
	 * @return void
	 */
	public function delete()
	{
		$db = Db::instance();

		$db->beginTransaction();
		try
		{
			if(!is_null($this->data['id']) && $this->data['id'] != FALSE)
			{
				// if id is int, see if it exists
				$statement = $db->prepare('SELECT COUNT("id") FROM "character" WHERE "id" = :id');
				$statement->execute(array(':id' => $this->data['id']));
				$check = $statement->fetch(PDO::FETCH_COLUMN);
			}
			if(!isset($check) || $check == FALSE)
			{
				// otherwise lookup via name
				$statement = $db->prepare('SELECT "id" FROM "character" WHERE "name" = :name');
				$statement->execute(array(':name' => $this->data['name']));
				$check = $statement->fetch(PDO::FETCH_COLUMN);
			}
			if($check !== FALSE)
			{
				// delete all meta data
				$statement = $db->prepare('DELETE FROM "character_has_character_meta" WHERE "character_id_fk" = :id');
				$statement->execute(array(':id' => $this->data['id']));
				// grab order
				$statement = $db->prepare('SELECT "order" FROM "character" WHERE "id" = :id');
				$statement->execute(array(':id' => $this->data['id']));
				$order = $statement->fetch(PDO::FETCH_COLUMN);
				// delete main
				$statement = $db->prepare('DELETE FROM "character" WHERE "id" = :id');
				$statement->execute(array(':id' => $this->data['id']));
				// all larger orders get decremented
				$statement = $db->prepare('UPDATE "character" SET "order" = "order" - 1 WHERE "order" > :order');
				$statement->execute(array(':order' => $order));
			}
		}
		catch(PDOException $e)
		{
			unset($statement);
			$db->rollback();
			$info = $db->errorInfo();
			unset($db, $e);
			throw new Exception(Writer::i18n('Cannot delete character: %s', $info[2]));
		}
		unset($statement);
		$db->commit();
		$this->__construct();
		unset($db, $check, $order);
		return;
	}

	/**
	 * public function save
	 *
	 * removes a character record
	 *
	 * @return void
	 */
	public function save()
	{
		$db = Db::instance();

		$db->beginTransaction();
		try
		{
			// see if it's in the db to delete
			if(!empty($this->data['id']))
			{
				// if id is int, see if it exists
				$statement = $db->prepare('SELECT "id" FROM "character" WHERE "id" = :id');
				$statement->execute(array(':id' => $this->data['id']));
				$check = $statement->fetch(PDO::FETCH_COLUMN);
			}
			else
			{
				// otherwise lookup via name
				$statement = $db->prepare('SELECT "id" FROM "character" WHERE "name" = :name');
				$statement->execute(array(':name' => $this->data['name']));
				$check = $statement->fetch(PDO::FETCH_COLUMN);
			}
			if($check === FALSE)
			{
				// insert main and grab id
				$statement = $db->prepare('INSERT INTO "character"("name", "order", "date_created", "date_edited") VALUES (:name, :order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
				$statement->execute(array(':name' => $this->data['name'], ':order' => $this->data['order']));
				$this->data['id'] = $db->lastInsertId();
				// prepare and insert meta data in loop
				$statement = $db->prepare('INSERT INTO "character_has_character_meta"("value","character_meta_id_fk", "character_id_fk") VALUES (:value, :metaid, :id)');
				$statement->bindValue(':id', $this->data['id']);
				$statement->bindParam(':metaid', $metaid);
				$statement->bindParam(':value', $value);
				foreach($this->meta as $key => $value)
				{
					$metaid = $this->metamap[$key];
					$statement->execute();
				}
				// refill non-tamperable, id, order, dates
				$statement = $db->prepare('SELECT "order", "date_created", "date_edited" FROM "character" WHERE "id" = :id');
				$statement->execute(array(':id' => $this->data['id']));
				$data = $statement->fetch(PDO::FETCH_ASSOC);
				foreach($data as $id => $value)
				{
					$this->data[$id] = $value;
				}
			}
			else
			{
				$this->data['id'] = $check;
				// main data update
				$statement = $db->prepare('UPDATE "character" SET "name" = :name, "order" = :order, "date_edited" = CURRENT_TIMESTAMP WHERE "id" = :id');
				$statement->execute(array(':name' => $this->data['name'], ':id' => $this->data['id'], ':order' => $this->data['order']));
				// check and insert/update meta data
				foreach($this->meta as $id => $value)
				{
					$check = $db->prepare('SELECT COUNT("id") FROM "character_has_character_meta" WHERE "character_meta_id_fk" = :metaid AND "character_id_fk" = :id');
					$check->execute(array(':id' => $this->data['id'], ':metaid' => $this->metamap[$id]));
					$check = (int) $check->fetch(PDO::FETCH_COLUMN);
					if($check > 0)
					{
						$statement = $db->prepare('UPDATE "character_has_character_meta" SET "value" = :value WHERE "character_meta_id_fk" = :metaid AND "character_id_fk" = :id');
						$statement->execute(array(':id' => $id, ':metaid' => $this->metamap[$id], ':value' => $value));
					}
					else
					{
						$statement = $db->prepare('INSERT INTO "character_has_character_meta"("value","character_meta_id_fk", "character_id_fk") VALUES (:value, :metaid, :id)');
						$statement->execute(array(':id' => $id, ':metaid' => $this->metamap[$id], ':value' => $value));
					}
				}
				// refill non-tamperable dates
				$statement = $db->prepare('SELECT "date_edited", "date_created" FROM "character" WHERE "id" = :id');
				$statement->execute(array(':id' => $this->data['id']));
				$data = $statement->fetch(PDO::FETCH_ASSOC);
				foreach($data as $id => $value)
				{
					$this->data[$id] = $value;
				}
			}
		}
		catch(PDOException $e)
		{
			unset($statement);
			$db->rollback();
			$info = $db->errorInfo();
			unset($db, $e);
			throw new Exception(Writer::i18n('Cannot save character: %s', $info[2]));
		}
		unset($statement);
		$db->commit();
		unset($db, $data, $id, $value, $metaid, $check, $key);
		return;
	}

	/**
	 * public function storeArray
	 *
	 * returns the object as a simple array
	 * plus color and saved for datastore
	 *
	 * @return array
	 */
	public function storeArray()
	{
		return array_merge(array_values($this->data), array('#000000', TRUE, FALSE), array_values($this->meta));
	}

	/**
	 * static public function find
	 *
	 * return an array of character objects, can add sql clause
	 *
	 * @param string|int pass list of ids or names to select
	 * @return array of objects instanceof CharacterDao
	 */
	static public function find($sql = NULL)
	{
		$db = Db::instance();

		$statement = $db->query('SELECT "id" FROM "character"' . $sql);
		$ids = $statement->fetchAll(PDO::FETCH_COLUMN);
		unset($statement);
		$list = array();
		foreach($ids as $id)
		{
			$list[] = new CharacterDao($id);
		}
		unset($db, $ids, $id);
		return $list;
	}

	/**
	 * static public function reorder
	 *
	 * because order is a PITA we have a function just for it
	 * is smart enough to reorder
	 *
	 * @param int $id primary key of row we're reordering
	 * @param int $new new position for row
	 * @return void
	 */
	static public function reorder($id, $new = NULL)
	{
		if(is_null($new))
		{
			return;
		}

		$db = Db::instance();

		try
		{
			// grab max
			$statement = $db->query('SELECT MAX("order") FROM "character"');
			$max = $statement->fetch(PDO::FETCH_COLUMN);

			if($new > $max)
			{
				$statement = $db->prepare('UPDATE "character" SET "order" = :order WHERE "id" = :id');
				$statement->execute(array(':id' => $id, ':order' => (int)++$max));
				unset($id, $new, $max, $statement, $db);
				return;
			}

			if($new < 0)
			{
				$new = 0;
			}
	
			$statement = $db->prepare('SELECT "order" FROM "character" WHERE "id" = :id');
			$statement->execute(array(':id' => $id));
			$old = $statement->fetch(PDO::FETCH_COLUMN);

			if($new == $old)
			{
				unset($id, $new, $max, $statement, $db, $old);
				return;
			}
			elseif($new > $old)
			{
				$statement = $db->prepare('UPDATE "character" SET "order" = "order" - 1 WHERE "order" <= :new AND "order" >= :old');
				$statement->execute(array(':old' => $old, ':new' => $new));
			}
			elseif($new < $old)
			{
				$statement = $db->prepare('UPDATE "character" SET "order" = "order" + 1 WHERE "order" >= :new AND "order" <= :old');
				$statement->execute(array(':old' => $old, ':new' => $new));
			}
	
			$statement = $db->prepare('UPDATE "character" SET "order" = :order WHERE "id" = :id');
			$statement->execute(array(':id' => $id, ':order' => $new));
		}
		catch(PDOException $e)
		{
			unset($statement);
			$info = $db->errorInfo();
			unset($db, $data, $check, $order, $id, $e, $metaid);
			throw new Exception(Writer::i18n('Cannot reorder character: %s', $info[2]));
		}

		unset($old, $id, $new, $max, $statement, $db);
		return ;
	}

	//----------------------------------------------------------------
	//             Overloading
	//----------------------------------------------------------------

	/**
	 * public function __set
	 *
	 * sets a value for the data store
	 *
	 * @param string $name item to set
	 * @param scalar $value value for item
	 * @return void
	 */
	public function __set($name, $value)
	{
		if(is_numeric($name) && isset($this->listmap[$name]))
		{
			$name = $this->listmap[$name];
		}
		if(array_key_exists($name, $this->data))
		{
			$this->data[$name] = $value;
		}
		if(array_key_exists($name, $this->meta))
		{
			$this->meta[$name] = $value;
		}
		return;
	}

	/**
	 * public function __get
	 *
	 * gets a value from the data store
	 *
	 * @param string $name item to grab
	 * @return mixed
	 */
	public function __get($name)
	{
		if(is_int($name) && isset($this->listmap[$name]))
		{
			$name = $this->listmap[$name];
		}
		if(isset($this->data[$name]))
		{
			return $this->data[$name];
		}
		elseif(isset($this->meta[$name]))
		{
			return $this->meta[$name];
		}
		return;
	}

	//----------------------------------------------------------------
	//             Iterate the data properly
	//----------------------------------------------------------------

	/**
	 * public function rewind
	 *
	 * uses array iteration funcs on data
	 *
	 * @return void
	 */
	public function rewind()
	{
		reset($this->data);
		reset($this->meta);
		return;
	}

	/**
	 * public function current
	 *
	 * returns current data value
	 *
	 * @return mixed
	 */
	public function current()
	{
		$current = current($this->data);
		if($current === FALSE)
		{
			return current($this->meta);
		}
		else
		{
			return $current;
		}
	}

	/**
	 * public function key
	 *
	 * uses array key function on internal current data array
	 *
	 * @return mixed
	 */
	public function key()
	{
		$current = current($this->data);
		if($current === FALSE)
		{
			return key($this->meta);
		}
		else
		{
			return key($this->data);
		}
	}

	/**
	 * public function next
	 *
	 * advances the pointer on internal array
	 *
	 * @return void
	 */
	public function next()
	{
		$current = current($this->data);
		if($current === FALSE)
		{
			next($this->meta);
		}
		else
		{
			next($this->data);
		}
		unset($current);
	}

	/**
	 * public function valid
	 *
	 * checks to see if we get false from current
	 *
	 * @return bool
	 */
	public function valid()
	{
		return $this->current() !== FALSE;
	}

	//----------------------------------------------------------------
	//             Meta Data Options
	//----------------------------------------------------------------

	/**
	 * static public function addMeta
	 *
	 * add a new meta data option and return id of it
	 *
	 * @param string $name name for the data
	 * @param string $display display name for the data
	 * @param string $default default value for the data
	 * @param string $type type of meta data (from type table)
	 * @param array $options list of options to add to 
	 * @return int
	 */
	static public function addMeta($name, $display, $default, $type, $options = NULL)
	{
		$db = Db::instance();
		$db->beginTransaction();
		try
		{
			if($name === 'id' || $name === 'name')
			{
				$name = 'meta' . $name;
			}
			// make sure it doesn't exist
			$statement = $db->prepare('SELECT COUNT("id") FROM "character_meta" WHERE "name" = :name');
			$statement->execute(array(':name' => $name));
			$check = $statement->fetch(PDO::FETCH_COLUMN);
			if($check > 0)
			{
				unset($statement, $db, $display, $default, $type, $options, $check);
				throw new Exception(Writer::i18n('Meta item "%s" already exists', $name));
			}
			// get type
			$statement = $db->prepare('SELECT "id" FROM "character_meta_type" WHERE "name" = :type');
			$statement->execute(array(':type' => $type));
			$type = $statement->fetch(PDO::FETCH_COLUMN);
			// do insert
			$statement = $db->prepare('INSERT INTO "character_meta"("name", "display", "default", "character_meta_type_id_fk") VALUES(:name, :display, :default, :type)');
			$statement->execute(array(':name' => $name, ':display' => $display, ':default' => $default, ':type' => $type));
			$id = $db->lastInsertId();
			// options if any
			if(!is_null($options) && is_array($options))
			{
				$statement = $db->prepare('INSERT INTO "character_meta_option"("name", "display", "value", "character_meta_id_fk") VALUES(:name, :display, :value, :id)');
				$statement->bindValue(':id', $id);
				$statement->bindParam(':name', $name);
				$statement->bindParam(':display', $display);
				$statement->bindParam(':value', $value);
				foreach($options as $data)
				{
					$name = isset($data['name']) ? $data['name'] : (isset($data[0]) ? $data[0] : NULL);
					$display = isset($data['display']) ? $data['display'] : (isset($data[0]) ? $data[0] : NULL);
					$value = isset($data['value']) ? $data['value'] : (isset($data[0]) ? $data[0] : NULL);
					$statement->execute();
				}
			}
		}
		catch(PDOException $e)
		{
			unset($statement);
			$db->rollback();
			$info = $db->errorInfo();
			unset($db, $name, $display, $default, $type, $options, $type, $id, $data, $value, $check);
			throw new Exception(Writer::i18n('Cannot create meta item: %s', $info[2]));
		}
		unset($statement, $name, $display, $default, $type, $options, $type, $id, $data, $value);
		$db->commit();
		return $db->lastInsertId();
	}

	/**
	 * static public function changeMeta
	 *
	 * alter meta data information
	 *
	 * @param int $id id of meta to change
	 * @param string $name name for the data
	 * @param string $display display name for the data
	 * @param string $default default value for the data
	 * @param string $type type of meta data (from type table)
	 * @param array $options list of options to add to 
	 * @return void
	 */
	static public function changeMeta($id, $name = NULL, $display = NULL, $default = NULL, $type = NULL, $options = NULL)
	{
		$db = Db::instance();
		$db->beginTransaction();
		try
		{
			// make sure it exists
			$statement = $db->prepare('SELECT COUNT("id") FROM "character_meta" WHERE "id" = :id');
			$statement->execute(array(':id' => $id));
			$check = $statement->fetch(PDO::FETCH_COLUMN);
			if($check < 1)
			{
				unset($statement, $db, $display, $default, $type, $options, $check);
				throw new Exception(Writer::i18n('Meta item "%s" does not exist', $name));
			}
			$updates = array();
			$bind = array(':id' => $id);
			if(!is_null($name))
			{
				if($name === 'id' || $name === 'name')
				{
					$name = 'meta' . $name;
				}
				$updates[] = '"name" = :name';
				$bind[':name'] = $name;
			}
			if(!is_null($display))
			{
				$updates[] = '"display" = :display';
				$bind[':display'] = $display;
			}
			if(!is_null($default))
			{
				$updates[] = '"default" = :default';
				$bind[':default'] = $default;
			}
			if(!is_null($display))
			{
				// get type
				$statement = $db->prepare('SELECT "id" FROM "character_meta_type" WHERE "name" = :type');
				$statement->execute(array(':type' => $type));
				$type = $statement->fetch(PDO::FETCH_COLUMN);
				$updates[] = '"character_meta_type_id_fk" = :type';
				$bind[':type'] = $type;
			}
			// do update (if needed
			if(count($updates) < 1)
			{
				// do update
				$statement = $db->prepare('UPDATE "character_meta" SET ' . implode(', ', $updates) . ' WHERE id = :id');
				$statement->execute($bind);
			}
			// options if any
			if(!is_null($options) && is_array($options))
			{
				$statement->prepare('DELETE FROM "character_meta_option" WHERE "character_meta_id_fk" = :id');
				$statement->execute(array(':id' => $id));
				$statement = $db->prepare('INSERT INTO "character_meta_option"("name", "display", "value", "character_meta_id_fk") VALUES(:name, :display, :value, :id)');
				$statement->bindValue(':id', $id);
				$statement->bindParam(':name', $name);
				$statement->bindParam(':display', $display);
				$statement->bindParam(':value', $value);
				foreach($options as $data)
				{
					$name = isset($data['name']) ? $data['name'] : (isset($data[0]) ? $data[0] : NULL);
					$display = isset($data['display']) ? $data['display'] : (isset($data[0]) ? $data[0] : NULL);
					$value = isset($data['value']) ? $data['value'] : (isset($data[0]) ? $data[0] : NULL);
					$statement->execute();
				}
			}
		}
		catch(PDOException $e)
		{
			unset($statement);
			$db->rollback();
			$info = $db->errorInfo();
			unset($db, $name, $display, $default, $type, $options, $type, $id, $data, $value, $check);
			throw new Exception(Writer::i18n('Cannot create meta item: %s', $info[2]));
		}
		unset($check, $updates, $bind, $statement, $name, $display, $default, $type, $options, $type, $id, $data, $value);
		$db->commit();
		unset($db);
		return;
	}

	/**
	 * static public function removeMeta
	 *
	 * remove a meta data item
	 *
	 * @param int $id id of meta to remove
	 * @return void
	 */
	static public function removeMeta($id)
	{
		$db = Db::instance();
		$db->beginTransaction();
		try
		{
			// try delete
			$statement = $db->prepare('DELETE FROM "character_meta" WHERE "id" = :id');
			$statement->execute(array(':id' => $id));
			$statement = $db->prepare('DELETE FROM "character_meta_option" WHERE "character_meta_id_fk" = :id');
			$statement->execute(array(':id' => $id));
			$statement = $db->prepare('DELETE FROM "character_has_character_meta" WHERE "character_meta_id_fk" = :id');
			$statement->execute(array(':id' => $id));
		}
		catch(PDOException $e)
		{
			unset($statement);
			$db->rollback();
			$info = $db->errorInfo();
			unset($db, $id);
			throw new Exception(Writer::i18n('Cannot delete meta item: %s', $info[2]));
		}
		$db->commit();
		unset($db, $statement, $id);
		return;
	}

	/**
	 * static public function countMeta
	 *
	 * counts number of rows in meta column
	 *
	 * @return type about
	 */
	static public function countMeta()
	{
		$db = Db::instance();
		$statement = $db->query('SELECT COUNT("id") FROM "character_meta"');
		$count = $statement->fetch(PDO::FETCH_COLUMN);
		unset($db, $statement);
		return $count;
	}

	/**
	 * static public function listMeta
	 *
	 * return a list of meta data info to use for edit screen
	 *
	 * @return type about
	 */
	static public function listMeta()
	{
		$db = Db::instance();
		$statement = $db->query('SELECT "t1"."id", "t1"."name", "t1"."display", "order", "date_created", "date_edited", "t2"."name" AS "type" FROM "character_meta" AS "t1" LEFT JOIN "character_meta_type" AS "t2" ON "t1"."character_meta_type_id_fk" = "t2"."id"');
		$array = $statement->fetchAll(PDO::FETCH_ASSOC);
		unset($db, $statement);
		return $array;
	}
}
?>