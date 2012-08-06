<?php
/**
 * window.class.php - character window main class
 *
 * parent window for all character related adminitration tasks such as listing,
 * creating, deleting, editing characters and manipulating meta-data for
 * characters as well as importing, exporting, printing and saving characters
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: window.class.php 146 2007-06-11 13:19:18Z emsmith $
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   writer
 * @category     lib
 * @filesource
 */

/**
 * CharacterWindow - character window - list view looks a lot like main window
 *
 * Has menu bar and tool bar specific to characters and character listing
 */
class CharacterWindow extends Window
{

	/**
	 * list of characters
	 * @var $treeview instanceof GtkTreeview
	 */
	protected $treeview;

	/**
	 * store timeouts to remove
	 * @var $timeouts array
	 */
	protected $timeouts = array();

	/**
	 * array of all actions available to put on a toolbar
	 * @var $toolbuttons 
	 */
	protected $toolbuttons = array(
		'separator', 'new', 'open', 'close', 'delete', 'undelete', 'save',
		'revert', 'print', 'wizard', 'import', 'export',
		'characters', 'settings', 'editing', 'publishing', 'notes',
		'preferences','help', 'website', 'about',);

	/**
	 * default items for window
	 * @var $tooldefault array
	 */
	protected $tooldefault = array('open', 'new', 'wizard', 'separator', 'delete',
		'undelete', 'save', 'separator', 'import', 'export', 'print');

	/**
	 * format for displaying dates
	 * @var $date string
	 */
	protected $date;

	/**
	 * format for displaying dates
	 * @var $date string
	 */
	protected $timeout;

	/**
	 * character edit window
	 * @var $editwindow object instanceof Window
	 */
	protected $editwindow;

	/**
	 * public function __construct
	 *
	 * description
	 *
	 * @return void
	 */
	public function __construct()
	{

		parent::__construct();
		$this->set_title('List');
		$this->connect('show', array($this, 'parent'));

		$this->buildTreeview();
		$this->vbox->pack_start($this->treeview);

		$this->connect_simple('show', array($this->treeview, 'grab_focus'));

		$config = Config::instance();
		$this->date = isset($config->date) ? (string) $config->date : 'Y-m-d H:i:s';
		$this->timeout = isset($config->timeout) ? (int) $config->timeout : 60000;

		$this->editwindow = new CharacterEdit();
		file_put_contents('C:\work\writer\\' .__CLASS__ . '.' . __FUNCTION__ . '.txt', print_r(get_defined_vars(), TRUE));
	}

	/**
	 * public function buildTreeview
	 *
	 * registers callbacks and sets up model
	 *
	 * @return void
	 */
	protected function buildTreeview()
	{
		$treeview = $this->treeview = new GtkTreeview();

		$treeview->set_rules_hint(TRUE);
		$cols = array('#' => 2, 'Name' => 1, 'Created' => 3, 'Edited' => 4);
		foreach($cols as $name => $id)
		{
			$render = new GtkCellRendererText();
			$col = new GtkTreeViewColumn(Writer::i18n($name), $render, 'text', $id, 'foreground', 5);
			$col->focus_cell($render);
			if($id == 1)
			{
				$col->set_expand(TRUE);
			}
			elseif($id == 3 || $id == 4)
			{
				$col->set_cell_data_func($render, array($this, 'dateFormat'));
			}
			$col->set_resizable(TRUE);
			$col->set_sort_column_id($id);
			$treeview->append_column($col);
		}
		$model = $this->buildModel();
		$model->set_default_sort_func(array($this, 'defaultSort'));
		$treeview->set_model($model);
		$treeview->set_search_column(1);
		$selection = $treeview->get_selection();
		$selection->set_mode(Gtk::SELECTION_BROWSE);
		$first = $treeview->get_model()->get_iter_first();
		if(!is_null($first))
		{
			$selection->select_iter($first);
		}
		$selection->connect('changed', array($this, 'onSelectionChanged'));

		// Popup menu
		$menu = $treeview->menu = new GtkMenu();
		$group = $this->actions['file'];
		$tooltips = Tooltips::instance();

		$action = $group->get_action('open');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$menu->append($item);

		$action = $group->get_action('delete');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$menu->append($item);

		$action = $group->get_action('undelete');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$menu->append($item);

		$menu->show_all();

		$treeview->set_events($treeview->get_events() | Gdk::BUTTON_PRESS_MASK);
		$treeview->connect('button-press-event', array($this, 'doPopup'), $menu);

		// double click on open
		$treeview->connect('row-activated', array($this, 'onOpen'));

		return;
	}

