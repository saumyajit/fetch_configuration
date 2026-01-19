<?php declare(strict_types = 1);
 
namespace Modules\HostConfig;
 
use APP;
use CController as CAction;
use Zabbix\Core\CModule;
 
/**
 * Please see Core\CModule class for additional reference.
 */
class Module extends CModule {
 
	/**
	 * Initialize module.
	 */
	public function init(): void {
		// Initialize main menu (CMenu class instance).
		APP::Component()->get('menu.main')
			->findOrAdd(_('Reports'))
				->getSubmenu()
					->insertAfter(_('Top 100 Noisy Alerts'),((new \CMenuItem(_('Host Configuration (RO)')))
						->setAction('gethostro.view'))
					);
	}
 
	/**
	 * Event handler, triggered before executing the action.
	 *
	 * @param CAction $action  Action instance responsible for current request.
	 */
	public function onBeforeAction(CAction $action): void {
	}
 
	/**
	 * Event handler, triggered on application exit.
	 *
	 * @param CAction $action  Action instance responsible for current request.
	 */
	public function onTerminate(CAction $action): void {
	}
}
