<?php
namespace show\system\event\listener;
use show\data\entry\AccessibleEntryList;
use wcf\data\search\extended\SearchExtendedGroup;
use wcf\data\search\extended\SearchExtendedItem;
use wcf\system\application\ApplicationHandler;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Extended Search for Display Window entries.
 *
 * @author		2019-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.show.extendedSearch
 */
class ExtendedSearchShowListener implements IParameterizedEventListener {
	private $eventObj;
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$this->eventObj = $eventObj;
		
		if (EXTENDED_SEARCH_SHOW_ENABLED && in_array($this->eventObj->getSearchType(), ['everywhere', 'com.uz.show.entry'])) {
			$eventObj->data[] = $this->getEntries();
		}
	}
	
	/**
	 * Returns the Display Window entry list
	 */
	private function getEntries() {
		$items = [];
		$search = $this->eventObj->getSearchString(EXTENDED_SEARCH_SEARCH_TYPE);
		$showList = new AccessibleEntryList();
		$showList->getConditionBuilder()->add('(entry.subject LIKE ? OR entry.message LIKE ? OR entry.location LIKE ?)', [$search, $search, $search]);
		$showList->getConditionBuilder()->add('entry.isDisabled = ?', [0]);
		$showList->sqlOrderBy = 'entry.views DESC';
		$showList->sqlLimit = EXTENDED_SEARCH_SHOW_COUNT;
		$showList->readObjects();
		
		foreach ($showList->getObjects() as $show) {
			$items[] = new SearchExtendedItem($show->getTitle(), $show->getLink(), $show->views, StringUtil::stripHTML($show->getSimplifiedFormattedMessage()));
		}
		
		// display on top if active
		$activeApplicationAbbr = ApplicationHandler::getInstance()->getActiveApplication()->getAbbreviation();
		return new SearchExtendedGroup(WCF::getLanguage()->get('wcf.extendedSearch.group.show'), $items, SearchExtendedGroup::POSITION_RIGHT, ($activeApplicationAbbr === 'show' ? 1 : 20));
	}
}