	/**
	 * public function buildModel
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function buildModel()
	{
		$list = CharacterDao::find();
		$count = CharacterDao::countMeta();
		$types = array(Gtk::TYPE_LONG, Gtk::TYPE_STRING, Gtk::TYPE_LONG,
			Gtk::TYPE_STRING, Gtk::TYPE_STRING, Gtk::TYPE_STRING,
			Gtk::TYPE_BOOLEAN, GTK::TYPE_BOOLEAN);
		while($count > 0)
		{
			$types[] = Gtk::TYPE_PHP_VALUE;
			$count--;
		}
		$model = new GtkListStore();
		call_user_func_array(array($model, '__construct'), $types);
		foreach($list as $object)
		{
			$model->append($object->storeArray());
		}
		return $model;
	}

	/**
	 * protected function buildActions
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	protected function buildActions()
	{
		parent::buildActions('character');
		// open is no longer ellipsized and has new tooltip
		$action = $this->actions['file']->get_action('open');
		$action->set_property('label', Writer::i18n('Open'));
		$action->set_property('tooltip', Writer::i18n('Open selected character'));
		// tooltip alteration
		$this->actions['file']->get_action('revert')->set_property('tooltip', Writer::i18n('Revert all character changes'));
		$this->actions['file']->get_action('close')->set_property('tooltip', Writer::i18n('Close character window'));
		$this->actions['file']->get_action('print')->set_property('tooltip', Writer::i18n('Print character list'));
		$this->actions['file']->get_action('save')->set_property('tooltip', Writer::i18n('Save all character changes'));
		$this->isChanged(FALSE);
		return;
	}

	/**
	 * public function buildMenu
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function buildMenu()
	{
		$menu = $this->menu = new GtkMenuBar();
		$tooltips = Tooltips::instance();
		$config = Config::instance();
		$name = $this->name;

		$group = $this->actions['file'];
		$item = new GtkMenuItem(Writer::i18n('_File'));
		$menu->add($item);
		$submenu = new GtkMenu();
		$item->set_submenu($submenu);

		$action = $group->get_action('new');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$action = $group->get_action('open');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$action = $group->get_action('delete');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$action = $group->get_action('undelete');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$submenu->append(new GtkSeparatorMenuItem());

		$action = $group->get_action('save');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$action = $group->get_action('revert');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$action = $group->get_action('print');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$submenu->append(new GtkSeparatorMenuItem());

		$action = $group->get_action('close');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$group = $this->actions['manage'];
		$item = new GtkMenuItem(Writer::i18n('_Manage'));
		$menu->add($item);
		$submenu = new GtkMenu();
		$item->set_submenu($submenu);

		$action = $group->get_action('wizard');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$action = $group->get_action('import');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$action = $group->get_action('export');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$group = $this->actions['tools'];
		$item = new GtkMenuItem(Writer::i18n('_Tools'));
		$menu->add($item);
		$submenu = new GtkMenu();
		$item->set_submenu($submenu);

		$action = $this->actions['toolbar']->get_action('toggle');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$submenu->append(new GtkSeparatorMenuItem());

		$action = $group->get_action('preferences');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		$action = $this->actions['toolbar']->get_action('customize');
		$item = $action->create_menu_item();
		$tooltips->set_tip($item, $action->get_property('tooltip'));
		$submenu->append($item);

		parent::buildMenu();

		return;
	}

	//----------------------------------------------------------------
	//             Callbacks
	//----------------------------------------------------------------

	/**
	 * public function parent
	 *
	 * if this is not the top window and has a parent, we link destroy to hide
	 *
	 * @param object instanceof GtkWindow $parent GtkWindow object parent
	 * @return void
	 */
	public function parent()
	{
		$this->set_transient_for(Writer::$window);
		$this->connect_simple('delete-event', array($this, 'onClose'));
		Writer::$window->connect_simple('destroy', array($this, 'onDeleteEvent'));
		return;
	}

	/**
	 * public function dateFormat
	 *
	 * callback to format the date in a cell
	 *
	 * @return void
	 */
	public function dateFormat($render, $cell)
	{
		$cell->set_property('text', date($this->date, strtotime($cell->get_property('text'))));
		return;
	}

