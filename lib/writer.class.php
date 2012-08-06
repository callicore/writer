<?php
/**
 * writer.class.php - Main window for the writer program
 *
 * main window for the application, opens up last used project or creates a new
 * blank project if no "last" is available to use
 *
 * This is released under the GPL, see docs/gpl.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/desktop/programs/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: writer.class.php 146 2007-06-11 13:19:18Z emsmith $
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