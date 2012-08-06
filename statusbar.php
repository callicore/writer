<?php
namespace Callicore;
class Statusbar extends GtkStatusbar {
	public function __construct() {
				$this->statusbar = new GtkStatusbar();
		if (empty($this->statusbar->name))
		{
			$this->statusbar->set_name($this->get_name());
		}
		// constructs a gtkframe with a gtklabel inside
		$children = $this->statusbar->get_children();
		$this->statusbar->frame = $children[0];
		$this->statusbar->label = $children[0]->child;
		$this->statusbar->label->set_padding(3, 0);
		$this->statusbar->label->set_label(CC::i18n('Ready'));
		$this->statusbar->label->set_use_markup(TRUE);
		return;
	}
}