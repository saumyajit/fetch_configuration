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
		// Initialize main menu (CMenu class instance)
		$mainMenu = APP::Component()->get('menu.main');
	
			// Find or add 'Reports' main menu
			$reportsMenu = $mainMenu->findOrAdd(_('Reports'));
	
				// Create parent menu 'Host Configuration (RO)'
				$hostConfigMenu = new \CMenuItem(_('Host Configuration (RO)'));
	
					// Add sub-menu items to the parent
					$hostConfigMenu->getSubmenu()
						->add((new \CMenuItem(_('Host View')))
							->setAction('gethostro.view'))
						->add((new \CMenuItem(_('Group View')))
							->setAction('getgroupro.view'));
				
		// Insert the parent menu under 'Reports' after 'Top 100 Noisy Alerts'
		$reportsMenu->getSubmenu()
			->insertAfter(_('Top 100 Noisy Alerts'), $hostConfigMenu);
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
