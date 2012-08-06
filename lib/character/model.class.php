<?php
/**
 * model.class.php - uses the dao and implements interfaces
 *
 * custom data store for a gtktreeview
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: model.class.php 150 2007-06-13 02:07:50Z emsmith $
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   writer
 * @category     lib
 * @filesource
 */

/**
 * CharacterModel - would probably be smarter to implement this as a custom datastore
 *
 * I need a new release for a customdatastore though...sigh
 */
class CharacterModel extends GtkListStore
{



	/**
	 * public function __construct
	 *
	 * description
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function __construct()
	{
		
	}







	/**
	 * public function unDelete
	 *
	 * untags item for deletion (is not deleted until save!)
	 *
	 * @todo an "are you sure" dialog
	 * @return void
	 */
	public function onUndelete()
	{
		$selection = CharacterWindow::instance()->treeview->get_selection();
		list($model, $iter) = $selection->get_selected();
		$next = $this->iter_next($iter);
		if(!is_null($next))
		{
			$selection->select_iter($next);
			CharacterWindow::instance()->treeview->scroll_to_cell($this->get_path($next));
		}
		// if saved flag is toggled go black
		$data = $this->get($iter, 6);
		if($data[0] == TRUE)
		{
			$this->set($iter, 5, '#000000');
		}
		// otherwise we go blue
		else
		{
			$this->set($iter, 5, '#000099');
		}
		$this->set($iter, 7, FALSE);
		$this->isChanged();
		unset($data, $selection, $model, $iter);
		return;
	}



	/**
	 * public function onSave
	 *
	 * write changes to db
	 *
	 * @param type $name about
	 * @return type about
	 */
	public function onSave()
	{
		
	}

	/**
	 * public function onEdit
	 *
	 * open the edit window with selected character displayed
	 *
	 * @return void
	 */
	public function onEdit()
	{
		$selection = $this->treeview->get_selection();
		list($model, $iter) = $selection->get_selected();
		// load in character
		$dialog =  new CharacterEdit($model, $iter);
		// run and get response
		$response = $dialog->run();
		$dialog->destroy();
	}




}
?>