	/**
	 * public function onNew
	 *
	 * inserts a new blank row for a character
	 *
	 * @return void
	 */
	public function onNew()
	{
		$dialog = new GtkDialog (Writer::i18n('Choose name'),
		$this, Gtk::DIALOG_MODAL | Gtk::DIALOG_DESTROY_WITH_PARENT | Gtk::DIALOG_NO_SEPARATOR,
		array(Gtk::STOCK_OK, Gtk::RESPONSE_OK));
		$dialog->set_default_response(Gtk::RESPONSE_OK);
		$dialog->vbox->add(new GtkLabel(Writer::i18n('Choose a unique character name')));
		$dialog->vbox->add($entry = new GtkEntry());
		$dialog->vbox->add($error = new GtkLabel());
		$error->set_use_markup(TRUE);
		$dialog->vbox->set_border_width(10);
		$dialog->show_all();
		$error->hide();
		// evil naughty bad loop will run forever
		while(1)
		{
			$check = $dialog->run();
			if($check == Gtk::RESPONSE_OK)
			{
				$name = $entry->get_text();
				if(empty($name))
				{
					$error->set_label(Writer::i18n('<span color="#CC0000">Character name cannot be empty</span>'));
					$error->show();
				}
				elseif($this->checkName($name) == TRUE)
				{
					$error->set_label(Writer::i18n('<span color="#CC0000">Character name must be unique</span>'));
					$error->show();
				}
				else
				{
					break;
				}
			}
			elseif($check == Gtk::RESPONSE_DELETE_EVENT)
			{
				$dialog->destroy();
				return;
			}
		}
		$dialog->destroy();
		$model = $this->treeview->get_model();
		$date = date('Y-m-d H:i:s');
		$iter = $model->append();
		$model->set($iter, 1, $name, 2, $model->iter_n_children(NULL), 3, $date,
			4, $date, 5, '#009900', 6, FALSE);
		$this->timeouts[$model->get_string_from_iter($iter)] = Gtk::timeout_add($this->timeout, array($this, 'onStale'), $iter);
		$this->treeview->get_selection()->select_iter($iter);
		$this->treeview->scroll_to_cell($model->get_path($iter));
		$this->isChanged();
		if(!is_null($this->statusbar))
		{
			$this->statusbar->label->set_label(Writer::i18n('<b>New character added</b>'));
		}
		return;
	}

	/**
	 * public function onSelectionChanged
	 *
	 * decides what actions can be taken
	 *
	 * @return void
	 */
	public function onSelectionChanged($selection)
	{
		list($model, $iter) = $selection->get_selected();
		if(is_null($iter))
		{
			return;
		}
		// manipulate delete/undelete
		$deleted = $model->get_value($iter, 7);
		if($deleted == TRUE)
		{
			$this->actions['file']->get_action('open')->set_sensitive(FALSE);
			$this->actions['file']->get_action('delete')->set_sensitive(FALSE);
			$this->actions['file']->get_action('undelete')->set_sensitive(TRUE);
		}
		else
		{
			$this->actions['file']->get_action('open')->set_sensitive(TRUE);
			$this->actions['file']->get_action('delete')->set_sensitive(TRUE);
			$this->actions['file']->get_action('undelete')->set_sensitive(FALSE);
		}
		return;
	}

	/**
	 * public function isChanged
	 *
	 * call this if we change a character at all
	 *
	 * @return void
	 */
	public function isChanged($toggle = TRUE)
	{
		$this->actions['file']->get_action('save')->set_sensitive($toggle);
		$this->actions['file']->get_action('revert')->set_sensitive($toggle);
		return;
	}

	/**
	* public function onStale
	*
	* changes font color for a row from green to blue as new data
	* becomes stale
	*
	* @param object $iter instanceof GtkTreeIter
	* @return void
	*/
	public function onStale($iter)
	{
		$this->treeview->get_model()->set($iter, 5, '#000099');
		return;
	}

	/**
	 * public function onRevert
	 *
	 * clear rows and reload from db
	 *
	 * @return void
	 */
	public function onRevert()
	{
		$model = $this->treeview->get_model();
		$model->clear();
		$list = CharacterDao::find();
		foreach($list as $object)
		{
			$model->append($object->storeArray());
		}
		$this->treeview->get_selection()->select_iter($model->get_iter_first());
		foreach($this->timeouts as $id)
		{
			Gtk::timeout_remove($id);
		}
		$this->timeouts = array();
		if(!is_null($this->statusbar))
		{
			$this->statusbar->label->set_label(Writer::i18n('<b>Character changes reverted</b>'));
		}
		$this->isChanged(FALSE);
		return;
	}

