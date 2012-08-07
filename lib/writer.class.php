<?php
/**
 * writer.class.php - Main window for the writer program
 *
 * main window for the application, opens up last used project or creates a new
 * blank project if no "last" is available to use
 *
 * This is released under the GPL, see docs/gpl.txt for details
 *
 * @author       Elizabeth M Smith <emsmith@callicore.net>
 * @copyright    Elizabeth M Smith (c)2006
 * @link         http://callicore.net/desktop/programs/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: writer.class.php 150 2007-06-13 02:07:50Z emsmith $
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   writer
 * @category     lib
 * @filesource
 */

/**
 * CC_Writer - checks settings and manages common properties
 *
 * Basically a wrapper class for the application
 */
class CC_Writer extends CC_Main
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
		parent::__construct();
		
		//var_dump($this->window);
		$action = CC_Actions::instance();
		$quit = $action->get_action('help', 'website');
		$new = $quit->create_menu_item();
		$image = $new->get_children();
		$image = $image[1];
		echo $image;
		$image->reparent($this->vbox);
		$icon = $quit->create_icon(CC::$DND);
		$this->vbox->add($icon);
		$quit = $action->get_action('file', 'quit');
		$icon = $quit->create_icon(CC::$DND);
		$this->vbox->add($icon);
		$this->show_all();
	}

	public function on_about()
	{
	}
	public function on_help()
	{
	}
}
?>