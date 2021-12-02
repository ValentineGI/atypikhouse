<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

// import Joomla view library
jimport('joomla.application.component.view');

class VikBookingViewTrackings extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		// require the tracker class
		VikBooking::getTracker(true);
		//

		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$lim = $mainframe->getUserStateFromRequest("com_vikbooking.limit", 'limit', $mainframe->get('list_limit'), 'int');
		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
		$session = JFactory::getSession();
		$pvborderby = VikRequest::getString('vborderby', '', 'request');
		$pvbordersort = VikRequest::getString('vbordersort', '', 'request');
		$validorderby = array('id', 'dt', 'lastdt', 'published', 'country', 'geo');
		$orderby = $session->get('vbViewTrackingsOrderby', 'lastdt');
		$ordersort = $session->get('vbViewTrackingsOrdersort', 'DESC');
		if (!empty($pvborderby) && in_array($pvborderby, $validorderby)) {
			$orderby = $pvborderby;
			$session->set('vbViewTrackingsOrderby', $orderby);
			if (!empty($pvbordersort) && in_array($pvbordersort, array('ASC', 'DESC'))) {
				$ordersort = $pvbordersort;
				$session->set('vbViewTrackingsOrdersort', $ordersort);
			}
		}

		// get min and max dates for filters
		$minmaxvals = array(
			'mintrackingdt' => 0,
			'maxtrackingdt' => 0,
			'mincheckin' 	=> 0,
			'maxcheckin' 	=> 0,
			'mincheckout' 	=> 0,
			'maxcheckout' 	=> 0,
		);
		$q = "SELECT MIN(`trackingdt`) AS `mintrackingdt`, MAX(`trackingdt`) AS `maxtrackingdt`, MIN(`checkin`) AS `mincheckin`, MAX(`checkin`) AS `maxcheckin`, MIN(`checkout`) AS `mincheckout`, MAX(`checkout`) AS `maxcheckout` 
			FROM `#__vikbooking_tracking_infos`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$data = $dbo->loadAssoc();
			$minmaxvals = array(
				'mintrackingdt' => (!empty($data['mintrackingdt']) ? strtotime($data['mintrackingdt']) : 0),
				'maxtrackingdt' => (!empty($data['maxtrackingdt']) ? strtotime($data['maxtrackingdt']) : 0),
				'mincheckin' 	=> (!empty($data['mincheckin']) ? strtotime($data['mincheckin']) : 0),
				'maxcheckin' 	=> (!empty($data['maxcheckin']) ? strtotime($data['maxcheckin']) : 0),
				'mincheckout' 	=> (!empty($data['mincheckout']) ? strtotime($data['mincheckout']) : 0),
				'maxcheckout' 	=> (!empty($data['maxcheckout']) ? strtotime($data['maxcheckout']) : 0),
			);
		}
		$mindate = min($minmaxvals);
		$maxdate = max($minmaxvals);

		// get all tracked countries for filters
		$countries = array();
		$q = "SELECT DISTINCT `t`.`country`,`c`.`country_name` FROM `#__vikbooking_trackings` AS `t` LEFT JOIN `#__vikbooking_countries` AS `c` ON `t`.`country`=`c`.`country_3_code` ORDER BY `c`.`country_name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$countries = $dbo->loadAssocList();
			// unset NULL values
			foreach ($countries as $k => $v) {
				if (empty($v['country'])) {
					unset($countries[$k]);
				}
			}
		}

		// get all tracked referrers for filters
		$referrers = array();
		$q = "SELECT DISTINCT `referrer` FROM `#__vikbooking_tracking_infos` ORDER BY `referrer` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$referrers = $dbo->loadAssocList();
			// unset NULL values
			foreach ($referrers as $k => $v) {
				if (empty($v['referrer'])) {
					unset($referrers[$k]);
				}
			}
		}
		
		// query filters
		$filters = array();

		// date filter
		$nowdf = VikBooking::getDateFormat(true);
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$now_info = getdate();
		$def_dt_from = date($df, mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] - 7), $now_info['year']));
		$def_dt_to = date($df);
		$pdatefilt = $mainframe->getUserStateFromRequest("vbo.trackings.datefilt", 'datefilt', 1, 'int');
		$pdatefiltfrom = $mainframe->getUserStateFromRequest("vbo.trackings.datefiltfrom", 'datefiltfrom', $def_dt_from, 'string');
		$pdatefiltto = $mainframe->getUserStateFromRequest("vbo.trackings.datefiltto", 'datefiltto', $def_dt_to, 'string');
		if ((!empty($pdatefiltfrom) || !empty($pdatefiltto))) {
			if ($pdatefilt == 1) {
				// tracking dates
				if (!empty($pdatefiltfrom)) {
					array_push($filters, '`t`.`lastdt` >= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltfrom, 0, 0)))->toSql()));
				}
				if (!empty($pdatefiltto)) {
					array_push($filters, '`t`.`lastdt` <= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltto, 23, 59, 59)))->toSql()));
				}
			} elseif ($pdatefilt == 2) {
				// booking dates
				$bookdatesfilt = array();
				if (!empty($pdatefiltfrom)) {
					// filter from-date inside a range of dates booked (we use 23:59:59 to avoid calculating the check-in time)
					array_push(
						$bookdatesfilt, 
						'(' . 
							'`i`.`checkin` <= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltfrom, 23, 59, 59)))->toSql()) . 
							' AND ' . 
							'`i`.`checkout` >= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltfrom, 0, 0, 0)))->toSql()) . 
						')'
					);
				}
				if (!empty($pdatefiltto)) {
					// filter to-date inside a range of dates booked (we use 00:00:00 to avoid calculating the check-out time)
					array_push(
						$bookdatesfilt, 
						'(' . 
							'`i`.`checkin` <= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltto, 23, 59, 59)))->toSql()) . 
							' AND ' . 
							'`i`.`checkout` >= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltto, 0, 0, 0)))->toSql()) . 
						')'
					);
				}
				if (!empty($pdatefiltfrom) && !empty($pdatefiltto)) {
					// filter dates including booking dates (bigger than)
					array_push(
						$bookdatesfilt, 
						'(' . 
							'`i`.`checkin` >= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltfrom, 0, 0)))->toSql()) . 
							' AND ' . 
							'`i`.`checkout` <= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltto, 23, 59, 59)))->toSql()) . 
						')'
					);
				}
				array_push($filters, '(' . implode(' OR ', $bookdatesfilt) . ')');
			} elseif ($pdatefilt == 3) {
				// checkin date
				if (!empty($pdatefiltfrom)) {
					array_push($filters, '`i`.`checkin` >= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltfrom, 0, 0)))->toSql()));
				}
				if (!empty($pdatefiltto)) {
					array_push($filters, '`i`.`checkin` <= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltto, 23, 59, 59)))->toSql()));
				}
			} elseif ($pdatefilt == 4) {
				// checkout date
				if (!empty($pdatefiltfrom)) {
					array_push($filters, '`i`.`checkout` >= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltfrom, 0, 0)))->toSql()));
				}
				if (!empty($pdatefiltto)) {
					array_push($filters, '`i`.`checkout` <= '.$dbo->quote(JDate::getInstance(date('Y-m-d H:i:s', VikBooking::getDateTimestamp($pdatefiltto, 23, 59, 59)))->toSql()));
				}
			}
		}

		// country filter
		$pcountryfilt = VikRequest::getString('countryfilt', '', 'request');
		if (!empty($pcountryfilt)) {
			array_push($filters, '`t`.`country` = '.$dbo->quote($pcountryfilt));
		}

		// referrer filter
		$preferrer = VikRequest::getString('referrer', '', 'request');
		if (!empty($preferrer)) {
			array_push($filters, '`i`.`referrer` = '.$dbo->quote($preferrer));
		}

		// calculate most demanded nights, conversion rates, best referrers
		$stats_data = array();
		$tomorrowdt = JDate::getInstance(date('Y-m-d', strtotime('tomorrow')))->toSql();
		$q = "SELECT `i`.`id`, `i`.`idtracking`, `i`.`identifier`, `i`.`checkin`, `i`.`checkout`, `i`.`idorder`, `i`.`referrer`, `t`.`lastdt`, `t`.`published` 
			FROM `#__vikbooking_tracking_infos` AS `i` 
			LEFT JOIN `#__vikbooking_trackings` AS `t` ON `i`.`idtracking`=`t`.`id` 
			WHERE `t`.`published`=1 AND ".(count($filters) ? implode(' AND ', $filters) : '`i`.`checkin` > '.$dbo->quote($tomorrowdt))." 
			ORDER BY `i`.`checkin` ASC, `i`.`id` DESC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$stats_data = $dbo->loadAssocList();
		}

		// build the records with pagination
		$rows = "";
		$navbut = "";
		// this query cannot be compatible with SQL ONLY_FULL_GROUP_BY as we always need to join `#__vikbooking_tracking_infos`
		$q = "SELECT SQL_CALC_FOUND_ROWS `t`.*,`c`.`country_name`,`cu`.`first_name`,`cu`.`last_name`,`cu`.`country` AS `c_country` ".
			"FROM `#__vikbooking_trackings` AS `t` ".
			"LEFT JOIN `#__vikbooking_tracking_infos` AS `i` ON `t`.`id`=`i`.`idtracking` ".
			"LEFT JOIN `#__vikbooking_countries` AS `c` ON `t`.`country`=`c`.`country_3_code` ".
			"LEFT JOIN `#__vikbooking_customers` AS `cu` ON `t`.`idcustomer`=`cu`.`id` ".
			(count($filters) ? 'WHERE '.implode(' AND ', $filters).' ' : '').
			"GROUP BY `t`.`id` ".
			"ORDER BY `t`.`".$orderby."` ".$ordersort;
		$dbo->setQuery($q, $lim0, $lim);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$rows = $dbo->loadAssocList();
			$dbo->setQuery('SELECT FOUND_ROWS();');
			jimport('joomla.html.pagination');
			$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
			$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";

			// load tracking infos
			foreach ($rows as $k => $v) {
				$infos = array();
				$q = "SELECT `i`.*,`o`.`status` 
					FROM `#__vikbooking_tracking_infos` AS `i` 
					LEFT JOIN `#__vikbooking_orders` AS `o` ON `i`.`idorder`=`o`.`id` 
					WHERE `i`.`idtracking`=".(int)$v['id']." ORDER BY `i`.`identifier` DESC, `i`.`id` ASC;";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows()) {
					$infos = $dbo->loadAssocList();
				}
				$rows[$k]['infos'] = $infos;
			}
		}

		// load all rooms
		$rooms = array();
		$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$all_rooms = $dbo->loadAssocList();
			foreach ($all_rooms as $v) {
				$rooms[$v['id']] = $v['name'];
			}
		}

		// load all prices
		$prices = array();
		$q = "SELECT `id`,`name` FROM `#__vikbooking_prices`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$all_prices = $dbo->loadAssocList();
			foreach ($all_prices as $v) {
				$prices[$v['id']] = $v['name'];
			}
		}
		
		$this->rows = &$rows;
		$this->stats_data = &$stats_data;
		$this->rooms = &$rooms;
		$this->prices = &$prices;
		$this->mindate = &$mindate;
		$this->maxdate = &$maxdate;
		$this->countries = &$countries;
		$this->referrers = &$referrers;
		$this->lim0 = &$lim0;
		$this->navbut = &$navbut;
		$this->orderby = &$orderby;
		$this->ordersort = &$ordersort;
		$this->datefilt = &$pdatefilt;
		$this->datefiltfrom = &$pdatefiltfrom;
		$this->datefiltto = &$pdatefiltto;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		JToolBarHelper::title(JText::translate('VBMAINTRACKINGSTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			JToolBarHelper::deleteList(JText::translate('VBDELCONFIRM'), 'removetrackings', JText::translate('VBMAINCRONDEL'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::custom('modtracking', 'refresh', 'refresh', JText::translate('VBTRKTGLPUBLISHED'), true, false);
			JToolBarHelper::spacer();
		}
		JToolBarHelper::cancel( 'cancel', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}

}
