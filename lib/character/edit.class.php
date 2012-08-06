<?php
/**
 * edit.class.php - character window edit class
 *
 * Edit a single character - both meta and regular data
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: edit.class.php 150 2007-06-13 02:07:50Z emsmith $
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   writer
 * @category     lib
 * @filesource
 */

/**
 * CharacterEdit - character edit window
 *
 * Simple form with save and cancel options
 */
class CharacterEdit extends Window
{

	/**
	 * set character information
	 * @var $character type
	 */
	protected $character;

	/**
	 * public function __construct
	 *
	 * Create a new CharacterEdit instance and build internal gui items
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->connect('show', array($this, 'parent'));
		$this->toolbar->set_no_show_all(TRUE);
		$this->toolbar->hide();
		$this->menu->set_no_show_all(TRUE);
		$this->menu->hide();

		// frame for form
		$frame = new GtkFrame(Writer::i18n('Character Information'));
		$frame->set_shadow_type(Gtk::SHADOW_ETCHED_IN);
		$frame->set_border_width(5);
		$this->vbox->add($frame);
		// scroll for form
		$scroll = new GtkScrolledWindow();
		$scroll->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
		$scroll->set_shadow_type(Gtk::SHADOW_NONE);
		$frame->add($scroll);
		// viewport by hand so we can kill shadow...grrr
		$port = new GtkViewport();
		$port->set_shadow_type(Gtk::SHADOW_NONE);
		$scroll->add($port);
		// table into viewport
		$table = new GtkTable();
		$port->add($table);

		// basic items
		//$table->attach($label = new GtkLabel(Writer::i18n('Id:')), 0, 1, 0, 1, Gtk::FILL, Gtk::SHRINK);
		//$label->set_alignment(1, 0.5);
		//$table->attach($label = new GtkLabel($this->character->id), 1, 2, 0, 1, Gtk::FILL, Gtk::SHRINK);
		//$label->set_alignment(0, 0.5);
		//$table->attach($label =new GtkLabel(Writer::i18n('Name:')), 0, 1, 1, 2, Gtk::FILL, Gtk::SHRINK);
		//$label->set_alignment(1, 0.5);
		//$table->attach(new GtkEntry($this->character->name), 1, 2, 1, 2, Gtk::FILL, Gtk::SHRINK);
		//$table->attach($label =new GtkLabel(Writer::i18n('Created:')), 0, 1, 2, 3, Gtk::FILL, Gtk::SHRINK);
		//$label->set_alignment(1, 0.5);
		//$table->attach($label =new GtkLabel($this->character->date_created), 1, 2, 2, 3, Gtk::FILL, Gtk::SHRINK);
		//$label->set_alignment(0, 0.5);
		//$table->attach($label =new GtkLabel(Writer::i18n('Edited:')), 0, 1, 3, 4, Gtk::FILL, Gtk::SHRINK);
		//$label->set_alignment(1, 0.5);
		//$table->attach($label =new GtkLabel($this->character->date_edited), 1, 2, 3, 4, Gtk::FILL, Gtk::SHRINK);
		//$label->set_alignment(0, 0.5);

		unset($port, $table, $this, $frame, $scroll);
		return;




		$meta = CharacterDao::listMeta();
		print_r($meta);
		return;
	}

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
		$this->set_transient_for(Writer::$window->characters);
		$this->connect_simple('delete-event', array($this, 'onClose'));
		Writer::$window->connect_simple('destroy', array($this, 'onDeleteEvent'));
		unset($this);
		return;
	}

	/**
	 * public function getData
	 *
	 * if this is not the top window and has a parent, we link destroy to hide
	 *
	 * @param object instanceof GtkWindow $parent GtkWindow object parent
	 * @return void
	 */
	public function getData($id)
	{
		// retrieve row from db
		$row = new CharacterDao($id);
		$this->set_title($row->name, Writer::i18n('Edit Character'));
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
		$this->hide_all();
		return TRUE;
	}
}
?>