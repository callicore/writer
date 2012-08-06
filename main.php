<?php
class CC_Main extends CC_Window {

	/**
	 * main vbox for window
	 * @var $vbox object instanceof GtkVBox
	 */
	private $vbox;

	/**
	 * main menu for the window
	 * @var $menu object instanceof GtkMenu
	 */
	private $menu;

	/**
	 * toolbar for the window
	 * @var $toolbar object instanceof CC_Toolbar
	 */
	private $toolbar;

	/**
	 * status bar for window
	 * @var $statusbar object instanceof GtkStatusBar
	 */
	private $statusbar;

	public function __construct($config)
	{

		parent::__construct($config);
		$this->set_title('Callicore Writer v.' . CC_Writer::VERSION);

		// main vbox
		$this->vbox = new GtkVBox();
		$this->add($this->vbox);

		//$this->register_actions();
		//$this->build_toolbar();
		//$this->build_menu();
		$this->statusbar = new CC_Statusbar();

		//$this->vbox->pack_start($this->menu, false, false);
		//$this->vbox->pack_start($this->toolbar, false, false);
		$this->vbox->pack_end($this->statusbar, false, false);

		$this->connect_simple('destroy',array('gtk','main_quit'));
		$this->connect_simple('delete-event', array($this, 'on_quit'));
		return;
	}

	/**
	 * public function on_quit
	 *
	 * exits the program
	 *
	 * @return void
	 */
	public function on_quit()
	{
		$dialog = new CC_Message('Are you sure you want to quit?'
			. PHP_EOL . 'All unsaved changes will be lost.', 'Leaving Callicore Writer',
			CC_Message::QUESTION);
		if ($dialog->run() == Gtk::RESPONSE_YES) {
			$this->on_state_save();
			$this->destroy();
			return false; // stop event propogation
		} else {
			$dialog->destroy();
			return true;
		}
	}
}
?>