	/**
	 * public function onDelete
	 *
	 * tags item for deletion (is not deleted until save!)
	 *
	 * @todo an "are you sure" dialog
	 * @return void
	 */
	public function onDelete()
	{
		$selection = $this->treeview->get_selection();
		list($model, $iter) = $selection->get_selected();
		$next = $model->iter_next($iter);
		if(!is_null($next))
		{
			$selection->select_iter($next);
			$this->treeview->scroll_to_cell($model->get_path($next));
		}
		$model->set($iter, 5, '#990000', 7, TRUE);
		$id = $model->get_string_from_iter($iter);
		if(isset($this->timeouts[$id]))
		{
			Gtk::timeout_remove($this->timeouts[$id]);
		}
		$this->isChanged();
		return;
	}

	/**
	 * public function onDelete
	 *
	 * tags item for deletion (is not deleted until save!)
	 *
	 * @todo an "are you sure" dialog
	 * @return void
	 */
	public function onUndelete()
	{
		$selection = $this->treeview->get_selection();
		list($model, $iter) = $selection->get_selected();
		$next = $model->iter_next($iter);
		if(!is_null($next))
		{
			$selection->select_iter($next);
			$this->treeview->scroll_to_cell($model->get_path($next));
		}
		if($model->get_value($iter, 6) == TRUE)
		{
			$model->set($iter, 5, '#000000', 7, FALSE);
		}
		else
		{
			$model->set($iter, 5, '#009900', 7, FALSE);
			$this->timeouts[$model->get_string_from_iter($iter)] = Gtk::timeout_add($this->timeout, array($this, 'onStale'), $iter);
		}
		$this->isChanged();
		return;
	}

	/**
	 * public function removeTimeout
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function onClose()
	{
		//check for needing to save
		foreach($this->timeouts as $id)
		{
			Gtk::timeout_remove($id);
		}
		$this->hide_all();
		return TRUE;
	}

	/**
	 * public function onSave
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function onSave()
	{
		$model = $this->treeview->get_model();
		$list = array();
		$total = $model->get_n_columns();
		while($total > 0)
		{
			$list[] = --$total;
		}
		$list = array_reverse($list);
		$model->foreach(array($this, 'updateDb'), $list);
		$model->clear();
		$list = CharacterDao::find();
		foreach($list as $object)
		{
			$model->append($object->storeArray());
		}
		$first = $model->get_iter_first();
		if(!is_null($first))
		{
			$this->treeview->get_selection()->select_iter($first);
		}
		foreach($this->timeouts as $id)
		{
			Gtk::timeout_remove($id);
		}
		$this->timeouts = array();
		$this->isChanged(FALSE);

		if(!is_null($this->statusbar))
		{
			$this->statusbar->label->set_label(Writer::i18n('<b>Character changes saved</b>'));
		}
		return;
	}

	/**
	 * public function updateDb
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function updateDb($model, $path, $iter, $list)
	{
		array_unshift($list, $iter);
		$row = call_user_func_array(array($model, 'get'), $list);
		if($row[7] == TRUE)
		{
			if($row[6] == TRUE)
			{
				$obj = new CharacterDao($row[0]);
				$obj->delete();
			}
			$next = $model->iter_next($iter);
			while(!is_null($next))
			{
				$model->set($next, 2, ($model->get_value($next, 2) - 1));
				$next = $model->iter_next($next);
			}
		}
		else
		{
			$obj = new CharacterDao($row[0]);
			unset($row[5], $row[6], $row[7]);
			$i = 0;
			foreach($obj as $name => $value)
			{
				$obj->$name = $row[$i];
				$i++;
			}
			$obj->save();
		}
		return;
	}

	/**
	 * public function checkName
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function checkName($name)
	{
		$model = $this->treeview->get_model();
		$iter = $model->get_iter_first();
		while(!is_null($iter))
		{
			if($model->get_value($iter, 1) === $name)
			{
				return TRUE;
			}
			$iter = $model->iter_next($iter);
		}
		return FALSE;
	}

	/**
	 * public function onCharacters
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function onOpen()
	{
		$selection = $this->treeview->get_selection();
		list($model, $iter) = $selection->get_selected();
		if(is_null($iter))
		{
			return;
		}
		$this->editwindow->getData($model->get_value($iter, 0));
		$this->editwindow->show_all();
		return;
	}

	/**
	 * public function defaultSort
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function defaultSort($model, $iter1, $iter2)
	{
		$iter1 = $model->get_value($iter1, 3);
		$iter2 = $model->get_value($iter2, 3);
		if($iter1 > $iter2)
		{
			return 1;
		}
		elseif($iter1 < $iter2)
		{
			return -1;
		}
		else
		{
			return 0;
		}
	}
}
?>