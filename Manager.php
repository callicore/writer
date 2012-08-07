<?php
/**
 * Manager.php - \Callicore\Writer\Manager actual application class
 *
 * This is released under the MIT, see license.txt for details
 *
 * @author       Elizabeth M Smith <auroraeosrose@php.net>
 * @copyright    Elizabeth M Smith (c)2009
 * @link         http://callicore.net
 * @license      http://www.opensource.org/licenses/mit-license.php MIT
 * @version      $Id: Client.php 21 2009-04-26 01:18:00Z auroraeosrose $
 * @since        Php 5.3.0
 * @package      callicore
 * @subpackage   lib
 * @filesource
 */

/**
 * Namespace for application
 */
namespace Callicore\Writer;
use \Callicore\Lib\Application; // this should have been loaded in the bootstrap

/**
 * actual application class, handles startup, shutdown, etc
 */
class Manager extends Application {

    /**
     * Our application name, overrides Application default
     *
     * @var string
     */
    protected $name = 'Writer';

    /**
     * Startup method for the twitter application
     *
     * @return void
     */
    public function main(){
        $window = new Main();
        $window->show_all();
    }
}
