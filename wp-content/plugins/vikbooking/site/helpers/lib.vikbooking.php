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

if (!defined('VBO_ADMIN_URI')) {
	//this library could be loaded by modules or VCM, so we need to load at least the Defines Adapter file.
	include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "adapter" . DIRECTORY_SEPARATOR . "defines.php");
}

if (!class_exists('VikRequest')) {
	// this library could be loaded by modules, so we need to load the Request Adapter file.
	include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'adapter' . DIRECTORY_SEPARATOR . 'request.php');
}

if (!class_exists('VikBookingIcons')) {
	// require the Icons class
	require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "icons.php");
}

if (!function_exists('showSelectVb')) {
	function showSelectVb($err, $err_code_info = array()) {
		include(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'error_form.php');
	}
}

class VikBooking {
	
	public static function addJoomlaUser($name, $username, $email, $password) {
		//new method
		jimport('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_users');
		$user = new JUser;
		$data = array();
		//Get the default new user group, Registered if not specified.
		$system = $params->get('new_usertype', 2);
		$data['groups'] = array();
		$data['groups'][] = $system;
		$data['name'] = $name;
		$data['username'] = $username;
		$data['email'] = self::getVboApplication()->emailToPunycode($email);
		$data['password'] = $password;
		$data['password2'] = $password;
		$data['sendEmail'] = 0; //should the user receive system mails?
		//$data['block'] = 0;
		if (!$user->bind($data)) {
			VikError::raiseWarning('', JText::translate($user->getError()));
			return false;
		}
		if (!$user->save()) {
			VikError::raiseWarning('', JText::translate($user->getError()));
			return false;
		}
		return $user->id;
	}
	
	public static function userIsLogged () {
		$user = JFactory::getUser();
		if ($user->guest) {
			return false;
		} else {
			return true;
		}
	}

	public static function prepareViewContent() {
		/**
		 * @wponly  JApplication::getMenu() cannot be adjusted to WP
		 */
		/*
		$menu = JFactory::getApplication()->getMenu()->getActive();
		//Joomla 3.7.x - property params is now protected, before it was public
		$menuParams = null;
		if (method_exists($menu, 'getParams')) {
			$menuParams = $menu->getParams();
		} elseif (isset($menu->params)) {
			//Until Joomla 3.6.5
			$menuParams = $menu->params;
		}
		//
		if ($menuParams !== null) {
			$document = JFactory::getDocument();
			if ( intval($menuParams->get('show_page_heading')) == 1 && strlen($menuParams->get('page_heading')) ) {
				echo '<div class="page-header'.(strlen($clazz = $menuParams->get('pageclass_sfx')) ? ' '.$clazz : '' ).'"><h1>'.$menuParams->get('page_heading').'</h1></div>';
			}
			if ( strlen($menuParams->get('menu-meta_description')) ) {
				$document->setDescription($menuParams->get('menu-meta_description'));
			}
			if ( strlen($menuParams->get('menu-meta_keywords')) ) {
				$document->setMetadata('keywords', $menuParams->get('menu-meta_keywords'));
			}
			if ( strlen($menuParams->get('robots')) ) {
				$document->setMetadata('robots', $menuParams->get('robots'));
			}
		}
		*/
	}

	public static function isFontAwesomeEnabled($skipsession = false) {
		if (!$skipsession) {
			$session = JFactory::getSession();
			$s = $session->get('vbofaw', '');
			if (strlen($s)) {
				return ((int)$s == 1);
			}
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='usefa';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			if (!$skipsession) {
				$session->set('vbofaw', $s);
			}
			return ((int)$s == 1);
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('usefa', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$skipsession) {
			$session->set('vbofaw', '1');
		}
		return true;
	}

	public static function loadFontAwesome($force_load = false) {
		if (!self::isFontAwesomeEnabled() && !$force_load) {
			return false;
		}

		/**
		 * We let the class VikBookingIcons load the proper FontAwesome libraries.
		 * 
		 * @since 	1.11
		 */
		VikBookingIcons::loadAssets();

		return true;
	}

	/**
	 * Checks if modifications or cancellations via front-end are allowed.
	 * 0 = everything is Disabled.
	 * 1 = Disabled, with request message (default).
	 * 2 = Modification Enabled, Cancellation Disabled.
	 * 3 = Cancellation Enabled, Modification Disabled.
	 * 4 = everything is Enabled.
	 *
	 * @return 	int
	 */
	public static function getReservationModCanc() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='resmodcanc';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return (int)$s;
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('resmodcanc', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	public static function getReservationModCancMin() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='resmodcancmin';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return (int)$s;
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('resmodcancmin', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	public static function getDefaultDistinctiveFeatures() {
		$features = array();
		$features['VBODEFAULTDISTFEATUREONE'] = '';
		//Below is the default feature for 'Room Code'. One default feature is sufficient
		//$features['VBODEFAULTDISTFEATURETWO'] = '';
		return $features;
	}

	/**
	 * Given the room's parameters and index, tries to take the first distinctive feature.
	 * 
	 * @param 	mixed 	$rparams 	string to be decoded, or decoded array/object params
	 * @param 	int 	$rindex 	room index to look for, starting from 1.
	 * 
	 * @return 	mixed 	false on failure, array otherwise [feature name, feature value]
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function getRoomUnitDistinctiveFeature($rparams, $rindex) {
		$rindex = (int)$rindex;
		if ($rindex < 1 || empty($rparams)) {
			return false;
		}

		if (is_string($rparams)) {
			// decode params
			$rparams = self::getRoomParam('features', $rparams);
		}

		if (is_object($rparams)) {
			// typecast to array
			$rparams = (array)$rparams;
		}

		if (!is_array($rparams) || !count($rparams)) {
			return false;
		}

		if (isset($rparams['features'])) {
			$rparams = $rparams['features'];
		}

		$feature = array();
		foreach ($rparams as $param_index => $rfeatures) {
			if ((int)$param_index != $rindex || !is_array($rfeatures) || !count($rfeatures)) {
				continue;
			}
			foreach ($rfeatures as $featname => $featval) {
				if (empty($featval)) {
					continue;
				}
				// use the first distinctive feature
				$tn_featname = JText::translate($featname);
				if ($tn_featname == $featname) {
					// no translation was applied
					if (defined('ABSPATH')) {
						// try to apply a translation through Gettext even if we have to pass a variable
						$tn_featname = __($featname);
					} else {
						// convert the string to a hypothetical INI constant
						$ini_constant = str_replace(' ', '_', strtoupper($featname));
						$tn_featname = JText::translate($ini_constant);
						$tn_featname = $tn_featname == $ini_constant ? $featname : $tn_featname;
					}
				}
				// store values and break loop
				$feature = array($tn_featname, $featval);
				break;
			}
		}

		return count($feature) ? $feature : false;
	}

	public static function getRoomUnitNumsUnavailable($order, $idroom) {
		$dbo = JFactory::getDbo();
		$unavailable_indexes = array();
		$first = $order['checkin'];
		$second = $order['checkout'];
		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		$q = "SELECT `b`.`id`,`b`.`checkin`,`b`.`checkout`,`b`.`realback`,`ob`.`idorder`,`ob`.`idbusy`,`or`.`id` AS `or_id`,`or`.`idroom`,`or`.`roomindex`,`o`.`status` ".
			"FROM `#__vikbooking_busy` AS `b` ".
			"LEFT JOIN `#__vikbooking_ordersbusy` `ob` ON `ob`.`idbusy`=`b`.`id` ".
			"LEFT JOIN `#__vikbooking_ordersrooms` `or` ON `or`.`idorder`=`ob`.`idorder` AND `or`.`idorder`!=".(int)$order['id']." ".
			"LEFT JOIN `#__vikbooking_orders` `o` ON `o`.`id`=`or`.`idorder` AND `o`.`id`=`ob`.`idorder` AND `o`.`id`!=".(int)$order['id']." ".
			"WHERE `or`.`idroom`=".(int)$idroom." AND `b`.`checkout` > ".time()." AND `o`.`status`='confirmed' AND `ob`.`idorder`!=".(int)$order['id']." AND `ob`.`idorder` > 0;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$busy = $dbo->loadAssocList();
			foreach ($groupdays as $gday) {
				foreach ($busy as $bu) {
					if (empty($bu['roomindex']) || empty($bu['idorder'])) {
						continue;
					}
					if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {;
						$unavailable_indexes[$bu['or_id']] = $bu['roomindex'];
					} elseif (count($groupdays) == 2 && $gday == $groupdays[0]) {
						if ($groupdays[0] < $bu['checkin'] && $groupdays[0] < $bu['realback'] && $groupdays[1] > $bu['checkin'] && $groupdays[1] > $bu['realback']) {
							$unavailable_indexes[$bu['or_id']] = $bu['roomindex'];
						}
					}
				}
			}
		}

		return $unavailable_indexes;
	}

	public static function getRoomUnitNumsAvailable($order, $idroom) {
		$dbo = JFactory::getDbo();
		$unavailable_indexes = array();
		$available_indexes = array();
		$first = $order['checkin'];
		$second = $order['checkout'];
		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		$q = "SELECT `b`.`id`,`b`.`checkin`,`b`.`checkout`,`b`.`realback`,`ob`.`idorder`,`ob`.`idbusy`,`or`.`id` AS `or_id`,`or`.`idroom`,`or`.`roomindex`,`o`.`status` ".
			"FROM `#__vikbooking_busy` AS `b` ".
			"LEFT JOIN `#__vikbooking_ordersbusy` `ob` ON `ob`.`idbusy`=`b`.`id` ".
			"LEFT JOIN `#__vikbooking_ordersrooms` `or` ON `or`.`idorder`=`ob`.`idorder` AND `or`.`idorder`!=".(int)$order['id']." ".
			"LEFT JOIN `#__vikbooking_orders` `o` ON `o`.`id`=`or`.`idorder` AND `o`.`id`=`ob`.`idorder` AND `o`.`id`!=".(int)$order['id']." ".
			"WHERE `or`.`idroom`=".(int)$idroom." AND `b`.`checkout` > ".time()." AND `o`.`status`='confirmed' AND `ob`.`idorder`!=".(int)$order['id']." AND `ob`.`idorder` > 0;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$busy = $dbo->loadAssocList();
			foreach ($groupdays as $gday) {
				foreach ($busy as $bu) {
					if (empty($bu['roomindex']) || empty($bu['idorder'])) {
						continue;
					}
					if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
						$unavailable_indexes[$bu['or_id']] = $bu['roomindex'];
					} elseif (count($groupdays) == 2 && $gday == $groupdays[0]) {
						if ($groupdays[0] < $bu['checkin'] && $groupdays[0] < $bu['realback'] && $groupdays[1] > $bu['checkin'] && $groupdays[1] > $bu['realback']) {
							$unavailable_indexes[$bu['or_id']] = $bu['roomindex'];
						}
					}
				}
			}
		}
		$q = "SELECT `params` FROM `#__vikbooking_rooms` WHERE `id`=".(int)$idroom.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$room_params = $dbo->loadResult();
			$room_params_arr = json_decode($room_params, true);
			if (array_key_exists('features', $room_params_arr) && is_array($room_params_arr['features']) && count($room_params_arr['features'])) {
				foreach ($room_params_arr['features'] as $rind => $rfeatures) {
					if (in_array($rind, $unavailable_indexes)) {
						continue;
					}
					$available_indexes[] = $rind;
				}
			}
		}

		return $available_indexes;
	}
	
	/**
	 * Load the restrictions applying the given filters to the passed rooms.
	 * The ordering of the query SHOULD remain unchanged, because it's required
	 * to have it by ascending order of the ID. So the older records first.
	 * Even when no filters, we always exclude expired restrictions.
	 * 
	 * @param 		boolean 	$filters 		whether to apply filters
	 * @param 		array 		$rooms 			the list of rooms to filter
	 * 
	 * @return 		array 		the list of restrictions found or an empty array.
	 */
	public static function loadRestrictions($filters = true, $rooms = array()) {
		$restrictions = array();
		$dbo = JFactory::getDbo();
		$limts = strtotime(date('Y').'-'.date('m').'-'.date('d'));
		if (!$filters) {
			$q = "SELECT * FROM `#__vikbooking_restrictions` WHERE `dto` = 0 OR `dto` >= ".$limts.";";
		} else {
			if (count($rooms) == 0) {
				$q = "SELECT * FROM `#__vikbooking_restrictions` WHERE `allrooms` = 1 AND (`dto` = 0 OR `dto` >= ".$limts.");";
			} else {
				$clause = array();
				foreach ($rooms as $idr) {
					if (empty($idr)) continue;
					$clause[] = "`idrooms` LIKE '%-".intval($idr)."-%'";
				}
				if (count($clause) > 0) {
					$q = "SELECT * FROM `#__vikbooking_restrictions` WHERE (`dto` = 0 OR `dto` >= ".$limts.") AND (`allrooms` = 1 OR (`allrooms` = 0 AND (".implode(" OR ", $clause).")));";
				} else {
					$q = "SELECT * FROM `#__vikbooking_restrictions` WHERE `allrooms` = 1 AND (`dto` = 0 OR `dto` >= ".$limts.");";
				}
			}
		}
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$allrestrictions = $dbo->loadAssocList();
			foreach ($allrestrictions as $k => $res) {
				if (!empty($res['month'])) {
					$restrictions[$res['month']] = $res;
				} else {
					if (!isset($restrictions['range'])) {
						$restrictions['range'] = array();
					}
					$restrictions['range'][$k] = $res;
				}
			}
		}
		return $restrictions;
	}
	
	public static function globalRestrictions($restrictions) {
		$ret = array();
		if (count($restrictions) > 0) {
			foreach($restrictions as $kr => $rr) {
				if ($kr == 'range') {
					foreach ($rr as $kd => $dr) {
						if ($dr['allrooms'] == 1) {
							$ret['range'][$kd] = $restrictions[$kr][$kd];
						}
					}
				} else {
					if ($rr['allrooms'] == 1) {
						$ret[$kr] = $restrictions[$kr];
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * From the given restrictions, check-in, check-out and nights, looks for
	 * a restriction to be returned and applied over these stays.
	 * In order to give priority to newer Restriction IDs, the order of the
	 * $restrictions array should be ascending, so that the newer restrictions
	 * will overwrite the array with the last ID (more recent). Only for date ranges.
	 * Returns an array with the record of the restriction found. The loop is not
	 * broken when the first valid restriction is found to give priority to
	 * newer restriction IDs to overwrite older records.
	 * 
	 * @param 	int 	$first 			the unix timestamp for the check-in date
	 * @param 	int 	$second 		the unix timestamp for the check-out date
	 * @param 	int 	$daysdiff 		the number of nights of stay
	 * @param 	array 	$restrictions 	the list of restrictions loaded
	 * 
	 * @return 	array 	the restriction found, or an empty array.
	 */
	public static function parseSeasonRestrictions($first, $second, $daysdiff, $restrictions) {
		$season_restrictions = array();
		$restrcheckin = getdate($first);
		$restrcheckout = getdate($second);
		if (array_key_exists($restrcheckin['mon'], $restrictions)) {
			//restriction found for this month, checking:
			$season_restrictions['id'] = $restrictions[$restrcheckin['mon']]['id'];
			$season_restrictions['name'] = $restrictions[$restrcheckin['mon']]['name'];
			$season_restrictions['allowed'] = true; //set to false when these nights are not allowed
			if (strlen($restrictions[$restrcheckin['mon']]['wday']) > 0) {
				//Week Day Arrival Restriction
				$rvalidwdays = array($restrictions[$restrcheckin['mon']]['wday']);
				if (strlen($restrictions[$restrcheckin['mon']]['wdaytwo']) > 0) {
					$rvalidwdays[] = $restrictions[$restrcheckin['mon']]['wdaytwo'];
				}
				$season_restrictions['wdays'] = $rvalidwdays;
			} elseif (!empty($restrictions[$restrcheckin['mon']]['ctad']) || !empty($restrictions[$restrcheckin['mon']]['ctdd'])) {
				if (!empty($restrictions[$restrcheckin['mon']]['ctad'])) {
					$season_restrictions['cta'] = explode(',', $restrictions[$restrcheckin['mon']]['ctad']);
				}
				if (!empty($restrictions[$restrcheckin['mon']]['ctdd'])) {
					$season_restrictions['ctd'] = explode(',', $restrictions[$restrcheckin['mon']]['ctdd']);
				}
			}
			if (!empty($restrictions[$restrcheckin['mon']]['maxlos']) && $restrictions[$restrcheckin['mon']]['maxlos'] > 0 && $restrictions[$restrcheckin['mon']]['maxlos'] > $restrictions[$restrcheckin['mon']]['minlos']) {
				$season_restrictions['maxlos'] = $restrictions[$restrcheckin['mon']]['maxlos'];
				if ($daysdiff > $restrictions[$restrcheckin['mon']]['maxlos']) {
					$season_restrictions['allowed'] = false;
				}
			}
			if ($daysdiff < $restrictions[$restrcheckin['mon']]['minlos']) {
				$season_restrictions['allowed'] = false;
			}
			$season_restrictions['minlos'] = $restrictions[$restrcheckin['mon']]['minlos'];
		} elseif (array_key_exists('range', $restrictions)) {
			foreach($restrictions['range'] as $restr) {
				if ($restr['dfrom'] <= $first && $restr['dto'] >= $first) {
					//restriction found for this date range, checking:
					$season_restrictions['id'] = $restr['id'];
					$season_restrictions['name'] = $restr['name'];
					$season_restrictions['allowed'] = true; //set to false when these nights are not allowed
					if (strlen($restr['wday']) > 0) {
						//Week Day Arrival Restriction
						$rvalidwdays = array($restr['wday']);
						if (strlen($restr['wdaytwo']) > 0) {
							$rvalidwdays[] = $restr['wdaytwo'];
						}
						$season_restrictions['wdays'] = $rvalidwdays;
					} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
						if (!empty($restr['ctad'])) {
							$season_restrictions['cta'] = explode(',', $restr['ctad']);
						}
						if (!empty($restr['ctdd'])) {
							$season_restrictions['ctd'] = explode(',', $restr['ctdd']);
						}
					}
					if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
						$season_restrictions['maxlos'] = $restr['maxlos'];
						if ($daysdiff > $restr['maxlos']) {
							$season_restrictions['allowed'] = false;
						}
					}
					if ($daysdiff < $restr['minlos']) {
						$season_restrictions['allowed'] = false;
					}
					$season_restrictions['minlos'] = $restr['minlos'];
				}
			}
		}

		return $season_restrictions;
	}

	public static function compareSeasonRestrictionsNights($restrictions) {
		$base_compare = array();
		$base_nights = 0;
		foreach ($restrictions as $nights => $restr) {
			$base_compare = $restr;
			$base_nights = $nights;
			break;
		}
		foreach ($restrictions as $nights => $restr) {
			if ($nights == $base_nights) {
				continue;
			}
			$diff = array_diff($base_compare, $restr);
			if (count($diff) > 0 && array_key_exists('id', $diff)) {
				//return differences only if the Restriction ID is different: ignore allowed, wdays, minlos, maxlos.
				//only one Restriction per time should be applied to certain Season Dates but check just in case.
				return $diff;
			}
		}

		return array();
	}
	
	public static function roomRestrictions($roomid, $restrictions) {
		$ret = array();
		if (!empty($roomid) && count($restrictions) > 0) {
			foreach($restrictions as $kr => $rr) {
				if ($kr == 'range') {
					foreach ($rr as $kd => $dr) {
						if ($dr['allrooms'] == 0 && !empty($dr['idrooms'])) {
							$allrooms = explode(';', $dr['idrooms']);
							if (in_array('-'.$roomid.'-', $allrooms)) {
								$ret['range'][$kd] = $restrictions[$kr][$kd];
							}
						}
					}
				} else {
					if ($rr['allrooms'] == 0 && !empty($rr['idrooms'])) {
						$allrooms = explode(';', $rr['idrooms']);
						if (in_array('-'.$roomid.'-', $allrooms)) {
							$ret[$kr] = $restrictions[$kr];
						}
					}
				}
			}
		}
		return $ret;
	}
	
	public static function validateRoomRestriction($roomrestr, $restrcheckin, $restrcheckout, $daysdiff) {
		$restrictionerrmsg = '';
		$restrictions_affcount = 0;
		if (array_key_exists($restrcheckin['mon'], $roomrestr)) {
			//restriction found for this month, checking:
			$restrictions_affcount++;
			if (strlen($roomrestr[$restrcheckin['mon']]['wday']) > 0) {
				$rvalidwdays = array($roomrestr[$restrcheckin['mon']]['wday']);
				if (strlen($roomrestr[$restrcheckin['mon']]['wdaytwo']) > 0) {
					$rvalidwdays[] = $roomrestr[$restrcheckin['mon']]['wdaytwo'];
				}
				if (!in_array($restrcheckin['wday'], $rvalidwdays)) {
					$restrictionerrmsg = JText::sprintf('VBRESTRTIPWDAYARRIVAL', self::sayMonth($restrcheckin['mon']), self::sayWeekDay($roomrestr[$restrcheckin['mon']]['wday']).(strlen($roomrestr[$restrcheckin['mon']]['wdaytwo']) > 0 ? '/'.self::sayWeekDay($roomrestr[$restrcheckin['mon']]['wdaytwo']) : ''));
				} elseif ($roomrestr[$restrcheckin['mon']]['multiplyminlos'] == 1) {
					if (($daysdiff % $roomrestr[$restrcheckin['mon']]['minlos']) != 0) {
						$restrictionerrmsg = JText::sprintf('VBRESTRTIPMULTIPLYMINLOS', self::sayMonth($restrcheckin['mon']), $roomrestr[$restrcheckin['mon']]['minlos']);
					}
				}
				$comborestr = self::parseJsDrangeWdayCombo($roomrestr[$restrcheckin['mon']]);
				if (count($comborestr) > 0) {
					if (array_key_exists($restrcheckin['wday'], $comborestr)) {
						if (!in_array($restrcheckout['wday'], $comborestr[$restrcheckin['wday']])) {
							$restrictionerrmsg = JText::sprintf('VBRESTRTIPWDAYCOMBO', self::sayMonth($restrcheckin['mon']), self::sayWeekDay($comborestr[$restrcheckin['wday']][0]).(count($comborestr[$restrcheckin['wday']]) == 2 ? '/'.self::sayWeekDay($comborestr[$restrcheckin['wday']][1]) : ''), self::sayWeekDay($restrcheckin['wday']));
						}
					}
				}
			} elseif (!empty($roomrestr[$restrcheckin['mon']]['ctad']) || !empty($roomrestr[$restrcheckin['mon']]['ctdd'])) {
				if (!empty($roomrestr[$restrcheckin['mon']]['ctad'])) {
					$ctarestrictions = explode(',', $roomrestr[$restrcheckin['mon']]['ctad']);
					if (in_array('-'.$restrcheckin['wday'].'-', $ctarestrictions)) {
						$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTAMONTH', self::sayWeekDay($restrcheckin['wday']), self::sayMonth($restrcheckin['mon']));
					}
				}
				if (!empty($roomrestr[$restrcheckin['mon']]['ctdd'])) {
					$ctdrestrictions = explode(',', $roomrestr[$restrcheckin['mon']]['ctdd']);
					if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions)) {
						$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTDMONTH', self::sayWeekDay($restrcheckout['wday']), self::sayMonth($restrcheckin['mon']));
					}
				}
			}
			if (!empty($roomrestr[$restrcheckin['mon']]['maxlos']) && $roomrestr[$restrcheckin['mon']]['maxlos'] > 0 && $roomrestr[$restrcheckin['mon']]['maxlos'] > $roomrestr[$restrcheckin['mon']]['minlos']) {
				if ($daysdiff > $roomrestr[$restrcheckin['mon']]['maxlos']) {
					$restrictionerrmsg = JText::sprintf('VBRESTRTIPMAXLOSEXCEEDED', self::sayMonth($restrcheckin['mon']), $roomrestr[$restrcheckin['mon']]['maxlos']);
				}
			}
			if ($daysdiff < $roomrestr[$restrcheckin['mon']]['minlos']) {
				$restrictionerrmsg = JText::sprintf('VBRESTRTIPMINLOSEXCEEDED', self::sayMonth($restrcheckin['mon']), $roomrestr[$restrcheckin['mon']]['minlos']);
			}
		} elseif (array_key_exists('range', $roomrestr)) {
			$restrictionsvalid = true;
			/**
			 * We use this map to know which restriction IDs are okay or not okay with the Min LOS.
			 * The most recent restrictions will have a higher priority over the oldest ones.
			 * 
			 * @since 	1.12.1
			 */
			$minlos_priority = array(
				'ok'  => array(),
				'nok' => array()
			);
			foreach ($roomrestr['range'] as $restr) {
				/**
				 * We should not always add 82799 seconds to the end date of the restriction
				 * because if they only last for one day (like a Saturday), then $restr['dto']
				 * will be already set to the time 23:59:59.
				 * 
				 * @since 	1.2.18
				 */
				$end_operator = date('Y-m-d', $restr['dfrom']) != date('Y-m-d', $restr['dto']) ? 82799 : 0;
				//
				if ($restr['dfrom'] <= $restrcheckin[0] && ($restr['dto'] + $end_operator) >= $restrcheckin[0]) {
					//restriction found for this date range, checking:
					$restrictions_affcount++;
					if (strlen($restr['wday']) > 0) {
						$rvalidwdays = array($restr['wday']);
						if (strlen($restr['wdaytwo']) > 0) {
							$rvalidwdays[] = $restr['wdaytwo'];
						}
						if (!in_array($restrcheckin['wday'], $rvalidwdays)) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VBRESTRTIPWDAYARRIVALRANGE', self::sayWeekDay($restr['wday']).(strlen($restr['wdaytwo']) > 0 ? '/'.self::sayWeekDay($restr['wdaytwo']) : ''));
						} elseif ($restr['multiplyminlos'] == 1) {
							if (($daysdiff % $restr['minlos']) != 0) {
								$restrictionsvalid = false;
								$restrictionerrmsg = JText::sprintf('VBRESTRTIPMULTIPLYMINLOSRANGE', $restr['minlos']);
							}
						}
						$comborestr = self::parseJsDrangeWdayCombo($restr);
						if (count($comborestr) > 0) {
							if (array_key_exists($restrcheckin['wday'], $comborestr)) {
								if (!in_array($restrcheckout['wday'], $comborestr[$restrcheckin['wday']])) {
									$restrictionsvalid = false;
									$restrictionerrmsg = JText::sprintf('VBRESTRTIPWDAYCOMBORANGE', self::sayWeekDay($comborestr[$restrcheckin['wday']][0]).(count($comborestr[$restrcheckin['wday']]) == 2 ? '/'.self::sayWeekDay($comborestr[$restrcheckin['wday']][1]) : ''), self::sayWeekDay($restrcheckin['wday']));
								}
							}
						}
					} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
						if (!empty($restr['ctad'])) {
							$ctarestrictions = explode(',', $restr['ctad']);
							if (in_array('-'.$restrcheckin['wday'].'-', $ctarestrictions)) {
								$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTARANGE', self::sayWeekDay($restrcheckin['wday']));
							}
						}
						if (!empty($restr['ctdd'])) {
							$ctdrestrictions = explode(',', $restr['ctdd']);
							if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions)) {
								$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTDRANGE', self::sayWeekDay($restrcheckout['wday']));
							}
						}
					}
					if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
						if ($daysdiff > $restr['maxlos']) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VBRESTRTIPMAXLOSEXCEEDEDRANGE', $restr['maxlos']);
						}
					}
					if ($daysdiff < $restr['minlos']) {
						$restrictionsvalid = false;
						$restrictionerrmsg = JText::sprintf('VBRESTRTIPMINLOSEXCEEDEDRANGE', $restr['minlos']);
						array_push($minlos_priority['nok'], (int)$restr['id']);
					} else {
						array_push($minlos_priority['ok'], (int)$restr['id']);
					}
				}
			}
			if (!$restrictionsvalid && count($minlos_priority['ok']) && count($minlos_priority['nok']) && max($minlos_priority['ok']) > max($minlos_priority['nok'])) {
				// we unset the error message because a more recent restriction is allowing this MinLOS
				$restrictionerrmsg = '';
			}
		}
		//April 2017 - Check global restriction of Min LOS for TAC functions in VBO and VCM
		if (empty($restrictionerrmsg) && count($roomrestr) && $restrictions_affcount <= 0) {
			//Check global MinLOS (only in case there are no restrictions affecting these dates or no restrictions at all)
			$globminlos = self::getDefaultNightsCalendar();
			if ($globminlos > 1 && $daysdiff < $globminlos) {
				$restrictionerrmsg = JText::sprintf('VBRESTRERRMINLOSEXCEEDEDRANGE', $globminlos);
			}
		}
		//

		return $restrictionerrmsg;
	}
	
	public static function parseJsDrangeWdayCombo($drestr) {
		$combo = array();
		if (strlen($drestr['wday']) > 0 && strlen($drestr['wdaytwo']) > 0 && !empty($drestr['wdaycombo'])) {
			$cparts = explode(':', $drestr['wdaycombo']);
			foreach($cparts as $kc => $cw) {
				if (!empty($cw)) {
					$nowcombo = explode('-', $cw);
					$combo[intval($nowcombo[0])][] = intval($nowcombo[1]);
				}
			}
		}
		return $combo;
	}

	public static function validateRoomPackage($pkg_id, $rooms, $numnights, $checkints, $checkoutts) {
		$dbo = JFactory::getDbo();
		$pkg = array();
		$q = "SELECT * FROM `#__vikbooking_packages` WHERE `id`='".intval($pkg_id)."';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$pkg = $dbo->loadAssoc();
			$vbo_tn = self::getTranslator();
			$vbo_tn->translateContents($pkg, '#__vikbooking_packages');
		} else {
			return JText::translate('VBOPKGERRNOTFOUND');
		}
		$rooms_req = array();
		foreach ($rooms as $num => $room) {
			if (!empty($room['id']) && !in_array($room['id'], $rooms_req)) {
				$rooms_req[] = $room['id'];
			}
		}
		$q = "SELECT `id` FROM `#__vikbooking_packages_rooms` WHERE `idpackage`=".$pkg['id']." AND `idroom` IN (".implode(', ', $rooms_req).");";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() != count($rooms_req)) {
			//error, not all the rooms requested are available for this package
			return JText::translate('VBOPKGERRNOTROOM');
		}
		if ($numnights < $pkg['minlos'] || ($pkg['maxlos'] > 0 && $numnights > $pkg['maxlos'])) {
			return JText::translate('VBOPKGERRNUMNIGHTS');
		}
		if ($checkints < $pkg['dfrom'] || $checkints > $pkg['dto']) {
			return JText::translate('VBOPKGERRCHECKIND');
		}
		if ($checkoutts < $pkg['dfrom'] || ($checkoutts > $pkg['dto'] && date('Y-m-d', $pkg['dfrom']) != date('Y-m-d', $pkg['dto']))) {
			//VBO 1.10 - we allow a check-out date after the pkg validity-end-date only if the validity dates are equal (dfrom & dto)
			return JText::translate('VBOPKGERRCHECKOUTD');
		}
		if (!empty($pkg['excldates'])) {
			//this would check if any stay date is excluded
			//$bookdates_ts = self::getGroupDays($checkints, $checkoutts, $numnights);
			//check just the arrival and departure dates
			$bookdates_ts = array($checkints, $checkoutts);
			$bookdates = array();
			foreach ($bookdates_ts as $bookdate_ts) {
				$info_d = getdate($bookdate_ts);
				$bookdates[] = $info_d['mon'].'-'.$info_d['mday'].'-'.$info_d['year'];
			}
			$edates = explode(';', $pkg['excldates']);
			foreach ($edates as $edate) {
				if (!empty($edate) && in_array($edate, $bookdates)) {
					return JText::sprintf('VBOPKGERREXCLUDEDATE', $edate);
				}
			}
		}
		return $pkg;
	}

	public static function getPackage($pkg_id) {
		$dbo = JFactory::getDbo();
		$pkg = array();
		$q = "SELECT * FROM `#__vikbooking_packages` WHERE `id`='".intval($pkg_id)."';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$pkg = $dbo->loadAssoc();
		}
		return $pkg;
	}
	
	public static function getRoomParam ($paramname, $paramstr) {
		if (empty($paramstr)) return '';
		$paramarr = json_decode($paramstr, true);
		if (array_key_exists($paramname, $paramarr)) {
			return $paramarr[$paramname];
		}
		return '';
	}

	public static function filterNightsSeasonsCal ($arr_nights) {
		$nights = array();
		foreach ($arr_nights as $night) {
			if (intval(trim($night)) > 0) {
				$nights[] = intval(trim($night));
			}
		}
		sort($nights);
		return array_unique($nights);
	}

	public static function getSeasonRangeTs ($from, $to, $year) {
		$sfrom = 0;
		$sto = 0;
		$tsbase = mktime(0, 0, 0, 1, 1, $year);
		$curyear = $year;
		$tsbasetwo = $tsbase;
		$curyeartwo = $year;
		if ($from > $to) {
			//between two years
			$curyeartwo += 1;
			$tsbasetwo = mktime(0, 0, 0, 1, 1, $curyeartwo);
		}
		$sfrom = ($tsbase + $from);
		$sto = ($tsbasetwo + $to);
		if ($curyear % 4 == 0 && ($curyear % 100 != 0 || $curyear % 400 == 0)) {
			//leap years
			$infoseason = getdate($sfrom);
			$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
			if ($infoseason[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom += 86400;
				if ($curyear == $curyeartwo) {
					$sto += 86400;
				}
			}
		} elseif ($curyeartwo % 4 == 0 && ($curyeartwo % 100 != 0 || $curyeartwo % 400 == 0)) {
			//leap years
			$infoseason = getdate($sto);
			$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
			if ($infoseason[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sto += 86400;
			}
		}
		return array($sfrom, $sto);
	}

	public static function sortSeasonsRangeTs ($all_seasons) {
		$sorted = array();
		$map = array();
		foreach ($all_seasons as $key => $season) {
			$map[$key] = $season['from_ts'];
		}
		asort($map);
		foreach ($map as $key => $s) {
			$sorted[] = $all_seasons[$key];
		}
		return $sorted;
	}

	public static function formatSeasonDates ($from_ts, $to_ts) {
		$one = getdate($from_ts);
		$two = getdate($to_ts);
		$months_map = array(
			1 => JText::translate('VBSHORTMONTHONE'),
			2 => JText::translate('VBSHORTMONTHTWO'),
			3 => JText::translate('VBSHORTMONTHTHREE'),
			4 => JText::translate('VBSHORTMONTHFOUR'),
			5 => JText::translate('VBSHORTMONTHFIVE'),
			6 => JText::translate('VBSHORTMONTHSIX'),
			7 => JText::translate('VBSHORTMONTHSEVEN'),
			8 => JText::translate('VBSHORTMONTHEIGHT'),
			9 => JText::translate('VBSHORTMONTHNINE'),
			10 => JText::translate('VBSHORTMONTHTEN'),
			11 => JText::translate('VBSHORTMONTHELEVEN'),
			12 => JText::translate('VBSHORTMONTHTWELVE')
		);
		$mday_map = array(
			1 => JText::translate('VBMDAYFRIST'),
			2 => JText::translate('VBMDAYSECOND'),
			3 => JText::translate('VBMDAYTHIRD'),
			'generic' => JText::translate('VBMDAYNUMGEN')
		);
		if ($one['year'] == $two['year']) {
			return $one['year'].' '.$months_map[(int)$one['mon']].' '.$one['mday'].'<sup>'.(array_key_exists((int)substr($one['mday'], -1), $mday_map) && ($one['mday'] < 10 || $one['mday'] > 20) ? $mday_map[(int)substr($one['mday'], -1)] : $mday_map['generic']).'</sup> - '.$months_map[(int)$two['mon']].' '.$two['mday'].'<sup>'.(array_key_exists((int)substr($two['mday'], -1), $mday_map) && ($two['mday'] < 10 || $two['mday'] > 20) ? $mday_map[(int)substr($two['mday'], -1)] : $mday_map['generic']).'</sup>';
		}
		return $months_map[(int)$one['mon']].' '.$one['mday'].'<sup>'.(array_key_exists((int)substr($one['mday'], -1), $mday_map) && ($one['mday'] < 10 || $one['mday'] > 20) ? $mday_map[(int)substr($one['mday'], -1)] : $mday_map['generic']).'</sup> '.$one['year'].' - '.$months_map[(int)$two['mon']].' '.$two['mday'].'<sup>'.(array_key_exists((int)substr($two['mday'], -1), $mday_map) && ($two['mday'] < 10 || $two['mday'] > 20) ? $mday_map[(int)substr($two['mday'], -1)] : $mday_map['generic']).'</sup> '.$two['year'];
	}

	public static function getFirstCustDataField($custdata) {
		$first_field = '';
		if (strpos($custdata, JText::translate('VBDBTEXTROOMCLOSED')) !== false) {
			//Room is closed with this booking
			return '----';
		}
		$parts = explode("\n", $custdata);
		foreach ($parts as $part) {
			if (!empty($part)) {
				$field = explode(':', trim($part));
				if (!empty($field[1])) {
					return trim($field[1]);
				}
			}
		}
		return $first_field;
	}

	/**
	 * This method composes a string to be logged for the admin
	 * to keep track of what was inside the booking before the
	 * modification. Returns a string and it uses language definitions
	 * that should be available on the front-end and back-end INI files.
	 *
	 * @param 	array 		$old_booking 	the array of the booking prior to the modification
	 *
	 * @return 	string
	 */
	public static function getLogBookingModification($old_booking) {
		$vbo_df = self::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
		$wdays_map = array(
			JText::translate('VBWEEKDAYZERO'),
			JText::translate('VBWEEKDAYONE'),
			JText::translate('VBWEEKDAYTWO'),
			JText::translate('VBWEEKDAYTHREE'),
			JText::translate('VBWEEKDAYFOUR'),
			JText::translate('VBWEEKDAYFIVE'),
			JText::translate('VBWEEKDAYSIX')
		);
		$now_info = getdate();
		$checkin_info = getdate($old_booking['checkin']);
		$checkout_info = getdate($old_booking['checkout']);

		$datemod = $wdays_map[$now_info['wday']].', '.date($df.' H:i', $now_info[0]);
		$prev_nights = $old_booking['days'].' '.($old_booking['days'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY'));
		$prev_dates = $prev_nights.' - '.$wdays_map[$checkin_info['wday']].', '.date($df, $checkin_info[0]).' - '.$wdays_map[$checkout_info['wday']].', '.date($df, $checkout_info[0]);
		$prev_rooms = '';
		if (isset($old_booking['rooms_info'])) {
			$orooms_arr = array();
			foreach ($old_booking['rooms_info'] as $oroom) {
				$orooms_arr[] = $oroom['name'].', '.JText::translate('VBMAILADULTS').': '.$oroom['adults'].', '.JText::translate('VBMAILCHILDREN').': '.$oroom['children'];
			}
			$prev_rooms = implode("\n", $orooms_arr);
		}
		$currencyname = self::getCurrencyName();
		$prev_total = $currencyname.' '.self::numberFormat($old_booking['total']);

		return JText::sprintf('VBOBOOKMODLOGSTR', $datemod, $prev_dates, $prev_rooms, $prev_total);
	}

	/**
	 * This method invokes the class
	 * VikChannelManagerLogos (new in VCM 1.6.4) to
	 * map the name of a channel to its corresponding logo.
	 * The method can also be used to get an istance of the class.
	 *
	 * @param 	mixed 		$provenience 	either a string or an array with main and sub channels.
	 * @param 	boolean 	$get_istance
	 *
	 * @return 	mixed 		boolean if the Class doesn't exist or if the provenience cannot be matched. Instance otherwise.
	 */
	public static function getVcmChannelsLogo($provenience, $get_istance = false) {
		if (!file_exists(VCM_ADMIN_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'logos.php')) {
			return false;
		}
		if (!class_exists('VikChannelManagerLogos')) {
			require_once(VCM_ADMIN_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'logos.php');
		}
		
		/**
		 * Due to the new iCal channel we now support main and sub channels.
		 * 
		 * @since 	1.13 - VCM 1.7.0
		 */
		if (is_string($provenience) && strpos($provenience, '_') !== false) {
			$provenience = explode('_', $provenience);
		}
		$main_channel = '';
		$full_channel = '';
		if (is_array($provenience)) {
			if (count($provenience) > 1) {
				$main_channel = $provenience[1];
			} else {
				$main_channel = $provenience[0];
			}
			if (stripos($provenience[0], 'ical') !== false) {
				$full_channel = implode('_', $provenience);
			}
		} else {
			$main_channel = $provenience;
		}

		// get object instance by passing the main provenience
		$obj = new VikChannelManagerLogos($main_channel);
		
		// update provenience with main and full channel
		if (!empty($full_channel)) {
			$obj->setProvenience($main_channel, $full_channel);
		}

		// return either the instance or the logo URL for this channel source
		return $get_istance ? $obj : $obj->getLogoURL();
	}

	public static function vcmAutoUpdate() {
		if (!file_exists(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			return -1;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='vcmautoupd';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) > 0 ? 1 : 0);
	}

	public static function getVcmInvoker() {
		if (!class_exists('VboVcmInvoker')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "vcm.php");
		}
		return new VboVcmInvoker();
	}

	public static function getBookingHistoryInstance() {
		if (!class_exists('VboBookingHistory')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "history.php");
		}
		return new VboBookingHistory();
	}

	/**
	 * Returns an instance of the VCMChatHandler class to handle
	 * the messaging/chat for the given reservation ID.
	 * 
	 * @param 	int 	$oid 		the ID of the booking in VBO
	 * @param 	string 	$channel 	the name of the source channel
	 * 
	 * @return 	mixed 	null if VCM is not available, VCMChatHandler instance otherwise
	 * 
	 * @since 	1.11.2
	 */
	public static function getVcmChatInstance($oid, $channel) {
		$vcm_messaging_helper = VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'chat' . DIRECTORY_SEPARATOR . 'handler.php';
		if (!is_file($vcm_messaging_helper)) {
			// VCM is not available
			return null;
		}
		// make sure the channel name is correct as it may contain the sub-network
		if (!empty($channel)) {
			$segments = explode('_', $channel);
			if (count($segments) > 1) {
				// we take the first segment, as the second could be the source sub-network (expedia_Hotels.com)
				$channel = $segments[0];
			}
		}
		// require main file of the abstract class
		require_once $vcm_messaging_helper;
		// return the instance of the class for this channel handler
		return VCMChatHandler::getInstance($oid, $channel);
	}

	/**
	 * Returns an instance of the VCMOpportunityHandler class to handle
	 * the various opportunities through VCM.
	 * 
	 * @return 	mixed 	null if VCM is not available, VCMOpportunityHandler instance otherwise
	 * 
	 * @since 	1.2.0
	 */
	public static function getVcmOpportunityInstance() {
		$vcm_opp_helper = VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'opportunity.php';
		if (!is_file($vcm_opp_helper)) {
			// VCM is not available
			return null;
		}
		// require main file of the class
		require_once $vcm_opp_helper;
		// return the instance of the class
		return VCMOpportunityHandler::getInstance();
	}

	public static function vcmBcomReportingSupported() {
		if (!is_file(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			return false;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `id` FROM `#__vikchannelmanager_channel` WHERE `uniquekey`='4';";
		$dbo->setQuery($q);
		$dbo->execute();
		return ($dbo->getNumRows() > 0);
	}

	/**
	 * Gets a list of all channels supporting the promotions.
	 * 
	 * @param 		string 	$key 	the key of the handler.
	 * 
	 * @return 		mixed 	false if VCM is not installed, empty array otherwise.
	 * 
	 * @requires 	Vik Channel Manager 1.7.1
	 * 
	 * @since 		1.3.0
	 */
	public static function getPromotionHandlers($key = null)
	{
		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			// VCM not installed
			return false;
		}

		if (!class_exists('VikChannelManager')) {
			require_once VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "lib.vikchannelmanager.php";
			require_once VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "vcm_config.php";
		}

		if (!method_exists('VikChannelManager', 'getPromotionHandlers')) {
			// VCM is outdated yet installed: an empty array is sufficient to not display warning messages
			return array();
		}

		return VikChannelManager::getPromotionHandlers($key);
	}

	/**
	 * Gets the factors for suggesting the application of the promotions.
	 * 
	 * @param 		mixed 	$data 	some optional instructions to be passed as argument.
	 * 
	 * @return 		mixed 	false if VCM is not installed, associative array otherwise.
	 * 
	 * @requires 	Vik Channel Manager 1.7.1
	 * 
	 * @since 		1.3.0
	 */
	public static function getPromotionFactors($data = null)
	{
		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			// VCM not installed
			return false;
		}

		if (!class_exists('VikChannelManager')) {
			require_once VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "lib.vikchannelmanager.php";
			require_once VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "vcm_config.php";
		}

		if (!method_exists('VikChannelManager', 'getPromotionFactors')) {
			// VCM is outdated
			return false;
		}

		return VikChannelManager::getPromotionFactors($data);
	}
	
	public static function getTheme() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='theme';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}
	
	public static function getFooterOrdMail($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='footerordmail';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		return $ft[0]['setting'];
	}
	
	public static function requireLogin() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='requirelogin';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return intval($s[0]['setting']) == 1 ? true : false;
	}

	public static function autoRoomUnit() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='autoroomunit';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return intval($s[0]['setting']) == 1 ? true : false;
	}

	public static function todayBookings() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='todaybookings';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return intval($s[0]['setting']) == 1 ? true : false;
	}
	
	public static function couponsEnabled() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='enablecoupons';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return intval($s[0]['setting']) == 1 ? true : false;
	}

	public static function customersPinEnabled() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='enablepin';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return intval($s[0]['setting']) == 1 ? true : false;
	}
	
	/**
	 * Detects the type of visitor from the user agent.
	 * Known types are: computer, smartphone, tablet.
	 * 
	 * @param 	boolean  $returnua 		whether the type of visitor should be returned. If false
	 * 									boolean is returned in case of mobile device detected.
	 * @param 	boolean  $loadassets 	whether the system should load an apposite CSS file.
	 * 
	 * @return 	mixed 	 string for the type of visitor or boolean if mobile detected.
	 * 
	 * @since 	1.0.13 - Revision September 2018
	 */
	public static function detectUserAgent($returnua = false, $loadassets = true) {
		$session = JFactory::getSession();
		$sval = $session->get('vbuseragent', '');
		$mobiles = array('tablet', 'smartphone');
		if (!empty($sval)) {
			if ($loadassets) {
				self::userAgentStyleSheet($sval);
			}
			return $returnua ? $sval : in_array($sval, $mobiles);
		}
		if (!class_exists('MobileDetector')) {
			require_once(VBO_SITE_PATH . DS . "helpers" . DS ."mobile_detector.php");
		}
		$detector = new MobileDetector;
		$visitoris = $detector->isMobile() ? ($detector->isTablet() ? 'tablet' : 'smartphone') : 'computer';
		$session->set('vbuseragent', $visitoris);
		if ($loadassets) {
			self::userAgentStyleSheet($visitoris);
		}
		return $returnua ? $visitoris : in_array($visitoris, $mobiles);
	}
	
	public static function userAgentStyleSheet($ua) {
		/**
		 * @wponly 	in order to not interfere with AJAX requests, we do nothing if doing AJAX.
		 */
		if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
			return;
		}
		//

		$document = JFactory::getDocument();
		/**
		 * @wponly 	the following CSS files are located in /site/resources/ for WP, not just on /site
		 */
		if ($ua == 'smartphone') {
			$document->addStyleSheet(VBO_SITE_URI.'resources/vikbooking_smartphones.css');
		} elseif ($ua == 'tablet') {
			$document->addStyleSheet(VBO_SITE_URI.'resources/vikbooking_tablets.css');
		}
		return true;
	}
	
	public static function loadJquery($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='loadjquery';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return intval($s[0]['setting']) == 1 ? true : false;
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbloadJquery', '');
			if (!empty($sval)) {
				return intval($sval) == 1 ? true : false;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='loadjquery';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vbloadJquery', $s[0]['setting']);
				return intval($s[0]['setting']) == 1 ? true : false;
			}
		}
	}

	public static function loadBootstrap($skipsession = false) {
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='bootstrap';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return intval($s[0]['setting']) == 1 ? true : false;
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbBootstrap', '');
			if (!empty($sval)) {
				return intval($sval) == 1 ? true : false;
			} else {
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='bootstrap';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vbBootstrap', $s[0]['setting']);
				return intval($s[0]['setting']) == 1 ? true : false;
			}
		}
	}

	public static function allowMultiLanguage($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='multilang';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return intval($s[0]['setting']) == 1 ? true : false;
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbMultiLang', '');
			if (!empty($sval)) {
				return intval($sval) == 1 ? true : false;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='multilang';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vbMultiLang', $s[0]['setting']);
				return intval($s[0]['setting']) == 1 ? true : false;
			}
		}
	}

	public static function getTranslator() {
		if (!class_exists('VikBookingTranslator')) {
			require_once(VBO_SITE_PATH . DS . "helpers" . DS . "translator.php");
		}
		return new VikBookingTranslator();
	}

	public static function getCPinIstance() {
		if (!class_exists('VikBookingCustomersPin')) {
			require_once(VBO_SITE_PATH . DS . "helpers" . DS . "cpin.php");
		}
		return new VikBookingCustomersPin();
	}

	/**
	 * Returns an instance of the VikBookingTracker Class.
	 * It is also possible to call this method to just require the library.
	 * This is useful for the back-end to access some static methods
	 * without tracking any data.
	 * 
	 * @param 	boolean 	$require_only 	whether to return the object.
	 * 
	 * @return 	VikBookingTracker
	 * 
	 * @since 	1.11
	 */
	public static function getTracker($require_only = false) {
		if (!class_exists('VikBookingTracker')) {
			require_once(VBO_SITE_PATH . DS . "helpers" . DS . "tracker.php");
		}
		return $require_only ? true : VikBookingTracker::getInstance();
	}

	/**
	 * Returns an instance of the VikBookingOperator Class.
	 * 
	 * @return 	VikBookingOperator
	 * 
	 * @since 	1.11
	 */
	public static function getOperatorInstance() {
		if (!class_exists('VikBookingOperator')) {
			require_once(VBO_SITE_PATH . DS . "helpers" . DS . "operator.php");
		}
		return VikBookingOperator::getInstance();
	}

	/**
	 * Returns an instance of the VikBookingFestivities Class.
	 * 
	 * @return 	VikBookingFestivities
	 * 
	 * @since 	1.12
	 */
	public static function getFestivitiesInstance() {
		if (!class_exists('VikBookingFestivities')) {
			require_once(VBO_ADMIN_PATH . DS . 'helpers' . DS . 'festivities.php');
		}
		return VikBookingFestivities::getInstance();
	}

	/**
	 * Returns an instance of the VikBookingCriticalDates Class.
	 * 
	 * @return 	VikBookingCriticalDates
	 * 
	 * @since 	1.13.5
	 */
	public static function getCriticalDatesInstance() {
		if (!class_exists('VikBookingCriticalDates')) {
			require_once(VBO_ADMIN_PATH . DS . 'helpers' . DS . 'critical_dates.php');
		}
		return VikBookingCriticalDates::getInstance();
	}

	/**
	 * Checks whether the chat is enabled.
	 * 
	 * @return 	int 	-1 if VCM is not installed, 0 if disabled, 1 otherwise
	 * 
	 * @since 	1.12
	 */
	public static function chatEnabled() {
		if (!is_file(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			return -1;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='chatenabled';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// enable the chat by default if VCM is installed
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('chatenabled', '1');";
			$dbo->setQuery($q);
			$dbo->execute();
			return 1;
		}
		$s = $dbo->loadResult();
		return intval($s);
	}

	/**
	 * Loads the chat parameters from the configuration.
	 * 
	 * @return 	object 	stdClass object from decoded JSON string
	 * 
	 * @since 	1.12
	 */
	public static function getChatParams() {
		if (!is_file(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			return new stdClass;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='chatparams';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// compose default and basic chat params for the first time
			$basic_params = new stdClass;
			$basic_params->res_status = array('confirmed', 'standy', 'cancelled');
			$basic_params->av_type = 'checkin';
			$basic_params->av_days = 0;

			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('chatparams', ".$dbo->quote(json_encode($basic_params)).");";
			$dbo->setQuery($q);
			$dbo->execute();

			return $basic_params;
		}
		return json_decode($dbo->loadResult());
	}

	/**
	 * Checks whether the pre-checkin is enabled.
	 * 
	 * @return 	int 	0 if disabled, 1 otherwise
	 * 
	 * @since 	1.12
	 */
	public static function precheckinEnabled() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='precheckinenabled';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// enable the pre-checkin by default
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('precheckinenabled', '1');";
			$dbo->setQuery($q);
			$dbo->execute();
			return 1;
		}
		$s = $dbo->loadResult();
		return intval($s);
	}

	/**
	 * Returns the minimum number of days in advance to
	 * enable the pre-checkin via front-end.
	 * 
	 * @return 	int 	the min number of days in advance for pre-checkin.
	 * 
	 * @since 	1.12
	 */
	public static function precheckinMinOffset() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='precheckinminoffset';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// set the limit to 1 day before arrival by default
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('precheckinminoffset', '1');";
			$dbo->setQuery($q);
			$dbo->execute();
			return 1;
		}
		$s = $dbo->loadResult();
		return intval($s);
	}

	/**
	 * Whether upselling extra services is enabled.
	 * 
	 * @return 	boolean 	true if enabled, false otherwise.
	 * 
	 * @since 	1.13
	 */
	public static function upsellingEnabled() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='upselling';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// enable the upselling feature by default
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('upselling', '1');";
			$dbo->setQuery($q);
			$dbo->execute();
			return true;
		}
		return (intval($dbo->loadResult()) > 0);
	}

	/**
	 * Collects a list of options/extra that can be upsold for the
	 * the rooms booked.
	 * 
	 * @param 	array 	$upsell_data 	list of stdClass objects for each room booked.
	 * @param 	array 	$info 			some booking details (id, checkin, checkout).
	 * @param 	object 	$vbo_tn 		the translation object.
	 * 
	 * @return 	array 	list of upsellable options for each room booked.
	 * 
	 * @since 	1.3.0
	 */
	public static function loadUpsellingData($upsell_data, $info, $vbo_tn) {
		$dbo = JFactory::getDbo();
		// get all options for all rooms booked
		$all_room_ids = array();
		foreach ($upsell_data as $v) {
			if (!in_array($v->id, $all_room_ids)) {
				array_push($all_room_ids, $v->id);
			}
		}
		$q = "SELECT `id`, `idopt` FROM `#__vikbooking_rooms` WHERE `id` IN (" . implode(', ', $all_room_ids) . ");";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no rooms found
			return array();
		}
		$records = $dbo->loadAssocList();
		$all_options = array();
		$rooms_options = array();
		foreach ($records as $v) {
			$allopts = explode(';', $v['idopt']);
			$room_opt = array();
			foreach ($allopts as $o) {
				if (empty($o)) {
					continue;
				}
				if (!in_array((int)$o, $all_options)) {
					array_push($all_options, (int)$o);
				}
				array_push($room_opt, (int)$o);
			}
			$rooms_options[$v['id']] = $room_opt;
		}
		if (!count($all_options)) {
			// no options found
			return array();
		}
		// load all options that could be used by the booked rooms no matter what was already booked
		$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id` IN (" . implode(', ', $all_options) . ") AND `forcesel`=0 AND `ifchildren`=0 AND `is_citytax`=0 AND `is_fee`=0;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no upsell-able options found
			return array();
		}
		$records = $dbo->loadAssocList();
		// filter options by available date and translate records
		self::filterOptionalsByDate($records, $info['checkin'], $info['checkout']);
		$vbo_tn->translateContents($records, '#__vikbooking_optionals');
		//
		$tot_upsellable = 0;
		foreach ($upsell_data as $k => $rdata) {
			if (!isset($upsell_data[$k]->upsellable)) {
				$upsell_data[$k]->upsellable = array();
			}
			foreach ($records as $opt) {
				if (!empty($opt['ageintervals'])) {
					// upsellable options should not contain age intervals for children
					continue;
				}
				if (!in_array($opt['id'], $rooms_options[$rdata->id])) {
					// this option is not assigned to this room
					continue;
				}
				// check if the option is suited for this room party
				$clone_opt = array($opt);
				self::filterOptionalsByParty($clone_opt, $rdata->adults, $rdata->children);
				if (!is_array($clone_opt) || !count($clone_opt)) {
					// this option is not suited for this room party
					continue;
				}
				//
				if (in_array($opt['id'], $rdata->options)) {
					// this option has already been booked
					continue;
				}
				// push this option and increase counter
				array_push($upsell_data[$k]->upsellable, $opt);
				$tot_upsellable++;
			}
		}
		
		// if no upsellable options were found, we return an empty array
		return $tot_upsellable > 0 ? $upsell_data : array();
	}

	/**
	 * Returns the minimum days in advance for booking, by considering by default
	 * also the property closing dates. If the property is currently closed, then
	 * the minimum number of days in advance will be increased.
	 * 
	 * @param 	bool 	$no_closing_dates 	whether to skip checking closing dates.
	 * 
	 * @return 	int 						the number of days in advance for booking.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP) the signature was changed from:
	 *			getMinDaysAdvance($skipsession = false) to:
	 * 			getMinDaysAdvance($no_closing_dates = false)
	 * 			This was made to return the proper number of days in advance
	 * 			in case the property is currently closed.
	 */
	public static function getMinDaysAdvance($no_closing_dates = false) {
		// cache value in static var
		static $getMinDaysAdvance = null;

		if ($getMinDaysAdvance) {
			return $getMinDaysAdvance;
		}
		//

		$dbo = JFactory::getDbo();
		
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='mindaysadvance';";
		$dbo->setQuery($q);
		$dbo->execute();
		$mind = $dbo->getNumRows() ? (int)$dbo->loadResult() : 0;

		// update cached var
		$getMinDaysAdvance = $mind;

		if ($no_closing_dates) {
			// do not check the closing dates
			return $mind;
		}

		// check if the property is currently closed
		$cur_closed_dates = self::getClosingDates();
		if (is_array($cur_closed_dates) && count($cur_closed_dates)) {
			$today_midnight = mktime(0, 0, 0);
			$closed_until = null;
			foreach ($cur_closed_dates as $kcd => $vcd) {
				if ($today_midnight >= $vcd['from'] && $today_midnight <= $vcd['to']) {
					// closing period found
					$closed_until = $vcd['to'];
					break;
				}
			}
			if ($closed_until !== null) {
				// count the number of days until property is closed
				$mind = 0;
				$today_info = getdate($today_midnight);
				while ($today_info[0] <= $closed_until) {
					$mind++;
					$today_info = getdate(mktime(0, 0, 0, $today_info['mon'], ($today_info['mday'] + 1), $today_info['year']));
				}
			}
		}

		// update cached var
		$getMinDaysAdvance = $mind;

		return $mind;
	}
	
	public static function getDefaultNightsCalendar($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='autodefcalnights';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (int)$s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbdefaultNightsCalendar', '');
			if (!empty($sval)) {
				return (int)$sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='autodefcalnights';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vbdefaultNightsCalendar', $s[0]['setting']);
				return (int)$s[0]['setting'];
			}
		}
	}
	
	public static function getSearchNumRooms($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numrooms';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (int)$s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbsearchNumRooms', '');
		if (!empty($sval)) {
			return (int)$sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numrooms';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbsearchNumRooms', $s[0]['setting']);
		return (int)$s[0]['setting'];
	}
	
	public static function getSearchNumAdults($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numadults';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbsearchNumAdults', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numadults';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbsearchNumAdults', $s[0]['setting']);
		return $s[0]['setting'];
	}
	
	public static function getSearchNumChildren($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numchildren';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbsearchNumChildren', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numchildren';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbsearchNumChildren', $s[0]['setting']);
		return $s[0]['setting'];
	}
	
	public static function getSmartSearchType($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smartsearch';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbsmartSearchType', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smartsearch';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbsmartSearchType', $s[0]['setting']);
		return $s[0]['setting'];
	}
	
	public static function getMaxDateFuture($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='maxdate';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbmaxDateFuture', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='maxdate';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbmaxDateFuture', $s[0]['setting']);
		return $s[0]['setting'];
	}

	public static function validateMaxDateBookings($checkints) {
		$datelim = self::getMaxDateFuture();
		$datelim = empty($datelim) ? '+2y' : $datelim;
		$numlim = (int)substr($datelim, 1, (strlen($datelim) - 2));
		$quantlim = substr($datelim, -1, 1);

		$now = getdate();
		if ($quantlim == 'w') {
			$until_ts = strtotime("+$numlim weeks") + 86399;
		} else {
			$until_ts = mktime(23, 59, 59, ($quantlim == 'm' ? ((int)$now['mon']+$numlim) : $now['mon']), ($quantlim == 'd' ? ((int)$now['mday']+$numlim) : $now['mday']), ($quantlim == 'y' ? ((int)$now['year']+$numlim) : $now['year']));
		}

		if ($until_ts > $now[0] && $checkints > $until_ts) {
			$vbo_df = self::getDateFormat();
			$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
			return date($df, $until_ts);
		}

		return '';
	}

	public static function validateMinDaysAdvance($checkints) {
		$mindadv = self::getMinDaysAdvance(true);
		if ($mindadv > 0) {
			$tsinfo = getdate($checkints);
			$limit_ts = mktime($tsinfo['hours'], $tsinfo['minutes'], $tsinfo['seconds'], date('n'), ((int)date('j') + $mindadv), date('Y'));
			if ($checkints < $limit_ts) {
				return $mindadv;
			}
		}

		return '';
	}
	
	/**
	 * The only supported calendar type has been changed to jQuery UI.
	 * 
	 * @since 	1.13
	 */
	public static function calendarType($skipsession = false) {
		return 'jqueryui';
	}
	
	public static function getSiteLogo() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='sitelogo';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getBackendLogo() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='backlogo';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		return '';
	}
	
	public static function numCalendars() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numcalendars';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}
	
	public static function getFirstWeekDay($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='firstwday';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbfirstWeekDay', '');
			if (strlen($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='firstwday';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vbfirstWeekDay', $s[0]['setting']);
				return $s[0]['setting'];
			}
		}
	}
	
	public static function showPartlyReserved() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showpartlyreserved';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return intval($s[0]['setting']) == 1 ? true : false;
	}

	public static function showStatusCheckinoutOnly() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showcheckinoutonly';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return intval($s[0]['setting']) == 1 ? true : false;
	}

	public static function getDisclaimer($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='disclaimer';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		return $ft[0]['setting'];
	}

	public static function showFooter() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showfooter';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$s = $dbo->loadAssocList();
			return (intval($s[0]['setting']) == 1 ? true : false);
		} else {
			return false;
		}
	}

	public static function getPriceName($idp, $vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`name` FROM `#__vikbooking_prices` WHERE `id`=" . (int)$idp . "";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$n = $dbo->loadAssocList();
			if (is_object($vbo_tn)) {
				$vbo_tn->translateContents($n, '#__vikbooking_prices');
			}
			return $n[0]['name'];
		}
		return "";
	}

	public static function getPriceAttr($idp, $vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`attr` FROM `#__vikbooking_prices` WHERE `id`=" . (int)$idp . "";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$n = $dbo->loadAssocList();
			if (is_object($vbo_tn)) {
				$vbo_tn->translateContents($n, '#__vikbooking_prices');
			}
			return $n[0]['attr'];
		}
		return "";
	}
	
	public static function getPriceInfo($idp, $vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_prices` WHERE `id`=" . (int)$idp . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$n = $dbo->loadAssocList();
			if (is_object($vbo_tn)) {
				$vbo_tn->translateContents($n, '#__vikbooking_prices');
			}
			return $n[0];
		}
		return "";
	}
	
	public static function getAliq($idal) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `aliq` FROM `#__vikbooking_iva` WHERE `id`='" . $idal . "';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$n = $dbo->loadAssocList();
			return $n[0]['aliq'];
		}
		return "";
	}

	public static function getTimeOpenStore($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='timeopenstore';";
			$dbo->setQuery($q);
			$dbo->execute();
			$n = $dbo->loadAssocList();
			if (empty($n[0]['setting']) && $n[0]['setting'] != "0") {
				return false;
			} else {
				$x = explode("-", $n[0]['setting']);
				if (!empty($x[1]) && $x[1] != "0") {
					return $x;
				}
			}
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbgetTimeOpenStore', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='timeopenstore';";
				$dbo->setQuery($q);
				$dbo->execute();
				$n = $dbo->loadAssocList();
				if (empty($n[0]['setting']) && $n[0]['setting'] != "0") {
					return false;
				} else {
					$x = explode("-", $n[0]['setting']);
					if (!empty($x[1]) && $x[1] != "0") {
						$session->set('vbgetTimeOpenStore', $x);
						return $x;
					}
				}
			}
		}
		return false;
	}

	public static function getHoursMinutes($secs) {
		if ($secs >= 3600) {
			$op = $secs / 3600;
			$hours = floor($op);
			$less = $hours * 3600;
			$newsec = $secs - $less;
			$optwo = $newsec / 60;
			$minutes = floor($optwo);
		} else {
			$hours = "0";
			$optwo = $secs / 60;
			$minutes = floor($optwo);
		}
		$x[] = $hours;
		$x[] = $minutes;
		return $x;
	}

	public static function getClosingDates() {
		// cache value in static var
		static $getClosingDates = null;

		if ($getClosingDates) {
			return $getClosingDates;
		}
		//

		$dbo = JFactory::getDbo();
		$allcd = array();

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='closingdates';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$s = $dbo->loadAssocList();
			if (!empty($s[0]['setting'])) {
				$allcd = json_decode($s[0]['setting'], true);
				$allcd = is_array($allcd) ? $allcd : array();
				$base_ts = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
				foreach ($allcd as $k => $v) {
					if ($v['to'] < $base_ts) {
						unset($allcd[$k]);
					}
				}
				$allcd = array_values($allcd);
			}
		}

		$getClosingDates = $allcd;

		return $allcd;
	}

	public static function parseJsClosingDates() {
		$cd = self::getClosingDates();
		if (count($cd) > 0) {
			$cdjs = array();
			foreach ($cd as $k => $v) {
				$cdjs[] = array(date('Y-m-d', $v['from']), date('Y-m-d', $v['to']));
			}
			return $cdjs;
		}
		return array();
	}

	public static function validateClosingDates($checkints, $checkoutts, $df = null)
	{
		$cd = self::getClosingDates();
		if (!count($cd)) {
			return '';
		}
		$df = empty($df) ? 'Y-m-d' : $df;
		$margin_seconds = 22 * 60 * 60;
		foreach ($cd as $k => $v) {
			$inner_closed = ($checkints >= $v['from'] && $checkints <= ($v['to'] + $margin_seconds));
			$outer_closed = ($checkoutts >= $v['from'] && $checkoutts <= ($v['to'] + $margin_seconds));
			$middle_closed = ($checkints <= $v['from'] && $checkoutts >= ($v['to'] + $margin_seconds));
			if ($inner_closed || $outer_closed || $middle_closed) {
				return date($df, $v['from']) . ' - ' . date($df, $v['to']);
			}
		}
		return '';
	}

	/**
	 * Whether the categories dropdown filter menu should be displayed.
	 * 
	 * @param 	boolean 	$skipsession 	[optional] re-read the configuration setting.
	 * 
	 * @return 	boolean
	 */
	public static function showCategoriesFront($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showcategories';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$s = $dbo->loadAssocList();
				return (intval($s[0]['setting']) == 1);
			}
			return false;
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbshowCategoriesFront', '');
		if (strlen($sval)) {
			return (intval($sval) == 1 ? true : false);
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showcategories';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			$session->set('vbshowCategoriesFront', $s[0]['setting']);
			return (intval($s[0]['setting']) == 1);
		}
		return false;
	}
	
	/**
	 * Whether the number of children dropdown menu should be displayed.
	 * Defaults to skip the session values and to re-read the configuration setting.
	 * 
	 * @param 	boolean 	$skipsession 	[optional] re-read the configuration setting.
	 * 
	 * @return 	boolean
	 */
	public static function showChildrenFront($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showchildren';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$s = $dbo->loadAssocList();
				return (intval($s[0]['setting']) == 1);
			}
			return false;
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbshowChildrenFront', '');
		if (strlen($sval)) {
			return (intval($sval) == 1 ? true : false);
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showchildren';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			$session->set('vbshowChildrenFront', $s[0]['setting']);
			return (intval($s[0]['setting']) == 1);
		}
		return false;
	}

	public static function allowBooking() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='allowbooking';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			return (intval($s[0]['setting']) == 1 ? true : false);
		} else {
			return false;
		}
	}

	public static function getDisabledBookingMsg($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='disabledbookingmsg';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($s, '#__vikbooking_texts');
		return $s[0]['setting'];
	}

	public static function getDateFormat($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbgetDateFormat', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbgetDateFormat', $s[0]['setting']);
		return $s[0]['setting'];
	}

	public static function getDateSeparator($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='datesep';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return empty($s[0]['setting']) ? "/" : $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbgetDateSep', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='datesep';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbgetDateSep', $s[0]['setting']);
		return empty($s[0]['setting']) ? "/" : $s[0]['setting'];
	}

	public static function getHoursMoreRb($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='hoursmorebookingback';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('getHoursMoreRb', '');
			if (strlen($sval) > 0) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='hoursmorebookingback';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('getHoursMoreRb', $s[0]['setting']);
				return $s[0]['setting'];
			}
		}
	}

	public static function getHoursRoomAvail() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='hoursmoreroomavail';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getFrontTitle($vbo_tn = null) {
		// cache value in static var
		static $getFrontTitle = null;

		if ($getFrontTitle) {
			return $getFrontTitle;
		}
		//

		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='fronttitle';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		
		$getFrontTitle = $ft[0]['setting'];

		return $ft[0]['setting'];
	}

	public static function getFrontTitleTag() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='fronttitletag';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	public static function getFrontTitleTagClass() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='fronttitletagclass';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	public static function getCurrencyName() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencyname';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	public static function getCurrencySymb($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencysymb';";
			$dbo->setQuery($q);
			$dbo->execute();
			$ft = $dbo->loadAssocList();
			return $ft[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbgetCurrencySymb', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencysymb';";
				$dbo->setQuery($q);
				$dbo->execute();
				$ft = $dbo->loadAssocList();
				$session->set('vbgetCurrencySymb', $ft[0]['setting']);
				return $ft[0]['setting'];
			}
		}
	}
	
	public static function getNumberFormatData($skipsession = false) {
		// cache value in static var
		static $getNumberFormatData = null;

		if ($getNumberFormatData) {
			return $getNumberFormatData;
		}
		//

		$dbo = JFactory::getDbo();
		
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numberformat'";
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			$numfdata = $dbo->loadResult();

			// cache value and return it
			$getNumberFormatData = $numfdata;

			return $numfdata;
		}

		$session = JFactory::getSession();
		$sval = $session->get('getNumberFormatData', '');
		if (!empty($sval)) {
			return $sval;
		}

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numberformat'";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		$numfdata = $dbo->loadResult();

		// update session value, cache it and return it
		$session->set('getNumberFormatData', $numfdata);
		$getNumberFormatData = $numfdata;

		return $numfdata;
	}

	/**
	 * It is possible to hide the decimals if they are like "N.00".
	 * 
	 * @return 	int 	0 if disabled, 1 if enabled (default).
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function hideEmptyDecimals() {
		// cache value in static var
		static $hideEmptyDecimals = null;

		if ($hideEmptyDecimals !== null) {
			return $hideEmptyDecimals;
		}
		//

		$dbo = JFactory::getDbo();

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='noemptydecimals';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$hideEmptyDecimals = (int)$dbo->loadResult();
			return $hideEmptyDecimals;
		}

		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('noemptydecimals', '1');";
		$dbo->setQuery($q);
		$dbo->execute();

		$hideEmptyDecimals = 1;
		
		return $hideEmptyDecimals;
	}
	
	public static function numberFormat($num, $skipsession = false) {
		$formatvals = self::getNumberFormatData($skipsession);
		$formatparts = explode(':', $formatvals);

		if ((int)$formatparts[0] > 0 && (floatval($num) - intval($num)) == 0 && self::hideEmptyDecimals()) {
			// number has got no decimals
			$formatparts[0] = 0;
		}

		return number_format((float)$num, (int)$formatparts[0], $formatparts[1], $formatparts[2]);
	}

	public static function getCurrencyCodePp() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencycodepp';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	public static function getIntroMain($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='intromain';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		return $ft[0]['setting'];
	}

	public static function getClosingMain($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='closingmain';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		return $ft[0]['setting'];
	}

	public static function getFullFrontTitle($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='fronttitle';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='fronttitletag';";
		$dbo->setQuery($q);
		$dbo->execute();
		$fttag = $dbo->loadAssocList();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='fronttitletagclass';";
		$dbo->setQuery($q);
		$dbo->execute();
		$fttagclass = $dbo->loadAssocList();
		if (empty($ft[0]['setting'])) {
			return "";
		} else {
			if (empty($fttag[0]['setting'])) {
				return $ft[0]['setting'] . "<br/>\n";
			} else {
				$tag = str_replace("<", "", $fttag[0]['setting']);
				$tag = str_replace(">", "", $tag);
				$tag = str_replace("/", "", $tag);
				$tag = trim($tag);
				return "<" . $tag . "" . (!empty($fttagclass) ? " class=\"" . $fttagclass[0]['setting'] . "\"" : "") . ">" . $ft[0]['setting'] . "</" . $tag . ">";
			}
		}
	}

	public static function dateIsValid($date) {
		$df = self::getDateFormat();
		$datesep = self::getDateSeparator();
		if (strlen($date) != 10) {
			return false;
		}
		$cur_dsep = "/";
		if ($datesep != $cur_dsep && strpos($date, $datesep) !== false) {
			$cur_dsep = $datesep;
		}
		$x = explode($cur_dsep, $date);
		if ($df == "%d/%m/%Y") {
			if (strlen($x[0]) != 2 || $x[0] > 31 || strlen($x[1]) != 2 || $x[1] > 12 || strlen($x[2]) != 4) {
				return false;
			}
		} elseif ($df == "%m/%d/%Y") {
			if (strlen($x[1]) != 2 || $x[1] > 31 || strlen($x[0]) != 2 || $x[0] > 12 || strlen($x[2]) != 4) {
				return false;
			}
		} else {
			if (strlen($x[2]) != 2 || $x[2] > 31 || strlen($x[1]) != 2 || $x[1] > 12 || strlen($x[0]) != 4) {
				return false;
			}
		}
		return true;
	}

	public static function sayDateFormat() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if ($s[0]['setting'] == "%d/%m/%Y") {
			return JText::translate('VBCONFIGONETWELVE');
		} elseif ($s[0]['setting'] == "%m/%d/%Y") {
			return JText::translate('VBCONFIGONEMDY');
		} else {
			return JText::translate('VBCONFIGONETENTHREE');
		}
	}

	/**
	 * Calculates the Unix timestamp from the given date and
	 * time. Avoids DST issues thanks to mktime. With prior releases,
	 * DST issues may occur due to the sum of seconds.
	 * 
	 * @param 	string 	$date 	the date string formatted with the current settings
	 * @param 	int 	$h 		hours from 0 to 23 for check-in/check-out
	 * @param 	int 	$m 		minutes from 0 to 59 for check-in/check-out
	 * @param 	int 	$s 		seconds from 0 to 59 for check-in/check-out
	 * 
	 * @return 	int 	the Unix timestamp of the date
	 * 
	 * @since 	1.0.14
	 */
	public static function getDateTimestamp($date, $h, $m, $s = 0) {
		$df = self::getDateFormat();
		$datesep = self::getDateSeparator();
		$cur_dsep = "/";
		if ($datesep != $cur_dsep && strpos($date, $datesep) !== false) {
			$cur_dsep = $datesep;
		}
		$x = explode($cur_dsep, $date);
		if (!(count($x) > 2)) {
			return 0;
		}
		if ($df == "%d/%m/%Y") {
			$month = (int)$x[1];
			$mday = (int)$x[0];
			$year = (int)$x[2];
		} elseif ($df == "%m/%d/%Y") {
			$month = (int)$x[0];
			$mday = (int)$x[1];
			$year = (int)$x[2];
		} else {
			$month = (int)$x[1];
			$mday = (int)$x[2];
			$year = (int)$x[0];
		}
		$h = empty($h) ? 0 : (int)$h;
		$m = empty($m) ? 0 : (int)$m;
		$s = $s > 0 && $s <= 59 ? $s : 0;
		return mktime($h, $m, $s, $month, $mday, $year);
	}

	public static function ivaInclusa($skipsession = false) {
		// cache value in static var
		static $getTaxIncluded = null;

		if ($getTaxIncluded) {
			return (bool)$getTaxIncluded;
		}
		//

		$dbo = JFactory::getDbo();
		
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='ivainclusa'";
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			$vat_incl_data = $dbo->getNumRows() ? (int)$dbo->loadResult() : 1;

			// cache value and return it
			$getTaxIncluded = $vat_incl_data;

			return (bool)$vat_incl_data;
		}

		$session = JFactory::getSession();
		$sval = $session->get('getTaxIncluded', '');
		if (strlen($sval)) {
			return (bool)$sval;
		}

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='ivainclusa'";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		$vat_incl_data = $dbo->getNumRows() ? (int)$dbo->loadResult() : 1;

		// update session value, cache it and return it
		$session->set('getTaxIncluded', $vat_incl_data);
		$getTaxIncluded = $vat_incl_data;

		return (bool)$vat_incl_data;
	}
	
	public static function showTaxOnSummaryOnly($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='taxsummary';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (intval($s[0]['setting']) == 1 ? true : false);
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbshowTaxOnSummaryOnly', '');
			if (strlen($sval) > 0) {
				return (intval($sval) == 1 ? true : false);
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='taxsummary';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vbshowTaxOnSummaryOnly', $s[0]['setting']);
				return (intval($s[0]['setting']) == 1 ? true : false);
			}
		}
	}

	public static function tokenForm() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='tokenform';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getPaypalAcc() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='ccpaypal';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getAccPerCent() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='payaccpercent';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}
	
	public static function getTypeDeposit($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='typedeposit';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('getTypeDeposit', '');
			if (strlen($sval) > 0) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='typedeposit';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('getTypeDeposit', $s[0]['setting']);
				return $s[0]['setting'];
			}
		}
	}
	
	public static function multiplePayments() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='multipay';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getAdminMail() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='adminemail';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		return '';
	}

	public static function getSenderMail() {
		$dbo = JFactory::getDbo();
		$q="SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='senderemail';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return empty($s[0]['setting']) ? self::getAdminMail() : $s[0]['setting'];
	}

	public static function getPaymentName($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='paymentname';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($s, '#__vikbooking_texts');
		return $s[0]['setting'];
	}

	public static function getTermsConditions($vbo_tn = null) {
		//VBO 1.10
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='termsconds';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			if (!is_object($vbo_tn)) {
				$vbo_tn = self::getTranslator();
			}
			$vbo_tn->translateContents($s, '#__vikbooking_texts');
		} else {
			//the record has never been saved. Compose it with the default lang definition
			$timeopst = self::getTimeOpenStore(true);
			if (is_array($timeopst)) {
				$openat = self::getHoursMinutes($timeopst[0]);
				$closeat = self::getHoursMinutes($timeopst[1]);
			} else {
				$openat = array(12, 0);
				$closeat = array(10, 0);
			}
			$checkin_str = ($openat[0] < 10 ? '0'.$openat[0] : $openat[0]).':'.($openat[1] < 10 ? '0'.$openat[1] : $openat[1]);
			$checkout_str = ($closeat[0] < 10 ? '0'.$closeat[0] : $closeat[0]).':'.($closeat[1] < 10 ? '0'.$closeat[1] : $closeat[1]);
			$s = array(0 => array('setting' => nl2br(JText::sprintf('VBOTERMSCONDSDEFTEXT', $checkin_str, $checkout_str))));
		}
		
		return $s[0]['setting'];
	}

	public static function getMinutesLock($conv = false) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='minuteslock';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return 0;
		}
		$s = $dbo->loadAssocList();
		if ($conv) {
			return (time() + ((int)$s[0]['setting'] * 60));
		}
		return (int)$s[0]['setting'];
	}

	public static function roomNotLocked($idroom, $units, $first, $second) {
		$dbo = JFactory::getDbo();
		$actnow = time();
		$booked = array ();
		$q = "DELETE FROM `#__vikbooking_tmplock` WHERE `until`<" . $dbo->quote($actnow) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		//vikbooking 1.1
		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		$check = "SELECT `id`,`checkin`,`realback` FROM `#__vikbooking_tmplock` WHERE `idroom`=" . $dbo->quote($idroom) . " AND `until`>=" . $dbo->quote($actnow) . ";";
		$dbo->setQuery($check);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$busy = $dbo->loadAssocList();
			foreach ($groupdays as $gday) {
				$bfound = 0;
				foreach ($busy as $bu) {
					if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
						$bfound++;
					}
				}
				if ($bfound >= $units) {
					return false;
				}
			}
		}
		//
		return true;
	}
	
	public static function getGroupDays($first, $second, $daysdiff) {
		$ret = array();
		$ret[] = $first;
		if ($daysdiff > 1) {
			$start = getdate($first);
			$end = getdate($second);
			$endcheck = mktime(0, 0, 0, $end['mon'], $end['mday'], $end['year']);
			for($i = 1; $i < $daysdiff; $i++) {
				$checkday = $start['mday'] + $i;
				$dayts = mktime(0, 0, 0, $start['mon'], $checkday, $start['year']);
				if ($dayts != $endcheck) {
					$ret[] = $dayts;
				}
			}
		}
		$ret[] = $second;
		return $ret;
	}

	/**
	 * Counts the hours of difference between the current
	 * server time and the selected check-in date and time.
	 *
	 * @param 	int 	$checkin_ts
	 * @param 	[int] 	$now_ts
	 *
	 * @return 	int
	 */
	public static function countHoursToArrival($checkin_ts, $now_ts = '') {
		$hoursdiff = 0;

		if (empty($now_ts)) {
			$now_ts = time();
		}

		if ($now_ts >= $checkin_ts) {
			return $hoursdiff;
		}

		$hoursdiff = floor(($checkin_ts - $now_ts) / 3600);

		return $hoursdiff;
	}
	
	public static function loadBusyRecords($roomids, $ts = 0) {
		$actnow = empty($ts) ? time() : $ts;
		$busy = array();
		if (!is_array($roomids) || !(count($roomids) > 0)) {
			return $busy;
		}
		$dbo = JFactory::getDbo();
		$check = "SELECT `id`,`idroom`,`checkin`,`checkout` FROM `#__vikbooking_busy` WHERE `idroom` IN (".implode(', ', $roomids).") AND `checkout` > ".$actnow.";";
		$dbo->setQuery($check);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$allbusy = $dbo->loadAssocList();
			foreach ($allbusy as $kb => $br) {
				$busy[$br['idroom']][$kb] = $br;
			}
		}
		return $busy;
	}

	/**
	 * Loads all the busy records by excluding the closures.
	 * Built for the page dashboard to calculate the actual
	 * rooms occupancy by excluding the rooms closed.
	 * 
	 * @param 	array 	$roomids 	the list of room IDs to read.
	 * @param 	int 	$ts 		the timestamp to read from.
	 * 
	 * @return 	array 	the list of busy records.
	 * 
	 * @since 	1.12
	 */
	public static function loadBusyRecordsUnclosed($roomids, $ts = 0) {
		$actnow = empty($ts) ? time() : $ts;
		$busy = array();
		if (!is_array($roomids) || !(count($roomids) > 0)) {
			return $busy;
		}
		$dbo = JFactory::getDbo();
		$check = "SELECT `b`.`id`,`b`.`idroom`,`b`.`checkin`,`b`.`checkout`,`o`.`closure` FROM `#__vikbooking_busy` AS `b` 
			LEFT JOIN `#__vikbooking_ordersbusy` AS `ob` ON `b`.`id`=`ob`.`idbusy` 
			LEFT JOIN `#__vikbooking_orders` AS `o` ON `ob`.`idorder`=`o`.`id` 
			WHERE `b`.`idroom` IN (".implode(', ', $roomids).") AND `b`.`checkout` > ".$actnow." AND `o`.`closure` != 1;";
		$dbo->setQuery($check);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$allbusy = $dbo->loadAssocList();
			foreach ($allbusy as $kb => $br) {
				$busy[$br['idroom']][$kb] = $br;
			}
		}
		return $busy;
	}

	public static function loadBookingBusyIds($idorder) {
		$busy = array();
		if (empty($idorder)) {
			return $busy;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$idorder.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$allbusy = $dbo->loadAssocList();
			foreach ($allbusy as $b) {
				array_push($busy, $b['idbusy']);
			}
		}
		return $busy;
	}

	public static function loadLockedRecords($roomids, $ts = 0) {
		$actnow = empty($ts) ? time() : $ts;
		$locked = array();
		$dbo = JFactory::getDbo();
		$q = "DELETE FROM `#__vikbooking_tmplock` WHERE `until`<" . $actnow . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!is_array($roomids) || !(count($roomids) > 0)) {
			return $locked;
		}
		$check = "SELECT `id`,`idroom`,`checkin`,`realback` FROM `#__vikbooking_tmplock` WHERE `idroom` IN (".implode(', ', $roomids).") AND `until` > ".$actnow.";";
		$dbo->setQuery($check);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$all_locked = $dbo->loadAssocList();
			foreach ($all_locked as $kb => $br) {
				$locked[$br['idroom']][$kb] = $br;
			}
		}
		return $locked;
	}

	public static function getRoomBookingsFromBusyIds($idroom, $arr_bids) {
		$bookings = array();
		if (empty($idroom) || !is_array($arr_bids) || !(count($arr_bids) > 0)) {
			return $bookings;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `ob`.`idorder`,`ob`.`idbusy` FROM `#__vikbooking_ordersbusy` AS `ob` WHERE `ob`.`idbusy` IN (".implode(',', $arr_bids).") GROUP BY `ob`.`idorder`,`ob`.`idbusy`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$all_booking_ids = $dbo->loadAssocList();
			$oids = array();
			foreach ($all_booking_ids as $bid) {
				$oids[] = $bid['idorder'];
			}
			$q = "SELECT `or`.`idorder`,CONCAT_WS(' ',`or`.`t_first_name`,`or`.`t_last_name`) AS `nominative`,`or`.`roomindex`,`o`.`status`,`o`.`days`,`o`.`checkout`,`o`.`custdata`,`o`.`country`,`o`.`closure`,`o`.`checked` ".
				"FROM `#__vikbooking_ordersrooms` AS `or` ".
				"LEFT JOIN `#__vikbooking_orders` `o` ON `o`.`id`=`or`.`idorder` ".
				"WHERE `or`.`idorder` IN (".implode(',', $oids).") AND `or`.`idroom`=".(int)$idroom." AND `o`.`status`='confirmed' ".
				"ORDER BY `o`.`checkout` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$bookings = $dbo->loadAssocList();
			}
		}
		return $bookings;
	}
	
	public static function roomBookable($idroom, $units, $first, $second, $skip_busy_ids = array()) {
		$dbo = JFactory::getDbo();
		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		$check = "SELECT `id`,`checkin`,`realback` FROM `#__vikbooking_busy` WHERE `idroom`=" . $dbo->quote($idroom) . " AND `realback`>=" . $first . ";";
		$dbo->setQuery($check);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$busy = $dbo->loadAssocList();
			foreach ($groupdays as $gday) {
				$bfound = 0;
				foreach ($busy as $bu) {
					if (in_array($bu['id'], $skip_busy_ids)) {
						//VBO 1.10 - Booking modification
						continue;
					}
					if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
						$bfound++;
					}
				}
				if ($bfound >= $units) {
					return false;
				}
			}
		}
		
		return true;
	}

	public static function payTotal() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='paytotal';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getDepositIfDays() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='depifdaysadv';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return intval($s[0]['setting']);
	}

	public static function depositAllowedDaysAdv($checkints) {
		$days_adv = self::getDepositIfDays();
		if (!($days_adv > 0) || !($checkints > 0)) {
			return true;
		}
		$now_info = getdate();
		$maxts = mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] + $days_adv), $now_info['year']);
		return $maxts > $checkints ? false : true;
	}

	public static function depositCustomerChoice() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='depcustchoice';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getDepositOverrides($getjson = false) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='depoverrides';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return $getjson ? $s : json_decode($s, true);
		}
		//count of this array will be at least 1 to store the "more" property
		$def_arr = array('more' => '');
		$def_val = json_encode($def_arr);
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('depoverrides', ".$dbo->quote($def_val).");";
		$dbo->setQuery($q);
		$dbo->execute();
		return $getjson ? $def_val : $def_arr;
	}

	public static function calcDepositOverride($amount_deposit, $nights) {
		$overrides = self::getDepositOverrides();
		$nights = intval($nights);
		$andmore = intval($overrides['more']);
		if (!(count($overrides) > 1)) {
			//no overrides
			return $amount_deposit;
		}
		foreach ($overrides as $k => $v) {
			if ((int)$k == $nights && strlen($v) > 0) {
				//exact override found
				return (float)$v;
			}
		}
		if ($andmore > 0 && $andmore <= $nights) {
			foreach ($overrides as $k => $v) {
				if ((int)$k == $andmore && strlen($v) > 0) {
					//"and more" nights found
					return (float)$v;
				}
			}
		}
		//nothing was found
		return $amount_deposit;
	}

	public static function noDepositForNonRefund() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='nodepnonrefund';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return ((int)$s == 1);
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('nodepnonrefund', '0');";
		$dbo->setQuery($q);
		$dbo->execute();
		//default to false
		return false;
	}

	/**
	 * This method returns the room-rate array with the lowest price
	 * that matches the preferred rate plan parameter (if available).
	 * The array $website_rates could not be an array, or it could be
	 * an array with the error string (response from the TACVBO Class).
	 * The method has been introduced in VBO 1.10 and it's mainly used
	 * by the module mod_vikbooking_channelrates and its ajax requests.
	 *
	 * @param 	array  		$website_rates 		the array of the website rates returned by the method fetchWebsiteRates()
	 * @param 	int  		$def_rplan 			the id of the default type of price to take for display. If empty, take the lowest rate
	 *
	 * @return 	array
	 */
	public static function getBestRoomRate($website_rates, $def_rplan) {
		if (!is_array($website_rates) || !(count($website_rates) > 0) || (is_array($website_rates) && isset($website_rates['e4j.error']))) {
			return array();
		}
		$best_room_rate = array();
		foreach ($website_rates as $rid => $tars) {
			foreach ($tars as $tar) {
				if (empty($def_rplan) || (int)$tar['idprice'] == $def_rplan) {
					//the array $website_rates is already sorted by price ASC, so we take the first useful array
					$best_room_rate = $tar;
					break 2;
				}
			}
		}
		if (!(count($best_room_rate) > 0)) {
			//the default rate plan is not available if we enter this statement, so we take the first and lowest rate
			foreach ($website_rates as $rid => $tars) {
				foreach ($tars as $tar) {
					$best_room_rate = $tar;
					break 2;
				}
			}
		}

		return $best_room_rate;
	}

	/**
	 * This method returns an array with the details
	 * of all channels in VCM that supports AV requests,
	 * and that have at least one room type mapped.
	 * The method invokes the Logos Class to return details
	 * about the name and logo URL of the channel.
	 * The method has been introduced in VBO 1.10 and it's mainly used
	 * by the module mod_vikbooking_channelrates and its ajax requests.
	 *
	 * @param 	array 	$channels 	an array of channel IDs to be mapped on the VCM relations
	 *
	 * @return 	array
	 */
	public static function getChannelsMap($channels) {
		if (!is_array($channels) || !(count($channels))) {
			return array();
		}
		$vcm_logos = self::getVcmChannelsLogo('', true);
		if (!is_object($vcm_logos)) {
			return array();
		}
		$channels_ids = array();
		foreach ($channels as $chid) {
			$ichid = (int)$chid;
			if ($ichid < 1) {
				continue;
			}
			array_push($channels_ids, $ichid);
		}
		if (!(count($channels_ids))) {
			return array();
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `idchannel`, `channel` FROM `#__vikchannelmanager_roomsxref` WHERE `idchannel` IN (".implode(', ', $channels_ids).") GROUP BY `idchannel`,`channel` ORDER BY `channel` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			return array();
		}
		$channels_names = $dbo->loadAssocList();
		$channels_map = array();
		foreach ($channels_names as $ch) {
			$ota_logo_url = $vcm_logos->setProvenience($ch['channel'])->getLogoURL();
			$ota_logo_url = $ota_logo_url === false ? '' : $ota_logo_url;
			$chdata = array(
				'id' => $ch['idchannel'],
				'name' => ucwords($ch['channel']),
				'logo' => $ota_logo_url
			);
			array_push($channels_map, $chdata);
		}
		return $channels_map;
	}

	/**
	 * This method returns a string to calculate the rates
	 * for the OTAs. Data is taken from the Bulk Rates Cache
	 * of Vik Channel Manager. The string returned contains
	 * the charge/discount operator at the position 0 (+ or -),
	 * and the percentage char (%) at the last position (if percent).
	 * Between the first and last position there is the float value.
	 * The method has been introduced in VBO 1.10 and it's mainly used
	 * by the module mod_vikbooking_channelrates and its ajax requests.
	 *
	 * @param 	array  		$best_room_rate 	array containing a specific tariff returned by getBestRoomRate()
	 *
	 * @return 	string
	 */
	public static function getOtasRatesVal($best_room_rate) {
		$otas_rates_val  = '';
		if (!(count($best_room_rate)) || !file_exists(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			return $otas_rates_val;
		}
		if (!class_exists('VikChannelManager')) {
			require_once(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php');
		}
		$bulk_rates_cache = VikChannelManager::getBulkRatesCache();
		if (count($bulk_rates_cache) && isset($best_room_rate['idprice'])) {
			if (isset($bulk_rates_cache[$best_room_rate['idroom']]) && isset($bulk_rates_cache[$best_room_rate['idroom']][$best_room_rate['idprice']])) {
				//the Bulk Rates Cache contains data for this room type and rate plan
				if ((int)$bulk_rates_cache[$best_room_rate['idroom']][$best_room_rate['idprice']]['rmod'] > 0) {
					//rates were modified for the OTAs, check how
					$rmodop = (int)$bulk_rates_cache[$best_room_rate['idroom']][$best_room_rate['idprice']]['rmodop'] > 0 ? '+' : '-';
					$rmodpcent = (int)$bulk_rates_cache[$best_room_rate['idroom']][$best_room_rate['idprice']]['rmodval'] > 0 ? '%' : '';
					$otas_rates_val = $rmodop.(float)$bulk_rates_cache[$best_room_rate['idroom']][$best_room_rate['idprice']]['rmodamount'].$rmodpcent;
				}
			}
		}

		return $otas_rates_val;
	}

	/**
	 * This method checks if some non-refundable rates were selected
	 * (`free_cancellation`=0), the only argument is an array of tariffs.
	 * The property 'idprice' must be defined on each sub-array.
	 * 
	 * @param 	$tars 		array
	 * 
	 * @return 	boolean
	 **/
	public static function findNonRefundableRates($tars) {
		$id_prices = array();
		foreach ($tars as $tar) {
			if (isset($tar['idprice'])) {
				if (!in_array($tar['idprice'], $id_prices)) {
					array_push($id_prices, (int)$tar['idprice']);
				}
				continue;
			}
			foreach ($tar as $t) {
				if (isset($t['idprice'])) {
					if (!in_array($t['idprice'], $id_prices)) {
						array_push($id_prices, (int)$t['idprice']);
					}
				}
			}
		}
		if (!(count($id_prices) > 0)) {
			//no id-prices found (probably a package)
			return false;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`name` FROM `#__vikbooking_prices` WHERE `id` IN (".implode(', ', $id_prices).") AND `free_cancellation`=0;";
		$dbo->setQuery($q);
		$dbo->execute();
		return (bool)($dbo->getNumRows() > 0);
	}

	/**
	 * This method checks if the deposit is allowed depending on
	 * the selected rate plans (idprice) for the rooms reserved.
	 * If the configuration setting is enabled, and if some
	 * non-refundable rates were selected (`free_cancellation`=0),
	 * the method will return false, because the deposit is not allowed.
	 * The only argument is an array of tariffs. The property 'idprice'
	 * must be defined on each sub-array (multi-dimension supported)
	 * throgh the method findNonRefundableRates();
	 * 
	 * @param 	$tars 		array
	 * 
	 * @return 	boolean
	 **/
	public static function allowDepositFromRates($tars) {
		if (!self::noDepositForNonRefund()) {
			//deposit can be paid also if non-refundable rates
			return true;
		}
		return !self::findNonRefundableRates($tars);
	}

	public static function showSearchSuggestions() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='searchsuggestions';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return (int)$dbo->loadResult();
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('searchsuggestions', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}
	
	public static function getCouponInfo($code) {
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_coupons` WHERE `code`=".$dbo->quote($code);
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return $dbo->loadAssoc();
		}
		return "";
	}
	
	public static function getRoomInfo($idroom) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`name`,`img`,`idcat`,`idcarat`,`info`,`smalldesc` FROM `#__vikbooking_rooms` WHERE `id`=".(int)$idroom.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			return $s[0];
		}
		return array();
	}
	
	public static function loadOrdersRoomsData($idorder) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `or`.*,`r`.`name` AS `room_name`,`r`.`params` FROM `#__vikbooking_ordersrooms` AS `or` LEFT JOIN `#__vikbooking_rooms` `r` ON `r`.`id`=`or`.`idroom` WHERE `or`.`idorder`=" . (int)$idorder . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		return $dbo->getNumRows() ? $dbo->loadAssocList() : "";
	}
	
	public static function sayCategory($ids, $vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$split = explode(";", $ids);
		$say = "";
		foreach ($split as $k => $s) {
			if (strlen($s)) {
				$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` WHERE `id`='" . $s . "';";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() < 1) {
					continue;
				}
				$nam = $dbo->loadAssocList();
				if (is_object($vbo_tn)) {
					$vbo_tn->translateContents($nam, '#__vikbooking_categories');
				}
				$say .= $nam[0]['name'];
				$say .= (strlen($split[($k +1)]) && end($split) != $s ? ", " : "");
			}
		}
		return $say;
	}

	public static function getRoomCaratOriz($idc, $vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$split = explode(";", $idc);
		$carat = "";
		$arr = array ();
		$where = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$where[] = $s;
			}
		}
		if (count($where) > 0) {
			$q = "SELECT `c`.* FROM `#__vikbooking_characteristics` AS `c` WHERE `c`.`id` IN (".implode(",", $where).") ORDER BY `c`.`ordering` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$arr = $dbo->loadAssocList();
				if (is_object($vbo_tn)) {
					$vbo_tn->translateContents($arr, '#__vikbooking_characteristics');
				}
			}
		}
		if (count($arr) > 0) {
			$carat .= "<div class=\"vbo-room-carats\">\n";
			foreach ($arr as $a) {
				if (!empty($a['textimg'])) {
					//tooltip icon text is not empty
					if (!empty($a['icon'])) {
						//an icon has been uploaded: display the image
						$carat .= "<span class=\"vbo-room-carat\"><span class=\"vbo-expl\" data-vbo-expl=\"".$a['textimg']."\"><img src=\"".VBO_SITE_URI."resources/uploads/".$a['icon']."\" alt=\"" . $a['name'] . "\" /></span></span>\n";
					} else {
						if (strpos($a['textimg'], '</i>') !== false) {
							//the tooltip icon text is a font-icon, we can use the name as tooltip
							$carat .= "<span class=\"vbo-room-carat\"><span class=\"vbo-expl\" data-vbo-expl=\"".$a['name']."\">".$a['textimg']."</span></span>\n";
						} else {
							//display just the text
							$carat .= "<span class=\"vbo-room-carat\">".$a['textimg']."</span>\n";
						}
					}
				} else {
					$carat .= (!empty($a['icon']) ? "<span class=\"vbo-room-carat\"><img src=\"".VBO_SITE_URI."resources/uploads/" . $a['icon'] . "\" alt=\"" . $a['name'] . "\" title=\"" . $a['name'] . "\"/></span>\n" : "<span class=\"vbo-room-carat\">".$a['name']."</span>\n");
				}
			}
			$carat .= "</div>\n";
		}
		return $carat;
	}

	public static function getRoomOptionals($idopts, $vbo_tn = null) {
		$split = explode(";", $idopts);
		$dbo = JFactory::getDbo();
		$arr = array ();
		$fetch = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$fetch[] = $s;
			}
		}
		if (count($fetch) > 0) {
			$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id` IN (".implode(", ", $fetch).") ORDER BY `#__vikbooking_optionals`.`ordering` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$arr = $dbo->loadAssocList();
				if (is_object($vbo_tn)) {
					$vbo_tn->translateContents($arr, '#__vikbooking_optionals');
				}
				return $arr;
			}
		}
		return "";
	}

	public static function getSingleOption($idopt, $vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$opt = array();
		if (!empty($idopt)) {
			$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . (int)$idopt . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$opt = $dbo->loadAssoc();
				if (is_object($vbo_tn)) {
					$vbo_tn->translateContents($opt, '#__vikbooking_optionals');
				}
			}
		}
		return $opt;
	}

	/**
	 * Unsets the options that are not available in the requested dates.
	 *
	 * @param 	array 	$optionals 	the array of options passed by reference
	 * @param 	int 	$checkin 	the timestamp of the check-in date and time
	 * @param 	int 	$checkout 	the timestamp of the check-out date and time
	 *
	 * @return 	void
	 *
	 * @since 	1.11
	 */
	public static function filterOptionalsByDate(&$optionals, $checkin, $checkout) {
		if (!is_array($optionals) || !count($optionals) || empty($checkin) || empty($checkout)) {
			return;
		}
		foreach ($optionals as $k => $v) {
			if (!empty($v['alwaysav'])) {
				$dates = explode(';', $v['alwaysav']);
				if (empty($dates[0]) || empty($dates[1])) {
					continue;
				}
				// it is sufficient that the check-in is included within the validity dates, we ignore the checkout
				if (!($checkin >= $dates[0]) || !($checkin <= $dates[1])) {
					unset($optionals[$k]);
				}
			}
		}
		if (!count($optionals)) {
			// the system requires it to be an empty string
			$optionals = '';
		}
	}

	/**
	 * Unsets the options that are not suited for the requested room party.
	 *
	 * @param 	array 	$optionals 	the array of options passed by reference.
	 * @param 	int 	$adults 	the number of adults in the room party.
	 * @param 	int 	$children 	the number of children in the room party.
	 *
	 * @return 	void
	 *
	 * @since 	1.13.5
	 */
	public static function filterOptionalsByParty(&$optionals, $adults, $children) {
		if (!is_array($optionals) || !count($optionals) || (empty($adults) && empty($children))) {
			return;
		}
		foreach ($optionals as $k => $v) {
			if (empty($v['oparams'])) {
				continue;
			}
			$v['oparams'] = json_decode($v['oparams'], true);
			if (!is_array($v['oparams']) || !count($v['oparams']) || (empty($v['oparams']['minguestsnum']) && empty($v['oparams']['maxguestsnum']))) {
				continue;
			}
			if (!empty($v['oparams']['minguestsnum'])) {
				// filter by minimum adults/guests
				if ($v['oparams']['mingueststype'] == 'adults' && $adults <= $v['oparams']['minguestsnum']) {
					// minimum number of adults not sufficient, unset option
					unset($optionals[$k]);
				} elseif ($v['oparams']['mingueststype'] == 'guests' && ($adults + $children) <= $v['oparams']['minguestsnum']) {
					// minimum number of total guests not sufficient, unset option
					unset($optionals[$k]);
				}
			}
			if (!empty($v['oparams']['maxguestsnum'])) {
				// filter by maximum adults/guests
				if ($v['oparams']['maxgueststype'] == 'adults' && $adults >= $v['oparams']['maxguestsnum']) {
					// maximum number of adults exceeded, unset option
					unset($optionals[$k]);
				} elseif ($v['oparams']['maxgueststype'] == 'guests' && ($adults + $children) >= $v['oparams']['maxguestsnum']) {
					// maximum number of total guests exceeded, unset option
					unset($optionals[$k]);
				}
			}
		}
		if (!count($optionals)) {
			// the system requires it to be an empty string
			$optionals = '';
		}
	}
	
	public static function getMandatoryTaxesFees($id_rooms, $num_adults, $num_nights) {
		$dbo = JFactory::getDbo();
		$taxes = 0;
		$fees = 0;
		$options_data = array();
		$id_options = array();
		$q = "SELECT `id`,`idopt` FROM `#__vikbooking_rooms` WHERE `id` IN (".implode(", ", $id_rooms).");";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$assocs = $dbo->loadAssocList();
			foreach ($assocs as $opts) {
				if (!empty($opts['idopt'])) {
					$r_ido = explode(';', rtrim($opts['idopt']));
					foreach ($r_ido as $ido) {
						if (!empty($ido) && !in_array($ido, $id_options)) {
							$id_options[] = $ido;
						}
					}
				}
			}
		}
		if (count($id_options) > 0) {
			$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id` IN (".implode(", ", $id_options).") AND `forcesel`=1 AND `ifchildren`=0 AND (`is_citytax`=1 OR `is_fee`=1);";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$alltaxesfees = $dbo->loadAssocList();
				foreach ($alltaxesfees as $tf) {
					$realcost = (intval($tf['perday']) == 1 ? ($tf['cost'] * $num_nights) : $tf['cost']);
					if (!empty($tf['maxprice']) && $tf['maxprice'] > 0 && $realcost > $tf['maxprice']) {
						$realcost = $tf['maxprice'];
					}
					$realcost = $tf['perperson'] == 1 ? ($realcost * $num_adults) : $realcost;
					$realcost = self::sayOptionalsPlusIva($realcost, $tf['idiva']);
					if ($tf['is_citytax'] == 1) {
						$taxes += $realcost;
					} elseif ($tf['is_fee'] == 1) {
						$fees += $realcost;
					}
					$optsett = explode('-', $tf['forceval']);
					$options_data[] = $tf['id'].':'.$optsett[0];
				}
			}
		}
		return array('city_taxes' => $taxes, 'fees' => $fees, 'options' => $options_data);
	}
	
	/**
	 * From a list of option/extras records, it returns an array with the options/extras
	 * that do not support age intervals, and options for the children age intervals.
	 * To be used as list($optionals, $ageintervals) = VikBooking::loadOptionAgeIntervals($optionals).
	 * 
	 * @param 	array 	$optionals 	the full list of options/extras records.
	 * @param 	int 	$adults 	the number of adults in the room requested.
	 * @param 	int 	$children 	the number of children in the room requested.
	 * 
	 * @return 	array 	the filtered regular and children age intervals options.
	 */
	public static function loadOptionAgeIntervals($optionals, $adults = null, $children = null) {
		// container for age intervals is an empty string by default
		$ageintervals = '';

		// regular options should start as an array
		if (!is_array($optionals)) {
			$optionals = array();
		}
		
		// check for the first valid age intervals option
		foreach ($optionals as $kopt => $opt) {
			if (!empty($opt['ageintervals'])) {
				$intervals = explode(';;', $opt['ageintervals']);
				foreach($intervals as $intv) {
					if (empty($intv)) {
						continue;
					}
					$parts = explode('_', $intv);
					if (count($parts) >= 3) {
						// age intervals option found, get the first and only one
						$ageintervals = $optionals[$kopt];
						break 2;
					}
				}
			}
		}
		
		if (is_array($ageintervals)) {
			/**
			 * We allow price adjustments for a minimum total guests by overriding the costs
			 * for each age interval for specific child numbers when the party covers some children.
			 * For example, when children should pay only starting from the 5th guest (minguestsnum=4),
			 * in case of a room party for 2 adults and 3 children, only the 3rd child should pay the
			 * regular fees, while the 1st and second children should have all age intervals to 0.00.
			 *
			 * @since 	1.13.5
			 */
			$oparams = !empty($ageintervals['oparams']) ? json_decode($ageintervals['oparams'], true) : array();
			$oparams = !is_array($oparams) ? array() : $oparams;
			$adults = (int)$adults;
			$children = (int)$children;
			$valid_guesttype = (!empty($oparams['mingueststype']) && $oparams['mingueststype'] == 'guests');
			if ($children > 0 && !empty($oparams['minguestsnum']) && $valid_guesttype && ($adults + $children) > $oparams['minguestsnum']) {
				$children_to_pay = ($adults + $children) - $oparams['minguestsnum'];
				if ($children_to_pay < $children) {
					// compose string for free age intervals
					$intervals = explode(';;', $ageintervals['ageintervals']);
					foreach ($intervals as $k => $v) {
						$parts = explode('_', $v);
						// set cost to 0 (key = 2)
						$parts[2] = '0';
						$intervals[$k] = implode('_', $parts);
					}
					$free_ageintervals = implode(';;', $intervals);
					for ($i = 1; $i <= $children; $i++) {
						if (($children - $i + 1) <= $children_to_pay) {
							// costs for this Nth last child should be regular as this child should pay
							$ageintervals['ageintervals_child' . $i] = $ageintervals['ageintervals'];
						} else {
							// override costs to 0.00 for this Nth first child since it's covered by the min guests number
							$ageintervals['ageintervals_child' . $i] = $free_ageintervals;
						}
					}
				}
			}
			//

			// remove age intervals from regular options
			foreach ($optionals as $kopt => $opt) {
				if ($opt['id'] == $ageintervals['id'] || !empty($opt['ageintervals'])) {
					// unset the option of type age intervals from the regular options
					unset($optionals[$kopt]);
				}
			}
		}

		// when no more regular options, we return an empty string rather than an array
		if (!count($optionals)) {
			$optionals = '';
		}
		
		// return the filtered list of regular options and age intervals options
		return array($optionals, $ageintervals);
	}

	/**
	 * Returns an array of overrides (if any) for the specific children Nth number in the party.
	 * This is because some children in the party may not need to pay anything due to the min guests.
	 * 
	 * @param 	array 	$optional	the option full record to parse.
	 * @param 	int 	$adults 	the number of adults in the room requested.
	 * @param 	int 	$children 	the number of children in the room requested.
	 * 
	 * @return 	array 	associative array with default age intervals and override strings.
	 * 
	 * @since 	1.13.5
	 */
	public static function getOptionIntervalChildOverrides($optional, $adults, $children)
	{
		if (!is_array($optional)) {
			$optional = array();
		}

		$overrides = array(
			'ageintervals' => (!empty($optional['ageintervals']) ? $optional['ageintervals'] : '')
		);

		$oparams = !empty($optional['oparams']) ? json_decode($optional['oparams'], true) : array();
		$oparams = !is_array($oparams) ? array() : $oparams;
		$adults = (int)$adults;
		$children = (int)$children;

		if (!count($oparams)) {
			return $overrides;
		}

		$valid_guesttype = (!empty($oparams['mingueststype']) && $oparams['mingueststype'] == 'guests');
		if ($children > 0 && !empty($oparams['minguestsnum']) && $valid_guesttype && ($adults + $children) > $oparams['minguestsnum']) {
			$children_to_pay = ($adults + $children) - $oparams['minguestsnum'];
			if ($children_to_pay < $children) {
				// compose string for free age intervals
				$intervals = explode(';;', $optional['ageintervals']);
				foreach ($intervals as $k => $v) {
					$parts = explode('_', $v);
					// set cost to 0 (key = 2)
					$parts[2] = '0';
					$intervals[$k] = implode('_', $parts);
				}
				$free_ageintervals = implode(';;', $intervals);
				for ($i = 1; $i <= $children; $i++) {
					if (($children - $i + 1) <= $children_to_pay) {
						// costs for this Nth last child should be regular as this child should pay
						$overrides['ageintervals_child' . $i] = $optional['ageintervals'];
					} else {
						// override costs to 0.00 for this Nth first child since it's covered by the min guests number
						$overrides['ageintervals_child' . $i] = $free_ageintervals;
					}
				}
			}
		}

		return $overrides;
	}

	/**
	 * Returns the number/index of the children being parsed given the full list of room options string,
	 * the current key in the array of the split room options string, the ID of the ageintervals option,
	 * and the total number of children. This is useful for later applying the cost overrides for the
	 * children ages through the method getOptionIntervalChildOverrides().
	 * 
	 * @param 	string 	$roptstr	plain room option string of "#__vikbooking_ordersrooms".
	 * @param 	int 	$optid 		the ID of the option of type age intervals to check.
	 * @param 	int 	$roptkey 	the current position in the loop of the room options string.
	 * @param 	int 	$children 	the number of children in the room requested.
	 * 
	 * @return 	int 	the position/number of the children being parsed, -1 if not found.
	 * 
	 * @since 	1.13.5
	 */
	public static function getRoomOptionChildNumber($roptstr, $optid, $roptkey, $children)
	{
		// default to -1 for not found
		$child_num = -1;

		$valid_opt_counter = 0;

		$roptions = explode(";", $roptstr);
		foreach ($roptions as $k => $opt) {
			if (empty($opt)) {
				continue;
			}
			$optvals = explode(":", $opt);
			/**
			 * In some cases, like the "saveorder" task, the room option string may contain
			 * the room number beside the option ID, separated with an underscore.
			 * If an underscore is present, we need to split it to find the option ID.
			 */
			if (strpos($optvals[0], '_') !== false) {
				// underscore found in room number portion, extract the option ID
				$rn_parts = explode('_', $optvals[0]);
				// 0th element is the room number in the party, 1st elem is the option ID
				$optvals[0] = $rn_parts[1];
			}
			//
			if ((int)$optvals[0] != (int)$optid) {
				// we are not interested into this option ID
				continue;
			}
			// increase counter for this option ID
			$valid_opt_counter++;

			if ((int)$k == (int)$roptkey && $valid_opt_counter <= $children) {
				// children position found, we need to return it as a 0th base
				$child_num = ($valid_opt_counter - 1);
				break;
			}
		}

		return $child_num;
	}
	
	public static function getOptionIntervalsCosts($intvstr) {
		$optcosts = array();
		$intervals = explode(';;', $intvstr);
		foreach ($intervals as $kintv => $intv) {
			if (empty($intv)) continue;
			$parts = explode('_', $intv);
			if (count($parts) >= 3) {
				$optcosts[$kintv] = (float)$parts[2];
			}
		}
		return $optcosts;
	}
	
	public static function getOptionIntervalsAges($intvstr) {
		$optages = array();
		$intervals = explode(';;', $intvstr);
		foreach ($intervals as $kintv => $intv) {
			if (empty($intv)) continue;
			$parts = explode('_', $intv);
			if (count($parts) >= 3) {
				$optages[$kintv] = $parts[0].' - '.$parts[1];
			}
		}
		return $optages;
	}

	public static function getOptionIntervalsPercentage($intvstr) {
		/* returns an associative array to tell whether an interval has a percentage cost (VBO 1.8) */
		$optcostspcent = array();
		$intervals = explode(';;', $intvstr);
		foreach ($intervals as $kintv => $intv) {
			if (empty($intv)) continue;
			$parts = explode('_', $intv);
			if (count($parts) >= 3) {
				//fixed amount
				$setval = 0;
				if (array_key_exists(3, $parts) && strpos($parts[3], '%b') !== false) {
					//percentage value of the room base cost (VBO 1.10)
					$setval = 2;
				} elseif (array_key_exists(3, $parts) && strpos($parts[3], '%') !== false) {
					//percentage value of the adults tariff
					$setval = 1;
				}
				$optcostspcent[$kintv] = $setval;
			}
		}
		return $optcostspcent;
	}

	public static function dayValidTs($days, $first, $second) {
		$secdiff = $second - $first;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		return ($daysdiff == $days ? true : false);
	}

	public static function sayCostPlusIva($cost, $idprice) {
		$dbo = JFactory::getDbo();
		$ivainclusa = self::ivaInclusa();
		if (intval($ivainclusa) == 0) {
			$q = "SELECT `p`.`idiva`,`i`.`aliq`,`i`.`taxcap` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` `i` ON `i`.`id`=`p`.`idiva` WHERE `p`.`id`=" . (int)$idprice . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$paliq = $dbo->loadAssocList();
				if (!empty($paliq[0]['aliq'])) {
					$subt = 100 + $paliq[0]['aliq'];
					$op = ($cost * $subt / 100);
					/**
					 * Tax Cap implementation for prices tax excluded (most common).
					 * 
					 * @since 	1.12
					 */
					if ($paliq[0]['taxcap'] > 0 && ($op - $cost) > $paliq[0]['taxcap']) {
						$op = ($cost + $paliq[0]['taxcap']);
					}
					// VBO 1.10 - apply rounding to avoid issues with multiple tax rates when tax excluded
					$formatvals = self::getNumberFormatData();
					$formatparts = explode(':', $formatvals);
					$rounded_op = round($op, (int)$formatparts[0]);
					if ($rounded_op > $op) {
						return $rounded_op;
					}
					/**
					 * When using base costs with decimals, and no tax rates assigned, maybe for a foreigners rate plan,
					 * no rounding is ever made, and so we should always apply such rounding to avoid getting decimals if they should be 0.
					 * 
					 * @since 	1.11.1
					 */
					return round($op, (int)$formatparts[0]);
				}
			}
		}
		return $cost;
	}

	public static function sayCostMinusIva($cost, $idprice) {
		$dbo = JFactory::getDbo();
		$ivainclusa = self::ivaInclusa();
		if (intval($ivainclusa) == 1) {
			$q = "SELECT `p`.`idiva`,`i`.`aliq`,`i`.`taxcap` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` `i` ON `i`.`id`=`p`.`idiva` WHERE `p`.`id`=" . (int)$idprice . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$paliq = $dbo->loadAssocList();
				if (!empty($paliq[0]['aliq'])) {
					$subt = 100 + $paliq[0]['aliq'];
					$op = ($cost * 100 / $subt);
					/**
					 * Tax Cap implementation also when prices tax included.
					 * 
					 * @since 	1.12
					 */
					if ($paliq[0]['taxcap'] > 0 && ($cost - $op) > $paliq[0]['taxcap']) {
						$op = ($cost - $paliq[0]['taxcap']);
					}
					// VBO 1.10 - apply rounding to avoid issues with multiple tax rates when tax included
					$formatvals = self::getNumberFormatData();
					$formatparts = explode(':', $formatvals);
					$rounded_op = round($op, (int)$formatparts[0]);
					if ($rounded_op < $op) {
						return $rounded_op;
					}
					/**
					 * When using base costs with decimals, and no tax rates assigned, maybe for a foreigners rate plan,
					 * no rounding is ever made, and so we should always apply such rounding to avoid getting decimals if they should be 0.
					 * 
					 * @since 	1.11.1
					 */
					return round($op, (int)$formatparts[0]);
				}
			}
		}
		return $cost;
	}

	public static function sayOptionalsPlusIva($cost, $idiva) {
		// this method can also be used to calculate taxes on the extra costs per room in the bookings
		if (empty($idiva)) {
			return $cost;
		}
		$dbo = JFactory::getDbo();
		$ivainclusa = self::ivaInclusa();
		if (intval($ivainclusa) == 0) {
			$q = "SELECT `aliq`,`taxcap` FROM `#__vikbooking_iva` WHERE `id`=" . (int)$idiva . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$piva = $dbo->loadAssocList();
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * $subt / 100);
				/**
				 * Tax Cap implementation for prices tax excluded (most common).
				 * 
				 * @since 	1.12
				 */
				if ($piva[0]['taxcap'] > 0 && ($op - $cost) > $piva[0]['taxcap']) {
					$op = ($cost + $piva[0]['taxcap']);
				}
				// VBO 1.10 - apply rounding to avoid issues with multiple tax rates when tax excluded
				$formatvals = self::getNumberFormatData();
				$formatparts = explode(':', $formatvals);
				$rounded_op = round($op, (int)$formatparts[0]);
				if ($rounded_op > $op) {
					return $rounded_op;
				}
				//
				return $op;
			}
		}
		return $cost;
	}

	public static function sayOptionalsMinusIva($cost, $idiva) {
		// this method can also be used to calculate taxes on the extra costs per room in the bookings
		if (empty($idiva)) {
			return $cost;
		}
		$dbo = JFactory::getDbo();
		$ivainclusa = self::ivaInclusa();
		if (intval($ivainclusa) == 1) {
			$q = "SELECT `aliq`,`taxcap` FROM `#__vikbooking_iva` WHERE `id`=" . (int)$idiva . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$piva = $dbo->loadAssocList();
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * 100 / $subt);
				/**
				 * Tax Cap implementation also when prices tax included.
				 * 
				 * @since 	1.12
				 */
				if ($piva[0]['taxcap'] > 0 && ($cost - $op) > $piva[0]['taxcap']) {
					$op = ($cost - $piva[0]['taxcap']);
				}
				// VBO 1.10 - apply rounding to avoid issues with multiple tax rates when tax included
				$formatvals = self::getNumberFormatData();
				$formatparts = explode(':', $formatvals);
				$rounded_op = round($op, (int)$formatparts[0]);
				if ($rounded_op < $op) {
					return $rounded_op;
				}
				//
				return $op;
			}
		}
		return $cost;
	}

	public static function sayPackagePlusIva($cost, $idiva) {
		$dbo = JFactory::getDbo();
		$ivainclusa = self::ivaInclusa();
		if (intval($ivainclusa) == 0) {
			$q = "SELECT `aliq`,`taxcap` FROM `#__vikbooking_iva` WHERE `id`=" . (int)$idiva . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$piva = $dbo->loadAssocList();
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * $subt / 100);
				/**
				 * Tax Cap implementation for prices tax excluded (most common).
				 * 
				 * @since 	1.12
				 */
				if ($piva[0]['taxcap'] > 0 && ($op - $cost) > $piva[0]['taxcap']) {
					$op = ($cost + $piva[0]['taxcap']);
				}
				// VBO 1.10 - apply rounding to avoid issues with multiple tax rates when tax excluded
				$formatvals = self::getNumberFormatData();
				$formatparts = explode(':', $formatvals);
				$rounded_op = round($op, (int)$formatparts[0]);
				if ($rounded_op > $op) {
					return $rounded_op;
				}
				//
				return $op;
			}
		}
		return $cost;
	}

	public static function sayPackageMinusIva($cost, $idiva, $force_invoice_excltax = false) {
		$dbo = JFactory::getDbo();
		$ivainclusa = self::ivaInclusa();
		if (intval($ivainclusa) == 1 || ($force_invoice_excltax === true && intval($ivainclusa) < 1)) {
			$q = "SELECT `aliq`,`taxcap` FROM `#__vikbooking_iva` WHERE `id`=" . (int)$idiva . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$piva = $dbo->loadAssocList();
				$subt = 100 + $piva[0]['aliq'];
				$op = ($cost * 100 / $subt);
				/**
				 * Tax Cap implementation also when prices tax included.
				 * 
				 * @since 	1.12
				 */
				if ($piva[0]['taxcap'] > 0 && ($cost - $op) > $piva[0]['taxcap']) {
					$op = ($cost - $piva[0]['taxcap']);
				}
				// VBO 1.10 - apply rounding to avoid issues with multiple tax rates when tax included
				$formatvals = self::getNumberFormatData();
				$formatparts = explode(':', $formatvals);
				$rounded_op = round($op, (int)$formatparts[0]);
				if ($rounded_op < $op) {
					return $rounded_op;
				}
				//
				return $op;
			}
		}
		return $cost;
	}
	
	public static function getSecretLink() {
		$sid = mt_rand();
		$dbo = JFactory::getDbo();
		$q = "SELECT `sid` FROM `#__vikbooking_orders`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$all = $dbo->loadAssocList();
			$arr = array();
			foreach ($all as $s) {
				$arr[] = $s['sid'];
			}
			if (in_array($sid, $arr)) {
				while (in_array($sid, $arr)) {
					$sid++;
				}
			}
		}
		return $sid;
	}
	
	public static function generateConfirmNumber($oid, $update = true) {
		$confirmnumb = date('ym');
		$confirmnumb .= (string)rand(100, 999);
		$confirmnumb .= (string)rand(10, 99);
		$confirmnumb .= (string)$oid;
		if ($update) {
			$dbo = JFactory::getDbo();
			$q="UPDATE `#__vikbooking_orders` SET `confirmnumber`='".$confirmnumb."' WHERE `id`='".$oid."';";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		return $confirmnumb;
	}
	
	public static function buildCustData($arr, $sep) {
		$cdata = "";
		foreach ($arr as $k => $e) {
			if (strlen($e)) {
				$cdata .= (strlen($k) > 0 ? $k . ": " : "") . $e . $sep;
			}
		}
		return $cdata;
	}

	/**
	 * This method parses the Joomla menu object
	 * to see if a menu item of a specific type
	 * is available, to get its ID.
	 * Useful when links must be displayed in pages where
	 * there is no Itemid set (booking details pages).
	 *
	 * @param 	array  		$viewtypes 		list of accepted menu items
	 *
	 * @return 	int
	 */
	public static function findProperItemIdType($viewtypes) {
		/**
		 * @wponly  JApplication::getMenu() cannot be adjusted to WP
		 */
		return 0;
	}

	/**
	 * Rewrites an internal URI that needs to be used outside of the website.
	 * This means that the routed URI MUST start with the base path of the site.
	 *
	 * @param 	mixed 	 $query 	The query string or an associative array of data.
	 * @param 	boolean  $xhtml  	Replace & by &amp; for XML compliance.
	 * @param 	mixed 	 $itemid 	The itemid to use. If null, the current one will be used.
	 *
	 * @return 	string 	The complete routed URI.
	 */
	public static function externalroute($query = '', $xhtml = true, $itemid = null) {
		$app = JFactory::getApplication();

		if (is_array($query)) {
			// the query is an array, build the query string
			$query_str = 'index.php';

			// make sure the array is not empty
			if ($query) {
				$query_str .= '?' . http_build_query($query);
			}

			$query = $query_str;
		}

		/**
		 * @wponly 	guess the view name from the query, useful for routing the URI
		 */
		$uri_view = array('');
		$uri_data = parse_url($query);
		if (!empty($uri_data['query']) && strpos($uri_data['query'], 'view=') !== false) {
			$query_parts = explode('view=', $uri_data['query']);
			$view_name 	 = $query_parts[1];
			$amp_pos 	 = strpos($view_name, '&');
			if ($amp_pos !== false) {
				$view_name = substr($view_name, 0, $amp_pos);
			}
			$uri_view = trim($view_name);
		}
		//

		if (is_null($itemid) && self::isSite()) {
			// no item id, get it from the request
			$itemid = $app->input->getInt('Itemid', 0);

			/**
			 * @wponly 	Itemid is mandatory to route a valid URI
			 */
			if (!$itemid) {
				// get Itemid from the Shortcodes model
				$model 	= JModel::getInstance('vikbooking', 'shortcodes', 'admin');
				$itemid = $model->best($uri_view);
			}
			//
		} elseif (is_null($itemid) && self::isAdmin()) {
			/**
			 * @wponly 	URIs can be routed also in the admin section, so the Itemid is important
			 */
			// get itemid from the Shortcodes model
			$model 	= JModel::getInstance('vikbooking', 'shortcodes');
			$itemid = $model->best($uri_view);
			//
		}

		if ($itemid) {
			if ($query) {
				// check if the query string contains a '?'
				if (strpos($query, '?') !== false) {
					// the query already starts with 'index.php?' or '?'
					$query .= '&';
				} else {
					// the query string is probably equals to 'index.php'
					$query .= '?';
				}
			} else {
				// empty query, create the default string
				$query = 'index.php?';
			}

			// the item id is set, append it at the end of the query string
			$query .= 'Itemid=' . $itemid;
		}

		/**
		 * @wponly 	JRoute already prepends base URI
		 */
		// route the query string, base URI will be prepended by JRoute::rewrite()
		$uri = JRoute::rewrite($query, $xhtml);

		// remove wp-admin/ from URL in case this method is called from admin
		if (self::isAdmin() && strpos($uri, 'wp-admin/') !== false) {
			$adminPos 	= strrpos($uri, 'wp-admin/');
			$uri 		= substr_replace($uri, '', $adminPos, 9);
		}
		//

		return $uri;
	}

	public static function sendAdminMail($to, $subject, $ftitle, $ts, $custdata, $rooms, $first, $second, $pricestr, $optstr, $tot, $status, $payname = "", $couponstr = "", $arrpeople = "", $confirmnumber = "") {
		$sendwhen = self::getSendEmailWhen();
		if ($sendwhen > 1 && $status == JText::translate('VBINATTESA')) {
			return true;
		}
		$emailparts = explode(';_;', $to);
		$to = $emailparts[0];
		if (!is_array($to) && strpos($to, ',') !== false) {
			$all_recipients = explode(',', $to);
			foreach ($all_recipients as $k => $v) {
				if (empty($v)) {
					unset($all_recipients[$k]);
				}
			}
			if (count($all_recipients) > 0) {
				$to = $all_recipients;
			}
		}
		if (empty($to)) {
			//Prevent Joomla Exceptions that would stop the script execution
			VikError::raiseWarning('', 'The administrator email address is empty. Email message could not be sent.');
			return false;
		}
		$replyto = isset($emailparts[1]) ? $emailparts[1] : '';
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencyname';";
		$dbo->setQuery($q);
		$dbo->execute();
		$currencyname = $dbo->loadResult();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$formdate = $dbo->loadResult();
		if ($formdate == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($formdate == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator();
		$roomsnum = count($rooms);
		$msg = $ftitle . "\n\n";
		$msg .= JText::translate('VBLIBONE') . " " . date(str_replace("/", $datesep, $df) . ' H:i', $ts) . "\n";
		$msg .= JText::translate('VBLIBTWO') . ":\n" . $custdata . "\n";
		$msg .= JText::translate('VBLIBTHREE') . ": " . $roomsnum . "\n";
		$msg .= JText::translate('VBLIBFOUR') . " " . date(str_replace("/", $datesep, $df) . ' H:i', $first) . "\n";
		$msg .= JText::translate('VBLIBFIVE') . " " . date(str_replace("/", $datesep, $df) . ' H:i', $second) . "\n\n";
		foreach($rooms as $num => $r) {
			$msg .= ($roomsnum > 1 ? JText::translate('VBMAILROOMNUM')."".$num.": " : "").$r['name'];
			//Rooms Distinctive Features
			$distinctive_features = array();
			$rparams = json_decode($r['params'], true);
			if (array_key_exists('features', $rparams) && count($rparams['features']) > 0 && array_key_exists('roomindex', $r) && !empty($r['roomindex']) && array_key_exists($r['roomindex'], $rparams['features'])) {
				$distinctive_features = $rparams['features'][$r['roomindex']];
			}
			if (count($distinctive_features)) {
				foreach ($distinctive_features as $dfk => $dfv) {
					if (strlen($dfv)) {
						//get the first non-empty distinctive feature of the room
						$msg .= " - ".JText::translate($dfk).': '.$dfv;
						break;
					}
				}
			}
			//
			$msg .= "\n";
			$msg .= JText::translate('VBMAILADULTS').": ".intval($arrpeople[$num]['adults']) . "\n";
			if ($arrpeople[$num]['children'] > 0) {
				$msg .= JText::translate('VBMAILCHILDREN').": ".$arrpeople[$num]['children'] . "\n";
			}
			$msg .= $pricestr[$num] . "\n";
			$allopts = "";
			if (isset($optstr[$num]) && is_array($optstr[$num]) && count($optstr[$num]) > 0) {
				foreach($optstr[$num] as $oo) {
					$expopts = explode("\n", $oo);
					foreach($expopts as $kopt => $optinfo) {
						if (!empty($optinfo)) {
							$splitopt = explode(":", $optinfo);
							$optprice = trim(str_replace($currencyname, "", $splitopt[1]));
							$optinfo = $splitopt[0].': '.self::numberFormat($optprice)." $currencyname";
							$expopts[$kopt] = $optinfo;
						}
					}
					$oo = implode("\n", $expopts);
					$allopts .= $oo;
				}
			}
			$msg .= $allopts . "\n";
		}
		//vikbooking 1.1 coupon
		if (strlen($couponstr) > 0) {
			$expcoupon = explode(";", $couponstr);
			$msg .= JText::translate('VBCOUPON')." ".$expcoupon[2].": -" . $expcoupon[1] . " " . $currencyname . "\n\n";
		}
		//
		$msg .= JText::translate('VBLIBSIX') . ": " . self::numberFormat($tot) . " " . $currencyname . "\n\n";
		if (!empty($payname)) {
			$msg .= JText::translate('VBLIBPAYNAME') . ": " . $payname . "\n\n";
		}
		$msg .= JText::translate('VBLIBSEVEN') . ": " . $status;
		
		//Confirmation Number
		if (strlen($confirmnumber) > 0) {
			$msg .= "\n\n".JText::translate('VBCONFIRMNUMB') . ": " . $confirmnumber;
		}
		//
		//No deposit, chose to pay full amount (information sent only when the status is Pending)
		$pnodep = VikRequest::getString('nodep', '', 'request');
		if (intval($pnodep) > 0) {
			$msg .= "\n\n".JText::translate('VBCUSTCHOICEPAYFULLADMIN');
		}
		//
		
		//$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		$mailer = JFactory::getMailer();
		$adsendermail = self::getSenderMail();
		$sender = array($adsendermail, $adsendermail);
		$mailer->setSender($sender);
		$mailer->addRecipient($to);
		$mailer->addReplyTo((!empty($replyto) ? $replyto : $adsendermail));
		/**
		 * Check whether an iCal should be attached for the admin.
		 * 
		 * @since 	1.2.0
		 */
		$admin_ical = self::getEmailIcal('admin', array(
			'ts' => $ts,
			'custdata' => $custdata,
			'checkin' => $first,
			'checkout' => $second,
			'subject' => $subject,
		));
		if ($admin_ical !== false && $status != JText::translate('VBINATTESA')) {
			$mailer->addAttachment($admin_ical);
		}
		//
		$mailer->setSubject($subject);
		$mailer->setBody($msg);
		$mailer->isHTML(false);
		$mailer->Encoding = 'base64';
		$mailer->Send();

		// unlink iCal file
		if ($admin_ical !== false) {
			@unlink($admin_ical);
		}
		//
		
		return true;
	}

	/**
	 * Generates an iCal file to be attached to the email message for the
	 * customer or the administrator with some basic booking details.
	 * 
	 * @param 	string 	$recip 		either admin or customer.
	 * @param 	array 	$booking 	the booking array or some keys.
	 * 
	 * @return 	mixed 	string in case of success, false otherwise.
	 * 
	 * @since 	1.12.0
	 */
	public static function getEmailIcal($recip, $booking) {
		// load configuration setting
		$attachical = self::attachIcal();

		if ($attachical === 0) {
			// do not attach any iCal file
			return false;
		}

		if ($attachical === 2 && strpos($recip, 'admin') === false) {
			// skip the iCal for the admin
			return false;
		}

		if ($attachical === 3 && strpos($recip, 'admin') !== false) {
			// skip the iCal for the customer
			return false;
		}

		if (strpos($recip, 'admin') !== false) {
			// prepare event description and summary for the admin
			$description = $booking['custdata'];
			$summary = !empty($booking['subject']) ? $booking['subject'] : '';
			$fname = $booking['ts'] . '.ics';
		} else {
			// event description and summary for the customer
			$description = '';
			$summary = self::getFrontTitle();
			$fname = 'reservation_reminder.ics';
		}

		// prepare iCal head
		$company_name = self::getFrontTitle();
		$ics_str = "BEGIN:VCALENDAR\r\n" .
					"PRODID:-//".$company_name."//".JUri::root()." 1.0//EN\r\n" .
					"CALSCALE:GREGORIAN\r\n" .
					"VERSION:2.0\r\n";
		// compose iCal body
		$ics_str .= 'BEGIN:VEVENT'."\r\n";
		$ics_str .= 'DTEND;VALUE=DATE:'.date('Ymd', $booking['checkout'])."\r\n";
		$ics_str .= 'DTSTART;VALUE=DATE:'.date('Ymd', $booking['checkin'])."\r\n";
		$ics_str .= 'UID:'.sha1($booking['ts'])."\r\n";
		$ics_str .= 'DESCRIPTION:'.preg_replace('/([\,;])/','\\\$1', $description)."\r\n";
		$ics_str .= 'SUMMARY:'.preg_replace('/([\,;])/','\\\$1', $summary)."\r\n";
		$ics_str .= 'LOCATION:'.preg_replace('/([\,;])/','\\\$1', $company_name)."\r\n";
		$ics_str .= 'END:VEVENT'."\r\n";
		// close iCal file content
		$ics_str .= "END:VCALENDAR";

		// store the event onto a .ics file. We use the resources folder in back-end.
		$fpath = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $fname;
		$fp = fopen($fpath, 'w+');
		$bytes = fwrite($fp, $ics_str);
		fclose($fp);

		return $bytes ? $fpath : false;
	}
	
	public static function loadEmailTemplate($booking_info = array()) {
		define('_VIKBOOKINGEXEC', '1');
		ob_start();
		include VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "email_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	/**
	 * Parses the raw HTML content of the booking email template.
	 * 
	 * @param 	string 	$tmpl 		the raw content of the template.
	 * @param 	mixed 	$bid 		int for the booking ID or booking array.
	 * @param 	array 	$rooms 		list of rooms booked and translated.
	 * @param 	array 	$rates 		list of translated rates for the booked rooms.
	 * @param 	array 	$options 	list of translated options booked.
	 * @param 	float 	[$total] 	the booking total amount (in case it has changed).
	 * @param 	string 	[$link] 	the booking link can be passed for the no-deposit.
	 * 
	 * @return 	string 	the HTML content of the parsed email template.
	 * 
	 * @since 	1.13 with different arguments.
	 */
	public static function parseEmailTemplate($tmpl, $bid, $rooms, $rates, $options, $total = 0, $link = null) {
		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();
		// get necessary values
		if (is_array($bid)) {
			// we got the full booking record
			$order_info = $bid;
			$bid = $order_info['id'];
		} else {
			$order_info = array();
			$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=" . (int)$bid . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				throw new Exception('Booking not found', 404);
				
			}
			$order_info = $dbo->loadAssoc();
		}
		$tars_info = array();
		$q = "SELECT `or`.`id`,`or`.`idroom`,`or`.`idtar`,`d`.`idprice` FROM `#__vikbooking_ordersrooms` AS `or` LEFT JOIN `#__vikbooking_dispcost` AS `d` ON `or`.`idtar`=`d`.`id` WHERE `or`.`idorder`=" . (int)$order_info['id'] . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('No rooms found', 404);
		}
		$tars_info = $dbo->loadAssocList();
		//

		// check if the language in use is the same as the one used during the checkout
		$lang = JFactory::getLanguage();
		if (!empty($order_info['lang'])) {
			if ($lang->getTag() != $order_info['lang']) {
				$lang->load('com_vikbooking', (defined('VIKBOOKING_LANG') ? VIKBOOKING_LANG : JPATH_SITE), $order_info['lang'], true);
			}
			if ($vbo_tn->getDefaultLang() != $order_info['lang']) {
				// force the translation to start because contents should be translated
				$vbo_tn::$force_tolang = $order_info['lang'];
			}
		}
		//

		// values for replacements
		$company_name 	= self::getFrontTitle();
		$currencyname 	= self::getCurrencyName();
		$sitelogo 		= self::getSiteLogo();
		$footermess 	= self::getFooterOrdMail($vbo_tn);
		$dateformat 	= self::getDateFormat();
		$datesep 		= self::getDateSeparator();
		if ($dateformat == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($dateformat == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$create_date = date(str_replace("/", $datesep, $df) . ' H:i', $order_info['ts']);
		$checkin_date = date(str_replace("/", $datesep, $df) . ' H:i', $order_info['checkin']);
		$checkout_date = date(str_replace("/", $datesep, $df) . ' H:i', $order_info['checkout']);
		$customer_info = nl2br($order_info['custdata']);
		$company_logo = '';
		if (!empty($sitelogo) && is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources'. DIRECTORY_SEPARATOR . $sitelogo)) {
			$company_logo = '<img src="' . VBO_ADMIN_URI . 'resources/' . $sitelogo . '" alt="' . $company_name . '" />';
		}
		if ($order_info['status'] == 'cancelled') {
			$confirmnumber = '';
			$status_str = JText::translate('VBCANCELLED');
		} elseif ($order_info['status'] == 'standby') {
			$confirmnumber = '';
			$status_str = JText::translate('VBWAITINGFORPAYMENT');
		} else {
			$confirmnumber = $order_info['confirmnumber'];
			$status_str = JText::translate('VBCOMPLETED');
		}
		// booking total amount
		$total = $total === 0 ? (float)$order_info['total'] : (float)$total;
		// booking link
		$use_sid = !empty($order_info['idorderota']) && !empty($order_info['channel']) ? $order_info['idorderota'] : $order_info['sid'];
		if (is_null($link)) {
			$link = self::externalroute("index.php?option=com_vikbooking&view=booking&sid={$use_sid}&ts={$order_info['ts']}", false);
		}

		// raw HTML content
		$parsed = $tmpl;

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($order_info, $rooms))
			->parseTokens($parsed);
		//
		
		// special tokens (tags) replacement
		$parsed = str_replace("{logo}", $company_logo, $parsed);
		$parsed = str_replace("{company_name}", $company_name, $parsed);
		$parsed = str_replace("{order_id}", $order_info['id'], $parsed);
		$statusclass = $order_info['status'] == 'confirmed' ? "confirmed" : "standby";
		$statusclass = $order_info['status'] == 'cancelled' ? "cancelled" : $statusclass;
		$parsed = str_replace("{order_status_class}", $statusclass, $parsed);
		$parsed = str_replace("{order_status}", $status_str, $parsed);
		$parsed = str_replace("{order_date}", $create_date, $parsed);
		// PIN Code
		if ($order_info['status'] == 'confirmed' && self::customersPinEnabled()) {
			$cpin = self::getCPinIstance();
			$customer_pin = $cpin->getPinCodeByOrderId($order_info['id']);
			if (!empty($customer_pin)) {
				$customer_info .= '<h3>'.JText::translate('VBYOURPIN').': '.$customer_pin.'</h3>';
			}
		}
		//
		$parsed = str_replace("{customer_info}", $customer_info, $parsed);
		// Confirmation Number
		if (strlen($confirmnumber) > 0) {
			$parsed = str_replace("{confirmnumb}", $confirmnumber, $parsed);
		} else {
			$parsed = preg_replace('#('.preg_quote('{confirmnumb_delimiter}').')(.*)('.preg_quote('{/confirmnumb_delimiter}').')#si', '$1'.' '.'$3', $parsed);
		}
		$parsed = str_replace("{confirmnumb_delimiter}", "", $parsed);
		$parsed = str_replace("{/confirmnumb_delimiter}", "", $parsed);
		//
		$roomsnum = count($rooms);
		$parsed = str_replace("{rooms_count}", $roomsnum, $parsed);
		$roomstr = "";
		// Rooms Distinctive Features
		preg_match_all('/\{roomfeature ([a-zA-Z0-9 ]+)\}/U', $parsed, $matches);
		//
		foreach ($rooms as $num => $r) {
			$roomstr .= "<strong>".$r['name']."</strong> ".$r['adults']." ".($r['adults'] > 1 ? JText::translate('VBMAILADULTS') : JText::translate('VBMAILADULT')).($r['children'] > 0 ? ", ".$r['children']." ".($r['children'] > 1 ? JText::translate('VBMAILCHILDREN') : JText::translate('VBMAILCHILD')) : "")."<br/>";
			// Rooms Distinctive Features
			if (is_array($matches[1]) && @count($matches[1]) > 0) {
				$distinctive_features = array();
				$rparams = json_decode($r['params'], true);
				if (array_key_exists('features', $rparams) && count($rparams['features']) > 0 && array_key_exists('roomindex', $r) && !empty($r['roomindex']) && array_key_exists($r['roomindex'], $rparams['features'])) {
					$distinctive_features = $rparams['features'][$r['roomindex']];
				}
				$docheck = (count($distinctive_features) > 0);
				foreach($matches[1] as $reqf) {
					$feature_found = false;
					if ($docheck) {
						foreach ($distinctive_features as $dfk => $dfv) {
							if (stripos($dfk, $reqf) !== false) {
								$feature_found = $dfk;
								if (strlen(trim($dfk)) == strlen(trim($reqf))) {
									break;
								}
							}
						}
					}
					if ($feature_found !== false && strlen($distinctive_features[$feature_found]) > 0) {
						$roomstr .= JText::translate($feature_found).': '.$distinctive_features[$feature_found].'<br/>';
					}
					$parsed = str_replace("{roomfeature ".$reqf."}", "", $parsed);
				}
			}
			//
		}
		// custom fields replace
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $cmatches);
		if (is_array($cmatches[1]) && @count($cmatches[1]) > 0) {
			$cfids = array();
			foreach ($cmatches[1] as $cfid ) {
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$dbo->execute();
			$cfields = $dbo->getNumRows() ? $dbo->loadAssocList() : array();
			$vbo_tn->translateContents($cfields, '#__vikbooking_custfields');
			$cfmap = array();
			foreach ($cfields as $cf) {
				$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $order_info['custdata']);
			if (count($partsreceived) > 0) {
				foreach($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach ($cmatches[1] as $cfid ) {
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}
		// end custom fields replace
		$parsed = str_replace("{rooms_info}", $roomstr, $parsed);
		$parsed = str_replace("{checkin_date}", $checkin_date, $parsed);
		$parsed = str_replace("{checkout_date}", $checkout_date, $parsed);
		//order details
		$orderdetails = "";
		foreach ($rooms as $num => $r) {
			$expdet = explode("\n", $rates[$num]);
			$faredets = explode(":", $expdet[0]);
			$orderdetails .= '<div class="roombooked"><strong>'.$r['name'].'</strong><br/>'.$faredets[0];
			if (!empty($expdet[1])) {
				$attrfaredets = explode(":", $expdet[1]);
				if (strlen($attrfaredets[1]) > 0) {
					$orderdetails .= ' - '.$attrfaredets[0].':'.$attrfaredets[1];
				}
			}
			$fareprice = trim(str_replace($currencyname, "", $faredets[1]));
			$orderdetails .= '<div style="float: right;"><span>'.$currencyname.' '.self::numberFormat($fareprice).'</span></div></div>';
			// options
			if (isset($options[$num]) && is_array($options[$num]) && count($options[$num]) > 0) {
				foreach ($options[$num] as $oo) {
					$expopts = explode("\n", $oo);
					foreach($expopts as $optinfo) {
						if (!empty($optinfo)) {
							$splitopt = explode(":", $optinfo);
							$optprice = trim(str_replace($currencyname, "", $splitopt[1]));
							$orderdetails .= '<div class="roomoption"><span>'.$splitopt[0].'</span><div style="float: right;"><span>'.$currencyname.' '.self::numberFormat($optprice).'</span></div></div>';
						}
					}
				}
			}
			//
			if ($roomsnum > 1 && $num < $roomsnum) {
				$orderdetails .= '<br/>';
			}
		}
		//
		// coupon
		if (!empty($order_info['coupon'])) {
			$expcoupon = explode(";", $order_info['coupon']);
			$orderdetails .= '<br/><div class="discount"><span>'.JText::translate('VBCOUPON').' '.$expcoupon[2].'</span><div style="float: right;"><span>- '.$currencyname.' '.self::numberFormat($expcoupon[1]).'</span></div></div>';
		}
		//
		// discount payment method
		if ($order_info['status'] != 'cancelled') {
			$idpayment = $order_info['idpayment'];
			if (!empty($idpayment)) {
				$exppay = explode('=', $idpayment);
				$payment = self::getPayment($exppay[0], $vbo_tn);
				if (is_array($payment)) {
					if ($payment['charge'] > 0.00 && $payment['ch_disc'] != 1) {
						// Discount (not charge)
						if ($payment['val_pcent'] == 1) {
							// fixed value
							$total -= $payment['charge'];
							$orderdetails .= '<br/><div class="discount"><span>'.$payment['name'].'</span><div style="float: right;"><span>- '.$currencyname.' '.self::numberFormat($payment['charge']).'</span></div></div>';
						} else {
							// percent value
							$percent_disc = $total * $payment['charge'] / 100;
							$total -= $percent_disc;
							$orderdetails .= '<br/><div class="discount"><span>'.$payment['name'].'</span><div style="float: right;"><span>- '.$currencyname.' '.self::numberFormat($percent_disc).'</span></div></div>';
						}
					}
				}
			}
		}
		//
		$parsed = str_replace("{order_details}", $orderdetails, $parsed);
		//
		$parsed = str_replace("{order_total}", $currencyname.' '.self::numberFormat($total), $parsed);
		$parsed = str_replace("{footer_emailtext}", $footermess, $parsed);
		$parsed = str_replace("{order_link}", '<a href="'.$link.'">'.$link.'</a>', $parsed);

		// deposit
		$deposit_str = '';
		if (!in_array($order_info['status'], array('confirmed', 'cancelled')) && !self::payTotal() && self::allowDepositFromRates($tars_info)) {
			$percentdeposit = self::getAccPerCent();
			$percentdeposit = self::calcDepositOverride($percentdeposit, $order_info['days']);
			if ($percentdeposit > 0 && self::depositAllowedDaysAdv($order_info['checkin'])) {
				if (self::getTypeDeposit() == "fixed") {
					$deposit_amount = $percentdeposit;
				} else {
					$deposit_amount = $total * $percentdeposit / 100;
				}
				if ($deposit_amount > 0) {
					$deposit_str = '<div class="deposit"><span>'.JText::translate('VBLEAVEDEPOSIT').'</span><div style="float: right;"><strong>'.$currencyname.' '.self::numberFormat($deposit_amount).'</strong></div></div>';
				}
			}
		}
		$parsed = str_replace("{order_deposit}", $deposit_str, $parsed);
		//
		// Amount Paid - Remaining Balance - Refunded Amount
		$totpaid_str = '';
		if ($order_info['refund'] > 0) {
			$totpaid_str .= '<div class="amountpaid amountrefunded"><span>' . JText::translate('VBO_AMOUNT_REFUNDED') . '</span><div style="float: right;"><strong>' . $currencyname . ' ' . self::numberFormat($order_info['refund']) . '</strong></div></div>';
		}
		if ($order_info['status'] != 'cancelled') {
			$tot_paid = $order_info['totpaid'];
			$diff_topay = (float)$total - (float)$tot_paid;
			if ((float)$tot_paid > 0) {
				$totpaid_str .= '<div class="amountpaid"><span>'.JText::translate('VBAMOUNTPAID').'</span><div style="float: right;"><strong>'.$currencyname.' '.self::numberFormat($tot_paid).'</strong></div></div>';
				// only in case the remaining balance is greater than 1 to avoid commissions issues
				if ($diff_topay > 1) {
					$totpaid_str .= '<div class="amountpaid"><span>'.JText::translate('VBTOTALREMAINING').'</span><div style="float: right;"><strong>'.$currencyname.' '.self::numberFormat($diff_topay).'</strong></div></div>';
				}
			}
		}
		$parsed = str_replace("{order_total_paid}", $totpaid_str, $parsed);
		//
		
		return $parsed;
	}
	
	public static function sendCustMail($to, $subject, $ftitle, $ts, $custdata, $rooms, $first, $second, $pricestr, $optstr, $tot, $link, $status, $orderid = "", $strcouponeff = "", $arrpeople = "", $confirmnumber = "") {
		$sendwhen = self::getSendEmailWhen();
		if ($sendwhen > 1 && $status == JText::translate('VBINATTESA')) {
			return true;
		}
		// $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='adminemail';";
		$dbo->setQuery($q);
		$dbo->execute();
		$adminemail = $dbo->loadResult();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$formdate = $dbo->loadResult();
		if ($formdate == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($formdate == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator();
		$roomsnum = count($rooms);
		//html part
		$from_name = $adminemail;
		$from_address = $adminemail;
		$reply_name = $from_name;
		$reply_address = $from_address;
		$reply_address = $from_address;
		$error_delivery_name = $from_name;
		$error_delivery_address = $from_address;
		$to_name = $to;
		$to_address = $to;
		//vikbooking 1.8 - set array variable to the template file
		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`='".(int)$orderid."';";
		$dbo->setQuery($q);
		$dbo->execute();
		$booking_info = $dbo->loadAssoc();
		$tmpl = self::loadEmailTemplate($booking_info);
		//
		// parse template
		$hmess = self::parseEmailTemplate($tmpl, $orderid, $rooms, $pricestr, $optstr, $tot, $link);
		//
		$hmess = '<html>'."\n".'<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>'."\n".'<body>'.$hmess.'</body>'."\n".'</html>';
		$mailer = JFactory::getMailer();
		$adsendermail = self::getSenderMail();
		$sender = array($adsendermail, self::getFrontTitle());
		$mailer->setSender($sender);
		$mailer->addRecipient($to);
		$mailer->addReplyTo($adsendermail);
		/**
		 * Check whether an iCal should be attached for the customer.
		 * 
		 * @since 	1.12.0
		 */
		$customer_ical = self::getEmailIcal('customer', $booking_info);
		if ($customer_ical !== false && $status != JText::translate('VBINATTESA')) {
			$mailer->addAttachment($customer_ical);
		}
		//
		$mailer->setSubject($subject);
		$mailer->setBody($hmess);
		$mailer->isHTML(true);
		$mailer->Encoding = 'base64';
		$mailer->Send();
		
		// unlink iCal file
		if ($customer_ical !== false) {
			@unlink($customer_ical);
		}
		//
		
		return true;
	}

	public static function sendCustMailFromBack($to, $subject, $ftitle, $ts, $custdata, $rooms, $first, $second, $pricestr, $optstr, $tot, $link, $status, $orderid = "", $strcouponeff = "", $arrpeople = "", $confirmnumber = "") {
		//this public static function is called from the administrator site
		// $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='adminemail';";
		$dbo->setQuery($q);
		$dbo->execute();
		$adminemail = $dbo->loadResult();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$formdate = $dbo->loadResult();
		if ($formdate == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($formdate == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator();
		$roomsnum = count($rooms);
		//html part
		$from_name = $adminemail;
		$from_address = $adminemail;
		$reply_name = $from_name;
		$reply_address = $from_address;
		$reply_address = $from_address;
		$error_delivery_name = $from_name;
		$error_delivery_address = $from_address;
		$to_name = $to;
		$to_address = $to;
		//vikbooking 1.8 - set array variable to the template file
		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`='".(int)$orderid."';";
		$dbo->setQuery($q);
		$dbo->execute();
		$booking_info = $dbo->loadAssoc();
		$tmpl = self::loadEmailTemplate($booking_info);
		// parse template
		$hmess = self::parseEmailTemplate($tmpl, $orderid, $rooms, $pricestr, $optstr, $tot, $link);
		//
		$hmess = '<html>'."\n".'<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>'."\n".'<body>'.$hmess.'</body>'."\n".'</html>';
		$mailer = JFactory::getMailer();
		$adsendermail = self::getSenderMail();
		$sender = array($adsendermail, self::getFrontTitle());
		$mailer->setSender($sender);
		$mailer->addRecipient($to);
		$mailer->addReplyTo($adsendermail);
		/**
		 * Check whether an iCal should be attached for the customer.
		 * 
		 * @since 	1.12.0
		 */
		$customer_ical = self::getEmailIcal('customer', $booking_info);
		if ($customer_ical !== false && $status != JText::translate('VBINATTESA')) {
			$mailer->addAttachment($customer_ical);
		}
		//
		$mailer->setSubject($subject);
		$mailer->setBody($hmess);
		$mailer->isHTML(true);
		$mailer->Encoding = 'base64';
		$mailer->Send();
		
		// unlink iCal file
		if ($customer_ical !== false) {
			@unlink($customer_ical);
		}
		//
		
		return true;
	}
	
	public static function sendCustMailByOrderId($oid) {
		//VikChannelManager should be the one calling this function
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".intval($oid).";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$order = $dbo->loadAssocList();
			//check if the language in use is the same as the one used during the checkout
			$lang = JFactory::getLanguage();
			$usetag = $lang->getTag();
			if (!empty($order[0]['lang'])) {
				if ($usetag != $order[0]['lang']) {
					$usetag = $order[0]['lang'];
				}
			}
			$lang->load('com_vikbooking', VIKBOOKING_LANG, $usetag, true);
			//
			$q = "SELECT `or`.*,`r`.`name`,`r`.`units`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`='".$order[0]['id']."' AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			$ordersrooms = $dbo->loadAssocList();
			$currencyname = self::getCurrencyName();
			$realback = self::getHoursRoomAvail() * 3600;
			$realback += $order[0]['checkout'];
			$rooms = array();
			$tars = array();
			$arrpeople = array();
			//send mail
			$ftitle = self::getFrontTitle();
			$nowts = time();
			$viklink = JURI::root()."index.php?option=com_vikbooking&view=booking&sid=".$order[0]['sid']."&ts=".$order[0]['ts'];
			foreach ($ordersrooms as $kor => $or) {
				$num = $kor + 1;
				$rooms[$num] = $or;
				$arrpeople[$num]['adults'] = $or['adults'];
				$arrpeople[$num]['children'] = $or['children'];
				$q="SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=" . (int)$or['idtar'] . ";";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					$tar = $dbo->loadAssocList();
					$tar = self::applySeasonsRoom($tar, $order[0]['checkin'], $order[0]['checkout']);
					//different usage
					if ($or['fromadult'] <= $or['adults'] && $or['toadult'] >= $or['adults']) {
						$diffusageprice = self::loadAdultsDiff($or['idroom'], $or['adults']);
						//Occupancy Override
						$occ_ovr = self::occupancyOverrideExists($tar, $or['adults']);
						$diffusageprice = $occ_ovr !== false ? $occ_ovr : $diffusageprice;
						//
						if (is_array($diffusageprice)) {
							//set a charge or discount to the price(s) for the different usage of the room
							foreach($tar as $kpr => $vpr) {
								$tar[$kpr]['diffusage'] = $or['adults'];
								if ($diffusageprice['chdisc'] == 1) {
									//charge
									if ($diffusageprice['valpcent'] == 1) {
										//fixed value
										$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
										$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
										$tar[$kpr]['diffusagecost'] = "+".$aduseval;
										$tar[$kpr]['room_base_cost'] = $vpr['cost'];
										$tar[$kpr]['cost'] = $vpr['cost'] + $aduseval;
									} else {
										//percentage value
										$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
										$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $tar[$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
										$tar[$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
										$tar[$kpr]['room_base_cost'] = $vpr['cost'];
										$tar[$kpr]['cost'] = $aduseval;
									}
								} else {
									//discount
									if ($diffusageprice['valpcent'] == 1) {
										//fixed value
										$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
										$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
										$tar[$kpr]['diffusagecost'] = "-".$aduseval;
										$tar[$kpr]['room_base_cost'] = $vpr['cost'];
										$tar[$kpr]['cost'] = $vpr['cost'] - $aduseval;
									} else {
										//percentage value
										$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
										$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $tar[$kpr]['days']) * $diffusageprice['value'] / 100) * $tar[$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
										$tar[$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
										$tar[$kpr]['room_base_cost'] = $vpr['cost'];
										$tar[$kpr]['cost'] = $aduseval;
									}
								}
							}
						}
					}
					//
					$tars[$num] = $tar[0];
				} else {
					return false;
				}
			}
			$pcheckin = $order[0]['checkin'];
			$pcheckout = $order[0]['checkout'];
			$secdiff = $pcheckout - $pcheckin;
			$daysdiff = $secdiff / 86400;
			if (is_int($daysdiff)) {
				if ($daysdiff < 1) {
					$daysdiff = 1;
				}
			} else {
				if ($daysdiff < 1) {
					$daysdiff = 1;
				} else {
					$sum = floor($daysdiff) * 86400;
					$newdiff = $secdiff - $sum;
					$maxhmore = self::getHoursMoreRb() * 3600;
					if ($maxhmore >= $newdiff) {
						$daysdiff = floor($daysdiff);
					} else {
						$daysdiff = ceil($daysdiff);
					}
				}
			}
			foreach ($ordersrooms as $kor => $or) {
				$num = $kor + 1;
				if (is_array($tars[$num])) {
					$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
					$calctar = self::sayCostPlusIva($display_rate, $tars[$num]['idprice']);
					$tars[$num]['calctar'] = $calctar;
					$isdue += $calctar;
					$pricestr[$num] = self::getPriceName($tars[$num]['idprice']) . ": " . $calctar . " " . $currencyname . (!empty($tars[$num]['attrdata']) ? "\n" . self::getPriceAttr($tars[$num]['idprice']) . ": " . $tars[$num]['attrdata'] : "");
				}
				if (!empty($or['optionals'])) {
					$stepo = explode(";", $or['optionals']);
					foreach ($stepo as $roptkey => $oo) {
						if (!empty($oo)) {
							$stept = explode(":", $oo);
							$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
							$dbo->setQuery($q);
							$dbo->execute();
							if ($dbo->getNumRows() == 1) {
								$actopt = $dbo->loadAssocList();
								$chvar = '';
								if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
									$optagenames = self::getOptionIntervalsAges($actopt[0]['ageintervals']);
									$optagepcent = self::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
									$optageovrct = self::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
									$child_num 	 = self::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
									$optagecosts = self::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
									$agestept = explode('-', $stept[1]);
									$stept[1] = $agestept[0];
									$chvar = $agestept[1];
									if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
										//percentage value of the adults tariff
										$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
										//VBO 1.10 - percentage value of room base cost
										$display_rate = isset($tars[$num]['room_base_cost']) ? $tars[$num]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost']);
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
									$actopt[0]['chageintv'] = $chvar;
									$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
									$actopt[0]['quan'] = $stept[1];
									$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $order[0]['days'] * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
								} else {
									$actopt[0]['quan'] = $stept[1];
									// VBO 1.11 - options percentage cost of the room total fee
									$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
									$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
									//
									$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $order[0]['days'] * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
								}
								if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
									$realcost = $actopt[0]['maxprice'];
									if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
										$realcost = $actopt[0]['maxprice'] * $stept[1];
									}
								}
								if ($actopt[0]['perperson'] == 1) {
									$realcost = $realcost * $or['adults'];
								}
								$tmpopr = self::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
								$isdue += $tmpopr;
								$optstr[$num][] = ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'] . ": " . $tmpopr . " " . $currencyname . "\n";
							}
						}
					}
				}
				//custom extra costs
				if (!empty($or['extracosts'])) {
					$cur_extra_costs = json_decode($or['extracosts'], true);
					foreach ($cur_extra_costs as $eck => $ecv) {
						$ecplustax = !empty($ecv['idtax']) ? self::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
						$isdue += $ecplustax;
						$optstr[$num][] = $ecv['name'] . ": " . $ecplustax . " " . $currencyname."\n";
					}
				}
				//
			}
			//vikbooking 1.1 coupon
			$usedcoupon = false;
			$origisdue = $isdue;
			if (strlen($order[0]['coupon']) > 0) {
				$usedcoupon = true;
				$expcoupon = explode(";", $order[0]['coupon']);
				$isdue = $isdue - $expcoupon[1];
			}
			// make sure to apply any previously refunded amount
			if ($order[0]['refund'] > 0) {
				$isdue -= $order[0]['refund'];
			}
			//
			//ConfirmationNumber
			$confirmnumber = $order[0]['confirmnumber'];
			//end ConfirmationNumber
			
			if ($order[0]['status'] != 'confirmed' && $order[0]['status'] != 'standby') {
				return false;
			}
			
			$langstatus = $order[0]['status'] == 'confirmed' ? JText::translate('VBCOMPLETED') : JText::translate('VBINATTESA');
			
			self::sendCustMail($order[0]['custmail'], strip_tags($ftitle)." ".JText::translate('VBORDNOL'), $ftitle, $nowts, $order[0]['custdata'], $rooms, $order[0]['checkin'], $order[0]['checkout'], $pricestr, $optstr, $isdue, $viklink, $langstatus, $order[0]['id'], $order[0]['coupon'], $arrpeople, $confirmnumber);
			
			return true;
		}
		return false;
	}

	/**
	 * New method for sending booking email messages
	 * to the guest or to the administrator(s).
	 * 
	 * @param 	int 	$bid 	the booking ID.
	 * @param 	array 	$for 	guest, admin or a custom email address.
	 * @param 	boolean $send 	whether to send or return the HTML message.
	 * 
	 * @return 	mixed 	True or False depending on the result or HTML string for the preview.
	 * 
	 * @since 	1.13
	 */
	public static function sendBookingEmail($bid, $for = array(), $send = true) {
		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();
		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=" . (int)$bid . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return false;
		}
		$booking = $dbo->loadAssoc();
		$result = false;
		// check if the language in use is the same as the one used during the checkout
		$lang = JFactory::getLanguage();
		if (!empty($booking['lang'])) {
			if ($lang->getTag() != $booking['lang']) {
				$lang->load('com_vikbooking', (defined('VIKBOOKING_LANG') ? VIKBOOKING_LANG : JPATH_SITE), $booking['lang'], true);
			}
			if ($vbo_tn->getDefaultLang() != $booking['lang']) {
				// force the translation to start because contents should be translated
				$vbo_tn::$force_tolang = $booking['lang'];
			}
		}
		//
		$q = "SELECT `or`.*,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`units`,`r`.`fromadult`,`r`.`toadult`,`r`.`params` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=" . $booking['id'] . " AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		$ordersrooms = $dbo->loadAssocList();
		$vbo_tn->translateContents($ordersrooms, '#__vikbooking_rooms', array('id' => 'r_reference_id'));
		$rooms = array();
		$tars = array();
		$is_package = !empty($booking['pkg']) ? true : false;
		$ftitle = self::getFrontTitle();
		$currencyname = self::getCurrencyName();
		foreach ($ordersrooms as $kor => $or) {
			$num = $kor + 1;
			$rooms[$num] = $or;
			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				// package or custom cost set from the back-end
				continue;
			}
			$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=" . (int)$or['idtar'] . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				// tariff not found
				if (self::isAdmin()) {
					VikError::raiseWarning('', JText::translate('VBERRNOFAREFOUND'));
				}
				continue;
			}
			$tar = $dbo->loadAssocList();
			$tar = self::applySeasonsRoom($tar, $booking['checkin'], $booking['checkout']);
			// different usage
			if ($or['fromadult'] <= $or['adults'] && $or['toadult'] >= $or['adults']) {
				$diffusageprice = self::loadAdultsDiff($or['idroom'], $or['adults']);
				// Occupancy Override
				$occ_ovr = self::occupancyOverrideExists($tar, $or['adults']);
				$diffusageprice = $occ_ovr !== false ? $occ_ovr : $diffusageprice;
				//
				if (is_array($diffusageprice)) {
					// set a charge or discount to the price(s) for the different usage of the room
					foreach ($tar as $kpr => $vpr) {
						$tar[$kpr]['diffusage'] = $or['adults'];
						if ($diffusageprice['chdisc'] == 1) {
							// charge
							if ($diffusageprice['valpcent'] == 1) {
								// fixed value
								$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
								$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
								$tar[$kpr]['diffusagecost'] = "+".$aduseval;
								$tar[$kpr]['room_base_cost'] = $vpr['cost'];
								$tar[$kpr]['cost'] = $vpr['cost'] + $aduseval;
							} else {
								// percentage value
								$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
								$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $tar[$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
								$tar[$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
								$tar[$kpr]['room_base_cost'] = $vpr['cost'];
								$tar[$kpr]['cost'] = $aduseval;
							}
						} else {
							// discount
							if ($diffusageprice['valpcent'] == 1) {
								// fixed value
								$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
								$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
								$tar[$kpr]['diffusagecost'] = "-".$aduseval;
								$tar[$kpr]['room_base_cost'] = $vpr['cost'];
								$tar[$kpr]['cost'] = $vpr['cost'] - $aduseval;
							} else {
								// percentage value
								$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
								$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $tar[$kpr]['days']) * $diffusageprice['value'] / 100) * $tar[$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
								$tar[$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
								$tar[$kpr]['room_base_cost'] = $vpr['cost'];
								$tar[$kpr]['cost'] = $aduseval;
							}
						}
					}
				}
			}
			//
			$tars[$num] = $tar[0];
		}
		$pcheckin = $booking['checkin'];
		$pcheckout = $booking['checkout'];
		$secdiff = $pcheckout - $pcheckin;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$isdue = 0;
		$pricestr = array();
		$optstr = array();
		foreach ($ordersrooms as $kor => $or) {
			$num = $kor + 1;
			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				// package cost or cust_cost may not be inclusive of taxes if prices tax included is off
				$calctar = self::sayPackagePlusIva($or['cust_cost'], $or['cust_idiva']);
				$isdue += $calctar;
				$pricestr[$num] = (!empty($or['pkg_name']) ? $or['pkg_name'] : (!empty($or['otarplan']) ? ucwords($or['otarplan']) : JText::translate('VBOROOMCUSTRATEPLAN'))).": ".$calctar." ".$currencyname;
			} elseif (array_key_exists($num, $tars) && is_array($tars[$num])) {
				$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
				$calctar = self::sayCostPlusIva($display_rate, $tars[$num]['idprice']);
				$tars[$num]['calctar'] = $calctar;
				$isdue += $calctar;
				$pricestr[$num] = self::getPriceName($tars[$num]['idprice'], $vbo_tn) . ": " . $calctar . " " . $currencyname . (!empty($tars[$num]['attrdata']) ? "\n" . self::getPriceAttr($tars[$num]['idprice'], $vbo_tn) . ": " . $tars[$num]['attrdata'] : "");
			}
			if (!empty($or['optionals'])) {
				$stepo = explode(";", $or['optionals']);
				foreach ($stepo as $roptkey => $oo) {
					if (empty($oo)) {
						continue;
					}
					$stept = explode(":", $oo);
					$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
					$dbo->setQuery($q);
					$dbo->execute();
					if ($dbo->getNumRows() == 1) {
						$actopt = $dbo->loadAssocList();
						$vbo_tn->translateContents($actopt, '#__vikbooking_optionals', array(), array(), (!empty($booking['lang']) ? $booking['lang'] : null));
						$chvar = '';
						if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
							$optagenames = self::getOptionIntervalsAges($actopt[0]['ageintervals']);
							$optagepcent = self::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
							$optageovrct = self::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
							$child_num 	 = self::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
							$optagecosts = self::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
							$agestept = explode('-', $stept[1]);
							$stept[1] = $agestept[0];
							$chvar = $agestept[1];
							if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
								// percentage value of the adults tariff
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
								} else {
									$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
									$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
								}
							} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
								// VBO 1.10 - percentage value of room base cost
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
								} else {
									$display_rate = isset($tars[$num]['room_base_cost']) ? $tars[$num]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost']);
									$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
								}
							}
							$actopt[0]['chageintv'] = $chvar;
							$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
							$actopt[0]['quan'] = $stept[1];
							$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $booking['days'] * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
						} else {
							$actopt[0]['quan'] = $stept[1];
							// VBO 1.11 - options percentage cost of the room total fee
							if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
								$deftar_basecosts = $or['cust_cost'];
							} else {
								$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
							}
							$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
							//
							$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $booking['days'] * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
						}
						if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
							$realcost = $actopt[0]['maxprice'];
							if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
								$realcost = $actopt[0]['maxprice'] * $stept[1];
							}
						}
						if ($actopt[0]['perperson'] == 1) {
							$realcost = $realcost * $or['adults'];
						}
						$tmpopr = self::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
						$isdue += $tmpopr;
						$optstr[$num][] = ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'] . ": " . $tmpopr . " " . $currencyname . "\n";
					}
				}
			}
			// custom extra costs
			if (!empty($or['extracosts'])) {
				$cur_extra_costs = json_decode($or['extracosts'], true);
				foreach ($cur_extra_costs as $eck => $ecv) {
					$ecplustax = !empty($ecv['idtax']) ? self::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
					$isdue += $ecplustax;
					$optstr[$num][] = $ecv['name'] . ": " . $ecplustax . " " . $currencyname."\n";
				}
			}
			//
		}

		// force the original total amount if rates have changed
		if (number_format($isdue, 2) != number_format($booking['total'], 2)) {
			$isdue = $booking['total'];
		}

		// mail subject
		$subject = JText::sprintf('VBOMAILSUBJECT', strip_tags($ftitle));
		// $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		
		// inject the recipient of the message for the template
		$booking['for'] = $for;

		// load template file that will get $booking as variable
		$tmpl = self::loadEmailTemplate($booking);

		// parse email template
		$hmess = self::parseEmailTemplate($tmpl, $booking, $rooms, $pricestr, $optstr, $isdue);
		$hmess = '<html>'."\n".'<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>'."\n".'<body>'.$hmess.'</body>'."\n".'</html>';

		if ($send !== true) {
			// return the content of the email message parsed
			return $hmess;
		}

		// when the message can be sent
		$sendwhen = self::getSendEmailWhen();

		// send the message
		foreach ($for as $who) {
			$use_subject = $subject;
			$recipients = array();
			$attachments = self::addEmailAttachment(null);
			$attach_ical = false;
			$force_replyto = null;
			if (strpos($who, '@') !== false) {
				// send email to custom email address
				array_push($recipients, trim($who));
			} elseif (stripos($who, 'guest') !== false || stripos($who, 'customer') !== false) {
				// send email to the customer
				if ($sendwhen > 1 && $booking['status'] == 'standby') {
					continue;
				}
				array_push($recipients, $booking['custmail']);
				/**
				 * Check whether an iCal should be attached for the customer.
				 * 
				 * @since 	1.12.0
				 */
				$attach_ical = self::getEmailIcal('customer', $booking);
			} elseif (stripos($who, 'admin') !== false) {
				// send email to the administrator(s)
				if ($sendwhen > 1 && $booking['status'] == 'standby') {
					continue;
				}
				$use_subject = $subject . ' #' . $booking['id'];
				$adminemail = self::getAdminMail();
				$extra_admin_recipients = self::addAdminEmailRecipient(null);
				if (empty($adminemail) && empty($extra_admin_recipients)) {
					// Prevent Joomla Exceptions that would stop the script execution
					VikError::raiseWarning('', 'The administrator email address is empty. Email message could not be sent.');
					continue;
				}
				if (strpos($adminemail, ',') !== false) {
					// multiple addresses
					$adminemails = explode(',', $adminemail);
					foreach ($adminemails as $am) {
						if (strpos($am, '@') !== false) {
							array_push($recipients, trim($am));
						}
					}
				} else {
					// single address
					array_push($recipients, trim($adminemail));
				}
				
				// merge extra recipients
				$recipients = array_merge($recipients, $extra_admin_recipients);

				// admin should reply to the customer
				$force_replyto = !empty($booking['custmail']) ? $booking['custmail'] : $force_replyto;

				/**
				 * Check whether an iCal should be attached for the admin.
				 * 
				 * @since 	1.2.0
				 */
				$attach_ical = self::getEmailIcal('admin', array(
					'ts' => $booking['ts'],
					'custdata' => $booking['custdata'],
					'checkin' => $booking['checkin'],
					'checkout' => $booking['checkout'],
					'subject' => JText::sprintf('VBNEWORDER', $booking['id']),
				));
			}
			// send the message, recipients should always be an array to support multiple admin addresses
			$mailer = JFactory::getMailer();
			$adsendermail = self::getSenderMail();
			$sender = array($adsendermail, $ftitle);
			$mailer->setSender($sender);
			$mailer->addRecipient($recipients);
			$extra_bcc = self::addAdminEmailRecipient(null, true);
			if (count($extra_bcc)) {
				$mailer->addBcc($extra_bcc);
			}
			$mailer->addReplyTo((!empty($force_replyto) ? $force_replyto : $adsendermail));
			if ($attach_ical !== false && $booking['status'] != 'standby') {
				$mailer->addAttachment($attach_ical);
			}
			foreach ($attachments as $attachment) {
				$mailer->addAttachment($attachment);
			}
			$mailer->setSubject($use_subject);
			$mailer->setBody($hmess);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			$result = $mailer->Send() || $result;
			
			// unlink iCal file
			if ($attach_ical !== false) {
				@unlink($attach_ical);
			}
			//
		}

		return $result;
	}

	/**
	 * This method serves to add one or more recipient email
	 * addresses for the next queue of email sending for the admin.
	 * This method can be used in the template file for the customer
	 * email to register an additional email address, maybe when a 
	 * specific room-type is booked.
	 * The methods sending the email messages are supposed to call this
	 * method by passing no arguments to obtain the extra addresses set.
	 *
	 * @param 	mixed 	$email 	null, string or array of email address(es).
	 * @param 	bool 	$bcc 	if true, addresses will be used as bcc.
	 * 
	 * @return 	array 	the current extra recipients or bcc addresses set.
	 * 
	 * @since 	1.3.0
	 * @since 	1.14 (J) - 1.4.0 (WP) added param $bcc
	 */
	public static function addAdminEmailRecipient($email, $bcc = false) {
		static $extra_recipients = array();
		static $extra_bcc = array();

		if (!empty($email)) {
			if (is_scalar($email)) {
				if ($bcc) {
					array_push($extra_bcc, $email);
				} else {
					array_push($extra_recipients, $email);
				}
			} else {
				if ($bcc) {
					$extra_bcc = array_merge($extra_bcc, $email);
				} else {
					$extra_recipients = array_merge($extra_recipients, $email);
				}
			}
		}
		
		return $bcc ? array_unique($extra_bcc) : array_unique($extra_recipients);
	}

	/**
	 * This method serves to add one or more attachments to the
	 * next queue of email sending for the admin.
	 * The methods sending the email messages are supposed to call this
	 * method by passing a null argument to obtain the attachments set.
	 *
	 * @param 	mixed 	$file 	null or string with path to file to attach.
	 * 
	 * @return 	array 	the current attachments set.
	 * 
	 * @since 	1.4.0
	 */
	public static function addEmailAttachment($file) {
		static $extra_attachments = array();

		if (!empty($file)) {
			if (is_scalar($file)) {
				array_push($extra_attachments, $file);
			} else {
				$extra_attachments = array_merge($extra_attachments, $file);
			}
		}
		
		return array_unique($extra_attachments);
	}

	/**
	 * This method is called whenever some rooms get booked.
	 * It checks whether the rooms involved have shared calendars.
	 * If some are found, then also such rooms will be occupied.
	 * 
	 * @param 	int 	$bid 		the booking ID.
	 * @param 	array 	$roomids 	the list of rooms booked.
	 * @param 	int 	$checkin 	checkin timestamp.
	 * @param 	int 	$checkout 	checkout timestamp.
	 * 
	 * @return 	boolean true if some other cals were occupied, false otherwise.
	 * 
	 * @since 	1.3.0
	 * 
	 * @see 	SynchVikBooking::getRoomsSharedCalsInvolved() in VCM that checks if this method exists.
	 */
	public static function updateSharedCalendars($bid, $roomids = array(), $checkin = 0, $checkout = 0) {
		$dbo = JFactory::getDbo();
		$bid = (int)$bid;
		if (!count($roomids)) {
			// get the IDs of all rooms booked
			$q = "SELECT `idroom` FROM `#__vikbooking_ordersrooms` WHERE `idorder`={$bid};";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$orr = $dbo->loadAssocList();
				foreach ($orr as $or) {
					array_push($roomids, $or['idroom']);
				}
			}
		}
		if (!count($roomids) || empty($bid)) {
			// unable to proceed
			return false;
		}
		$roomids = array_unique($roomids);
		if (empty($checkin) || empty($checkout)) {
			// get checkin and checkout timestamps from booking
			$q = "SELECT `checkin`, `checkout` FROM `#__vikbooking_orders` WHERE `id`={$bid};";
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				// booking not found
				return false;
			}
			$bdata = $dbo->loadAssoc();
			$checkin = $bdata['checkin'];
			$checkout = $bdata['checkout'];
		}

		// get rooms involved
		$involved = array();
		$q = "SELECT * FROM `#__vikbooking_calendars_xref` WHERE `mainroom` IN (" . implode(', ', $roomids) . ") OR `childroom` IN (" . implode(', ', $roomids) . ");";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no rooms involved that need their calendars updated
			return false;
		}
		$rooms_found = $dbo->loadAssocList();
		foreach ($rooms_found as $rf) {
			if (!in_array($rf['mainroom'], $roomids)) {
				array_push($involved, $rf['mainroom']);
			}
			if (!in_array($rf['childroom'], $roomids)) {
				array_push($involved, $rf['childroom']);
			}
		}
		$involved = array_unique($involved);
		if (!count($involved)) {
			// no rooms involved
			return false;
		}

		// occupy the calendars for the involved rooms found
		$bids_generated = array();
		foreach ($involved as $rid) {
			$q = "INSERT INTO `#__vikbooking_busy` (`idroom`,`checkin`,`checkout`,`realback`,`sharedcal`) VALUES(".(int)$rid.", ".(int)$checkin.", ".(int)$checkout.", ".(int)$checkout.", 1);";
			$dbo->setQuery($q);
			$dbo->execute();
			array_push($bids_generated, $dbo->insertid());
		}
		foreach ($bids_generated as $busyid) {
			$q = "INSERT INTO `#__vikbooking_ordersbusy` (`idorder`,`idbusy`) VALUES(".$bid.", ".(int)$busyid.");";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		return true;
	}

	/**
	 * This method is needed whenever a booking gets modified by
	 * adding or removing rooms. This way we reset (remove) all
	 * busy records that were previously stored due to a shared
	 * calendar. The correct relations should then be re-created
	 * by calling updateSharedCalendars().
	 * 
	 * @param 	int 	$bid 	the booking ID.
	 * 
	 * @return 	boolean 		true if some records were cleaned.
	 * 
	 * @see 	updateSharedCalendars() should be called after.
	 * 
	 * @since 	1.3.0
	 */
	public static function cleanSharedCalendarsBusy($bid) {
		$dbo = JFactory::getDbo();
		$bid = (int)$bid;
		// get all the occupied records due to shared calendars for this booking
		$q = "SELECT `b`.`id`, `b`.`idroom`, `b`.`sharedcal`, `ob`.`idorder`, `ob`.`idbusy` FROM `#__vikbooking_busy` AS `b` LEFT JOIN `#__vikbooking_ordersbusy` AS `ob` ON `ob`.`idbusy`=`b`.`id` WHERE `ob`.`idorder`={$bid} AND `b`.`sharedcal`=1;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return false;
		}
		$allbusy = $dbo->loadAssocList();
		$busy_ids = array();
		foreach ($allbusy as $b) {
			// push busy ID to be removed later
			array_push($busy_ids, $b['id']);
			// delete the current busy ID-booking ID relation for this shared calendar
			$q = "DELETE FROM `#__vikbooking_ordersbusy` WHERE `idorder`={$bid} AND `idbusy`={$b['id']};";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		if (count($busy_ids)) {
			// delete all busy records due to shared calendars
			$q = "DELETE FROM `#__vikbooking_busy` WHERE `id` IN (" . implode(', ', $busy_ids) . ");";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		return true;
	}

	public static function sendJutility() {
		//deprecated in VBO 1.10
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='sendjutility';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getCategoryName($idcat, $vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` WHERE `id`=" . $dbo->quote($idcat) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		$p = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : array();
		if (is_object($vbo_tn) && count($p) > 0) {
			$vbo_tn->translateContents($p, '#__vikbooking_categories');
		}
		return count($p) > 0 ? $p[0]['name'] : '';
	}
	
	public static function loadAdultsDiff($idroom, $adults) {
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_adultsdiff` WHERE `idroom`='" . $idroom . "' AND `adults`='".$adults."';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$diff = $dbo->loadAssocList();
			return $diff[0];
		} else {
			return "";
		}
	}

	public static function loadRoomAdultsDiff($idroom) {
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_adultsdiff` WHERE `idroom`=" . (int)$idroom . " ORDER BY `adults` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$diff = $dbo->loadAssocList();
			$roomdiff = array();
			foreach ($diff as $v) {
				$roomdiff[$v['adults']] = $v;
			}
			return $roomdiff;
		}
		return array();
	}

	public static function occupancyOverrideExists($tar, $adults) {
		foreach ($tar as $k => $v) {
			if (is_array($v) && array_key_exists('occupancy_ovr', $v)) {
				if (array_key_exists($adults, $v['occupancy_ovr'])) {
					return $v['occupancy_ovr'][$adults];
				}
			}
		}
		return false;
	}
	
	public static function getChildrenCharges($idroom, $children, $ages, $num_nights) {
		/* charges as percentage amounts of the adults tariff not supported for third parties (only VBO 1.8) */
		$charges = array();
		if (!($children > 0) || !(count($ages) > 0)) {
			return $charges;
		}
		$dbo = JFactory::getDbo();
		$id_options = array();
		$q = "SELECT `id`,`idopt` FROM `#__vikbooking_rooms` WHERE `id`=".(int)$idroom.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$assocs = $dbo->loadAssocList();
			foreach ($assocs as $opts) {
				if (!empty($opts['idopt'])) {
					$r_ido = explode(';', rtrim($opts['idopt']));
					foreach ($r_ido as $ido) {
						if (!empty($ido) && !in_array($ido, $id_options)) {
							$id_options[] = $ido;
						}
					}
				}
			}
		}
		if (count($id_options) > 0) {
			$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id` IN (".implode(", ", $id_options).") AND `ifchildren`=1 AND (LENGTH(`ageintervals`) > 0 OR `ageintervals` IS NOT NULL) LIMIT 1;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$ageintervals = $dbo->loadAssocList();
				$split_ages = explode(';;', $ageintervals[0]['ageintervals']);
				$age_range = array();
				foreach ($split_ages as $kg => $spage) {
					if (empty($spage)) {
						continue;
					}
					$parts = explode('_', $spage);
					if (strlen($parts[0]) > 0 && intval($parts[1]) > 0 && floatval($parts[2]) > 0) {
						$ind = count($age_range);
						$age_range[$ind]['from'] = intval($parts[0]);
						$age_range[$ind]['to'] = intval($parts[1]);
						//taxes are calculated later in VCM
						//$age_range[$ind]['cost'] = self::sayOptionalsPlusIva((floatval($parts[2]) * $num_nights), $ageintervals[0]['idiva']);
						$age_range[$ind]['cost'] = floatval($parts[2]) * $num_nights;
						$age_range[$ind]['option_str'] = $ageintervals[0]['id'].':1-'.($kg + 1);
					}
				}
				if (count($age_range) > 0) {
					$tot_charge = 0;
					$affected = array();
					$option_str = '';
					foreach ($ages as $age) {
						if (strlen($age) == 0) {
							continue;
						}
						foreach ($age_range as $range) {
							if (intval($age) >= $range['from'] && intval($age) <= $range['to']) {
								$tot_charge += $range['cost'];
								$affected[] = $age;
								$option_str .= $range['option_str'].';';
								break;
							}
						}
					}
					if ($tot_charge > 0) {
						$charges['total'] = $tot_charge;
						$charges['affected'] = $affected;
						$charges['options'] = $option_str;
					}
				}
			}
		}
		
		return $charges;
	}
	
	public static function sortRoomPrices($arr) {
		$newarr = array ();
		foreach ($arr as $k => $v) {
			$newarr[$k] = $v['cost'];
		}
		asort($newarr);
		$sorted = array ();
		foreach ($newarr as $k => $v) {
			$sorted[$k] = $arr[$k];
		}
		return $sorted;
	}
	
	public static function sortResults($arr) {
		$newarr = array ();
		foreach ($arr as $k => $v) {
			$newarr[$k] = $v[0]['cost'];
		}
		asort($newarr);
		$sorted = array ();
		foreach ($newarr as $k => $v) {
			$sorted[$k] = $arr[$k];
		}
		return $sorted;
	}
	
	public static function sortMultipleResults($arr) {
		foreach ($arr as $k => $v) {
			$newarr = array ();
			foreach ($v as $subk => $subv) {
				$newarr[$subk] = $subv[0]['cost'];
			}
			asort($newarr);
			$sorted = array ();
			foreach ($newarr as $nk => $v) {
				$sorted[$nk] = $arr[$k][$nk];
			}
			$arr[$k] = $sorted;
		}
		return $arr;
	}

	public static function applySeasonalPrices($arr, $from, $to) {
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();
		$roomschange = array();
		$one = getdate($from);
		
		// leap years
		if (($one['year'] % 4) == 0 && ($one['year'] % 100 != 0 || $one['year'] % 400 == 0)) {
			$isleap = true;
		} else {
			$isleap = false;
		}

		$baseone = mktime(0, 0, 0, 1, 1, $one['year']);
		$tomidnightone = intval($one['hours']) * 3600;
		$tomidnightone += intval($one['minutes']) * 60;
		$sfrom = $from - $baseone - $tomidnightone;
		$fromdayts = mktime(0, 0, 0, $one['mon'], $one['mday'], $one['year']);
		$two = getdate($to);
		$basetwo = mktime(0, 0, 0, 1, 1, $two['year']);
		$tomidnighttwo = intval($two['hours']) * 3600;
		$tomidnighttwo += intval($two['minutes']) * 60;
		$sto = $to - $basetwo - $tomidnighttwo;
		
		// leap years, last day of the month of the season
		if ($isleap) {
			$leapts = mktime(0, 0, 0, 2, 29, $two['year']);
			if ($two[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom -= 86400;
				$sto -= 86400;
			} elseif ($sto < $sfrom && $one['year'] < $two['year']) {
				//lower checkin date when in leap year but not for checkout
				$sfrom -= 86400;
			}
		}
		
		// count nights requested
		$booking_nights = 1;
		foreach ($arr as $k => $a) {
			if (isset($a[0]) && isset($a[0]['days'])) {
				$booking_nights = $a[0]['days'];
				break;
			}
		}

		$q = "SELECT * FROM `#__vikbooking_seasons` WHERE (" .
		 ($sto > $sfrom ? "(`from`<=" . $sfrom . " AND `to`>=" . $sto . ") " : "") .
		 ($sto > $sfrom ? "OR (`from`<=" . $sfrom . " AND `to`>=" . $sfrom . ") " : "(`from`<=" . $sfrom . " AND `to`<=" . $sfrom . " AND `from`>`to`) ") .
		 ($sto > $sfrom ? "OR (`from`<=" . $sto . " AND `to`>=" . $sto . ") " : "OR (`from`>=" . $sto . " AND `to`>=" . $sto . " AND `from`>`to`) ") .
		 ($sto > $sfrom ? "OR (`from`>=" . $sfrom . " AND `from`<=" . $sto . " AND `to`>=" . $sfrom . " AND `to`<=" . $sto . ")" : "OR (`from`>=" . $sfrom . " AND `from`>" . $sto . " AND `to`<" . $sfrom . " AND `to`<=" . $sto . " AND `from`>`to`)") .
		 ($sto > $sfrom ? " OR (`from`<=" . $sfrom . " AND `from`<=" . $sto . " AND `to`<" . $sfrom . " AND `to`<" . $sto . " AND `from`>`to`) OR (`from`>" . $sfrom . " AND `from`>" . $sto . " AND `to`>=" . $sfrom . " AND `to`>=" . $sto . " AND `from`>`to`)" : " OR (`from` <=" . $sfrom . " AND `to` >=" . $sfrom . " AND `from` >" . $sto . " AND `to` >" . $sto . " AND `from` < `to`)") .
		 ($sto > $sfrom ? " OR (`from` >=" . $sfrom . " AND `from` <" . $sto . " AND `to` <" . $sfrom . " AND `to` <" . $sto . " AND `from` > `to`)" : " OR (`from` <" . $sfrom . " AND `to` >=" . $sto . " AND `from` <=" . $sto . " AND `to` <" . $sfrom . " AND `from` < `to`)"). //VBO 1.6 Else part is for Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8
		 ($sto > $sfrom ? " OR (`from` >" . $sfrom . " AND `from` >" . $sto . " AND `to` >=" . $sfrom . " AND `to` <" . $sto . " AND `from` > `to`)" : " OR (`from` >=" . $sfrom . " AND `from` >" . $sto . " AND `to` >" . $sfrom . " AND `to` >" . $sto . " AND `from` < `to`) OR (`from` <" . $sfrom . " AND `from` <" . $sto . " AND `to` <" . $sfrom . " AND `to` <=" . $sto . " AND `from` < `to`)"). //VBO 1.7 Else part for seasons Dec 25 to Dec 31, Jan 2 to Jan 5 - Booking Dec 20 to Jan 7
		") ORDER BY `#__vikbooking_seasons`.`promo` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		$totseasons = $dbo->getNumRows();
		if ($totseasons > 0) {
			$seasons = $dbo->loadAssocList();
			$vbo_tn->translateContents($seasons, '#__vikbooking_seasons');
			$applyseasons = false;
			$mem = array();
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = array();
				/**
				 * The keys below are all needed to apply the promotions on the room's final cost.
				 * 
				 * @since 	1.13.5
				 */
				$mem[$k]['diffs'] = array();
				$mem[$k]['trans_keys'] = array();
				$mem[$k]['trans_factors'] = array();
			}
			foreach ($seasons as $s) {
				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					//VBO 1.7 - do not skip seasons tied to the year for bookings between two years
					if ($one['year'] != $s['year'] && $two['year'] != $s['year']) {
						//VBO 1.9 - tied to the year can be set for prev year (Dec 27 to Jan 3) and booking can be Jan 1 to Jan 3 - do not skip in this case
						if (($one['year'] - $s['year']) != 1 || $s['from'] < $s['to']) {
							continue;
						}
						//VBO 1.9 - tied to 2016 going through Jan 2017: dates of December 2017 should skip this speacial price
						if (($one['year'] - $s['year']) == 1 && $s['from'] > $s['to']) {
							$calc_ends = mktime(0, 0, 0, 1, 1, ($s['year'] + 1)) + $s['to'];
							if ($calc_ends < ($from - $tomidnightone)) {
								continue;
							}
						}
					} elseif ($one['year'] < $s['year'] && $two['year'] == $s['year']) {
						//VBO 1.9 - season tied to the year 2017 accross 2018 and we are parsing dates accross prev year 2016-2017
						if ($s['from'] > $s['to']) {
							continue;
						}
					} elseif ($one['year'] == $s['year'] && $two['year'] == $s['year'] && $s['from'] > $s['to']) {
						//VBO 1.9 - season tied to the year 2017 accross 2018 and we are parsing dates at the beginning of 2017 due to beginning loop in 2016 (Rates Overview)
						if (($baseone + $s['from']) > $to) {
							continue;
						}
					}
				}
				//
				$allrooms = explode(",", $s['idrooms']);
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : array();
				$inits = $baseone + $s['from'];
				if ($s['from'] < $s['to']) {
					$ends = $basetwo + $s['to'];
					//VikBooking 1.6 check if the inits must be set to the year after
					//ex. Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8 to charge Jan 6,7
					if ($sfrom > $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto <= $s['to'] && $s['from'] < $s['to'] && $sfrom > $sto) {
						$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
						$inits = $tmpbase + $s['from'];
					} elseif ($sfrom >= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						//VBO 1.7 - Season Dec 23 to Dec 29 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom <= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						//VBO 1.7 - Season Dec 30 to Dec 31 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom > $s['from'] && $sfrom > $s['to'] && $sto >= $s['from'] && ($sto >= $s['to'] || $sto <= $s['to']) && $sfrom > $sto) {
						//VBO 1.7 - Season Jan 1 to Jan 2 - Booking Dec 29 to Jan 5
						$inits = $basetwo + $s['from'];
					}
				} else {
					//between 2 years
					if ($baseone < $basetwo) {
						//ex. 29/12/2012 - 14/01/2013
						$ends = $basetwo + $s['to'];
					} else {
						if (($sfrom >= $s['from'] && $sto >= $s['from']) OR ($sfrom < $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto > $s['to'])) {
							//ex. 25/12 - 30/12 with init season on 20/12 OR 27/12 for counting 28,29,30/12
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
							$ends = $tmpbase + $s['to'];
						} else {
							//ex. 03/01 - 09/01
							$ends = $basetwo + $s['to'];
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] - 1));
							$inits = $tmpbase + $s['from'];
						}
					}
				}
				
				// leap years
				if ($isleap == true) {
					$infoseason = getdate($inits);
					$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
					// VikBooking 1.6 added below && $infoseason['year'] == $one['year']
					// for those seasons like 2015 Dec 14 to 2016 Jan 5 and booking dates like 2016 Jan 1 to Jan 6 where 2015 is not leap
					if ($infoseason[0] > $leapts && $infoseason['year'] == $one['year']) {
						/**
						 * Timestamp must be greater than the leap-day of Feb 29th.
						 * It used to be checked for >= $leapts.
						 * 
						 * @since 	July 3rd 2019
						 */
						$inits += 86400;
						$ends += 86400;
					}
				}
				
				// promotions
				$promotion = array();
				if ($s['promo'] == 1) {
					$daysadv = (($inits - time()) / 86400);
					$daysadv = $daysadv > 0 ? (int)ceil($daysadv) : 0;
					if (!empty($s['promodaysadv']) && $s['promodaysadv'] > $daysadv) {
						continue;
					} elseif (!empty($s['promolastmin']) && $s['promolastmin'] > 0) {
						$secstocheckin = ($from - time());
						if ($s['promolastmin'] < $secstocheckin) {
							// VBO 1.11 - too many seconds to the check-in date, skip this last minute promotion
							continue;
						}
					}
					if ($s['promominlos'] > 1 && $booking_nights < $s['promominlos']) {
						/**
						 * The minimum length of stay parameter is also taken to exclude the promotion from the calculation.
						 * 
						 * @since 	1.13.5
						 */
						continue;
					}
					$promotion['todaydaysadv'] = $daysadv;
					$promotion['promodaysadv'] = $s['promodaysadv'];
					$promotion['promotxt'] = $s['promotxt'];
				}
				
				// occupancy override
				$occupancy_ovr = !empty($s['occupancy_ovr']) ? json_decode($s['occupancy_ovr'], true) : array();
				
				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && count($wdays) > 0) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}
				
				// checkin must be after the beginning of the season
				$checkininclok = true;
				if ($s['checkinincl'] == 1) {
					$checkininclok = false;
					if ($s['from'] < $s['to']) {
						if ($sfrom >= $s['from'] && $sfrom <= $s['to']) {
							$checkininclok = true;
						}
					} else {
						if (($sfrom >= $s['from'] && $sfrom > $s['to']) || ($sfrom < $s['from'] && $sfrom <= $s['to'])) {
							$checkininclok = true;
						}
					}
				}
				if ($checkininclok !== true) {
					continue;
				}

				foreach ($arr as $k => $a) {
					// applied only to some types of price
					if (count($allprices) > 0 && !empty($allprices[0])) {
						if (!in_array("-" . $a[0]['idprice'] . "-", $allprices)) {
							continue;
						}
					}
					// applied only to some room types
					if (!in_array("-" . $a[0]['idroom'] . "-", $allrooms)) {
						continue;
					}
					
					// count affected nights of stay
					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a[0]['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						if ($todayts >= $inits && $todayts <= $ends) {
							// week days
							if ($filterwdays == true) {
								$checkwday = getdate($todayts);
								if (in_array($checkwday['wday'], $wdays)) {
									$affdays++;
								}
							} else {
								$affdays++;
							}
							//
						}
					}
					if (!($affdays > 0)) {
						// no nights affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a[0]['cost'] / $a[0]['days'];

					// modification factor object
					$factor = new stdClass;
					
					// calculate new price progressively
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$factor->pcent = 1;
						$pctval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists($a[0]['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$factor->type = '-';
							$cpercent = 100 - $pctval;
						}
						$factor->amount = $pctval;
						$newprice = ($dailyprice * $cpercent / 100) * $affdays;
					} else {
						// absolute value
						$factor->pcent = 0;
						$absval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists($a[0]['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$newprice = ($dailyprice + $absval) * $affdays;
						} else {
							// discount
							$factor->type = '-';
							$newprice = ($dailyprice - $absval) * $affdays;
						}
						$factor->amount = $absval;
					}
					
					// apply rounding
					$factor->roundmode = $s['roundmode'];
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}
					
					// define the promotion (only if no value overrides set the amount to 0)
					if (count($promotion) && ((isset($absval) && $absval > 0) || $pctval > 0)) {
						/**
						 * Include the discount information (if any). The cost re-calculated may not be
						 * precise if multiple special prices were applied over the same dates.
						 * 
						 * @since 	1.13
						 */
						if ($s['type'] == 2 && $s['diffcost'] > 0) {
							$promotion['discount'] = array(
								'amount' => $s['diffcost'],
								'pcent'	 => (int)($s['val_pcent'] == 2),
							);
						}
						//
						$mem[$k]['promotion'] = $promotion;
					}
					
					// define the occupancy override
					if (array_key_exists($a[0]['idroom'], $occupancy_ovr) && count($occupancy_ovr[$a[0]['idroom']]) > 0) {
						$mem[$k]['occupancy_ovr'] = $occupancy_ovr[$a[0]['idroom']];
					}

					// push difference generated only if to be applied progressively
					if (!$s['promo'] || ($s['promo'] && !$s['promofinalprice'])) {
						/**
						 * Push the difference generated by this special price for later transliteration of final price,
						 * only if the special price is calculated progressively and not on the final price.
						 * 
						 * @since 	1.13.5
						 */
						array_push($mem[$k]['diffs'], ($newprice - ($dailyprice * $affdays)));
					} elseif ($s['promo'] && $s['promofinalprice'] && $factor->pcent) {
						/**
						 * This is a % promotion to be applied on the final price, so we need to save that this memory key 
						 * will need the transliteration, aka adjusting this new price by applying the charge/discount on
						 * all differences applied by the previous special pricing rules.
						 * 
						 * @since 	1.13.5
						 */
						array_push($mem[$k]['trans_keys'], count($mem[$k]['sum']));
						array_push($mem[$k]['trans_factors'], $factor);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
					array_push($roomschange, $a[0]['idroom']);
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && count($v['sum'])) {
						$newprice = 0;
						$dailyprice = $arr[$k][0]['cost'] / $arr[$k][0]['days'];
						$restdays = $arr[$k][0]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;

						// calculate new final cost
						$redo_rounding = null;
						foreach ($v['sum'] as $sum_index => $add) {
							/**
							 * The application of the various special pricing rules is made in a progressive and cumulative way
							 * by always starting from the room base cost or its average daily cost. However, promotions may need
							 * to be applied on the room final cost, and not in a progresive way. In order to keep the progressive
							 * algorithm, for applying the special prices on the room final cost we need to apply the same promotion
							 * onto the differences generated by all the regular and progressively applied special pricing rules.
							 * 
							 * @since 	1.13.5
							 */
							if (in_array($sum_index, $v['trans_keys']) && count($v['diffs'])) {
								/**
								 * This progressive price difference must be applied on the room final cost, so we need to
								 * apply the transliteration over the other differences applied by other special prices.
								 */
								$transliterate_key = array_search($sum_index, $v['trans_keys']);
								if ($transliterate_key !== false && isset($v['trans_factors'][$transliterate_key])) {
									// this is the % promotion we are looking for applying it on the final cost
									$factor = $v['trans_factors'][$transliterate_key];
									if (is_object($factor) && $factor->pcent) {
										$final_factor = 0;
										foreach ($v['diffs'] as $diff_index => $prog_diff) {
											$final_factor += $prog_diff * $factor->amount / 100;
										}
										// update rounding
										$redo_rounding = !empty($factor->roundmode) ? $factor->roundmode : $redo_rounding;
										// apply the final transliteration to obtain a value like if it was applied on the room's final cost
										$add = $factor->type == '+' ? ($add + $final_factor) : ($add - $final_factor);
									}
								}
							}

							// apply new price progressively
							$newprice += $add;
						}

						// apply rounding from factor
						if (!empty($redo_rounding)) {
							$newprice = round($newprice, 0, constant($redo_rounding));
						}

						// set promotion (if any)
						if (isset($v['promotion'])) {
							$arr[$k][0]['promotion'] = $v['promotion'];
						}
						
						// set occupancy overrides (if any)
						if (isset($v['occupancy_ovr'])) {
							$arr[$k][0]['occupancy_ovr'] = $v['occupancy_ovr'];
						}
						
						// set new final cost and update nights affected
						$arr[$k][0]['cost'] = $newprice;
						$arr[$k][0]['affdays'] = $v['daysused'];
					}
				}
			}
		}
		
		// week days with no season
		$roomschange = array_unique($roomschange);
		$q = "SELECT * FROM `#__vikbooking_seasons` WHERE ((`from` = 0 AND `to` = 0) OR (`from` IS NULL AND `to` IS NULL));";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$specials = $dbo->loadAssocList();
			$vbo_tn->translateContents($specials, '#__vikbooking_seasons');
			$applyseasons = false;
			unset($mem);
			$mem = array();
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = array();
			}
			foreach($specials as $s) {
				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					if ($one['year'] != $s['year']) {
						continue;
					}
				}
				//
				$allrooms = explode(",", $s['idrooms']);
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : array();
				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && count($wdays) > 0) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}
				//
				foreach ($arr as $k => $a) {
					// only rooms with no price modifications from seasons
					
					// applied only to some types of price
					if (count($allprices) > 0 && !empty($allprices[0])) {
						if (!in_array("-" . $a[0]['idprice'] . "-", $allprices)) {
							continue;
						}
					}
					
					/**
					 * We should not exclude the rooms that already had a modification of the price through a season
					 * with a dates filter or we risk to get invalid prices by skipping a rule for just some weekdays.
					 * The control " || in_array($a[0]['idroom'], $roomschange)" was removed from the IF below.
					 * 
					 * @since 	1.11
					 */
					if (!in_array("-" . $a[0]['idroom'] . "-", $allrooms)) {
						continue;
					}

					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a[0]['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						// week days
						if ($filterwdays == true) {
							$checkwday = getdate($todayts);
							if (in_array($checkwday['wday'], $wdays)) {
								$affdays++;
							}
						}
					}
					if (!($affdays > 0)) {
						// no nights affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a[0]['cost'] / $a[0]['days'];
					
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$pctval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists($a[0]['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$cpercent = 100 - $pctval;
						}
						$newprice = ($dailyprice * $cpercent / 100) * $affdays;
					} else {
						// absolute value
						$absval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists($a[0]['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$newprice = ($dailyprice + $absval) * $affdays;
						} else {
							// discount
							$newprice = ($dailyprice - $absval) * $affdays;
						}
					}
					
					// apply rounding
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && @count($v['sum']) > 0) {
						$newprice = 0;
						$dailyprice = $arr[$k][0]['cost'] / $arr[$k][0]['days'];
						$restdays = $arr[$k][0]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;
						foreach ($v['sum'] as $add) {
							$newprice += $add;
						}
						$arr[$k][0]['cost'] = $newprice;
						$arr[$k][0]['affdays'] = $v['daysused'];
					}
				}
			}
		}
		// end week days with no season

		return $arr;
	}

	/**
	 * Applies the special prices over an array of tariffs.
	 * The function is also used by VCM (>= 1.6.5) with specific arguments.
	 *
	 * @param 	array  		$arr 			array of tariffs taken from the DB
	 * @param 	int  		$from 			start timestamp
	 * @param 	int  		$to 			end timestamp
	 * @param 	array  		$parsed_season 	array of a season to parse (used to render the seasons calendars in back-end and front-end)
	 * @param 	array  		$seasons_dates 	(VBO 1.10) array of seasons with dates filter taken from the DB to avoid multiple queries (VCM)
	 * @param 	array  		$seasons_wdays 	(VBO 1.10) array of seasons with weekdays filter (only) taken from the DB to avoid multiple queries (VCM)
	 *
	 * @return 	array
	 */
	public static function applySeasonsRoom($arr, $from, $to, $parsed_season = array(), $seasons_dates = array(), $seasons_wdays = array()) {
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();
		$roomschange = array();
		$one = getdate($from);
		
		// leap years
		if ($one['year'] % 4 == 0 && ($one['year'] % 100 != 0 || $one['year'] % 400 == 0)) {
			$isleap = true;
		} else {
			$isleap = false;
		}
		
		$baseone = mktime(0, 0, 0, 1, 1, $one['year']);
		$tomidnightone = intval($one['hours']) * 3600;
		$tomidnightone += intval($one['minutes']) * 60;
		$sfrom = $from - $baseone - $tomidnightone;
		$fromdayts = mktime(0, 0, 0, $one['mon'], $one['mday'], $one['year']);
		$two = getdate($to);
		$basetwo = mktime(0, 0, 0, 1, 1, $two['year']);
		$tomidnighttwo = intval($two['hours']) * 3600;
		$tomidnighttwo += intval($two['minutes']) * 60;
		$sto = $to - $basetwo - $tomidnighttwo;
		
		// leap years, last day of the month of the season
		if ($isleap) {
			$leapts = mktime(0, 0, 0, 2, 29, $two['year']);
			if ($two[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom -= 86400;
				$sto -= 86400;
			} elseif ($sto < $sfrom && $one['year'] < $two['year']) {
				// lower checkin date when in leap year but not for checkout
				$sfrom -= 86400;
			}
		}

		// count nights requested
		$booking_nights = 1;
		foreach ($arr as $k => $a) {
			if (isset($a['days'])) {
				$booking_nights = $a['days'];
				break;
			}
		}
		
		$totseasons = 0;
		if (!count($parsed_season) && !count($seasons_dates)) {
			$q = "SELECT * FROM `#__vikbooking_seasons` WHERE (" .
		 	($sto > $sfrom ? "(`from`<=" . $sfrom . " AND `to`>=" . $sto . ") " : "") .
		 	($sto > $sfrom ? "OR (`from`<=" . $sfrom . " AND `to`>=" . $sfrom . ") " : "(`from`<=" . $sfrom . " AND `to`<=" . $sfrom . " AND `from`>`to`) ") .
		 	($sto > $sfrom ? "OR (`from`<=" . $sto . " AND `to`>=" . $sto . ") " : "OR (`from`>=" . $sto . " AND `to`>=" . $sto . " AND `from`>`to`) ") .
		 	($sto > $sfrom ? "OR (`from`>=" . $sfrom . " AND `from`<=" . $sto . " AND `to`>=" . $sfrom . " AND `to`<=" . $sto . ")" : "OR (`from`>=" . $sfrom . " AND `from`>" . $sto . " AND `to`<" . $sfrom . " AND `to`<=" . $sto . " AND `from`>`to`)") .
		 	($sto > $sfrom ? " OR (`from`<=" . $sfrom . " AND `from`<=" . $sto . " AND `to`<" . $sfrom . " AND `to`<" . $sto . " AND `from`>`to`) OR (`from`>" . $sfrom . " AND `from`>" . $sto . " AND `to`>=" . $sfrom . " AND `to`>=" . $sto . " AND `from`>`to`)" : " OR (`from` <=" . $sfrom . " AND `to` >=" . $sfrom . " AND `from` >" . $sto . " AND `to` >" . $sto . " AND `from` < `to`)") .
		 	($sto > $sfrom ? " OR (`from` >=" . $sfrom . " AND `from` <" . $sto . " AND `to` <" . $sfrom . " AND `to` <" . $sto . " AND `from` > `to`)" : " OR (`from` <" . $sfrom . " AND `to` >=" . $sto . " AND `from` <=" . $sto . " AND `to` <" . $sfrom . " AND `from` < `to`)"). //VBO 1.6 Else part is for Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8
		 	($sto > $sfrom ? " OR (`from` >" . $sfrom . " AND `from` >" . $sto . " AND `to` >=" . $sfrom . " AND `to` <" . $sto . " AND `from` > `to`)" : " OR (`from` >=" . $sfrom . " AND `from` >" . $sto . " AND `to` >" . $sfrom . " AND `to` >" . $sto . " AND `from` < `to`) OR (`from` <" . $sfrom . " AND `from` <" . $sto . " AND `to` <" . $sfrom . " AND `to` <=" . $sto . " AND `from` < `to`)"). //VBO 1.7 Else part for seasons Dec 25 to Dec 31, Jan 2 to Jan 5 - Booking Dec 20 to Jan 7
			") ORDER BY `#__vikbooking_seasons`.`promo` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			$totseasons = $dbo->getNumRows();
		}
		if ($totseasons > 0 || count($parsed_season) > 0 || count($seasons_dates) > 0) {
			if ($totseasons > 0) {
				$seasons = $dbo->loadAssocList();
			} elseif (count($parsed_season) > 0) {
				$seasons = array($parsed_season);
			} else {
				$seasons = $seasons_dates;
			}
			$vbo_tn->translateContents($seasons, '#__vikbooking_seasons');
			$applyseasons = false;
			$mem = array();
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = array();
				$mem[$k]['spids'] = array();
				/**
				 * The keys below are all needed to apply the promotions on the room's final cost.
				 * 
				 * @since 	1.13.5
				 */
				$mem[$k]['diffs'] = array();
				$mem[$k]['trans_keys'] = array();
				$mem[$k]['trans_factors'] = array();
			}
			$affdayslistless = array();
			foreach ($seasons as $s) {
				// VBO 1.10 - double check that the 'from' and 'to' properties are not empty (dates filter), in case VCM passes an array of seasons already taken from the DB
				if (empty($s['from']) && empty($s['to']) && !empty($s['wdays'])) {
					// a season for Jan 1st to Jan 1st (1 day), with NO week-days filter is still accepted
					continue;
				}
				//

				/**
				 * VCM may build a fake season as a "restriction placeholder" if no special prices found.
				 * We need to skip such fake seasons as they do not need any parsing.
				 * 
				 * @since 	1.13
				 */
				if (empty($s['from']) && empty($s['to']) && empty($s['diffcost']) && !isset($s['from'])) {
					continue;
				}
				//

				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					//VBO 1.7 - do not skip seasons tied to the year for bookings between two years
					if ($one['year'] != $s['year'] && $two['year'] != $s['year']) {
						//VBO 1.9 - tied to the year can be set for prev year (Dec 27 to Jan 3) and booking can be Jan 1 to Jan 3 - do not skip in this case
						if (($one['year'] - $s['year']) != 1 || $s['from'] < $s['to']) {
							continue;
						}
						//VBO 1.9 - tied to 2016 going through Jan 2017: dates of December 2017 should skip this speacial price
						if (($one['year'] - $s['year']) == 1 && $s['from'] > $s['to']) {
							$calc_ends = mktime(0, 0, 0, 1, 1, ($s['year'] + 1)) + $s['to'];
							if ($calc_ends < ($from - $tomidnightone)) {
								continue;
							}
						}
					} elseif ($one['year'] < $s['year'] && $two['year'] == $s['year']) {
						//VBO 1.9 - season tied to the year 2017 accross 2018 and we are parsing dates accross prev year 2016-2017
						if ($s['from'] > $s['to']) {
							continue;
						}
					} elseif ($one['year'] == $s['year'] && $two['year'] == $s['year'] && $s['from'] > $s['to']) {
						//VBO 1.9 - season tied to the year 2017 accross 2018 and we are parsing dates at the beginning of 2017 due to beginning loop in 2016 (Rates Overview)
						if (($baseone + $s['from']) > $to) {
							continue;
						}
					}
				}
				//
				$allrooms = !empty($s['idrooms']) ? explode(",", $s['idrooms']) : array();
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : array();
				$inits = $baseone + $s['from'];
				if ($s['from'] < $s['to']) {
					$ends = $basetwo + $s['to'];
					//VikBooking 1.6 check if the inits must be set to the year after
					//ex. Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8 to charge Jan 6,7
					if ($sfrom > $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto <= $s['to'] && $s['from'] < $s['to'] && $sfrom > $sto) {
						$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
						$inits = $tmpbase + $s['from'];
					} elseif ($sfrom >= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						//VBO 1.7 - Season Dec 23 to Dec 29 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom <= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						//VBO 1.7 - Season Dec 30 to Dec 31 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom > $s['from'] && $sfrom > $s['to'] && $sto >= $s['from'] && ($sto >= $s['to'] || $sto <= $s['to']) && $sfrom > $sto) {
						//VBO 1.7 - Season Jan 1 to Jan 2 - Booking Dec 29 to Jan 5
						$inits = $basetwo + $s['from'];
					}
				} else {
					//between 2 years
					if ($baseone < $basetwo) {
						//ex. 29/12/2012 - 14/01/2013
						$ends = $basetwo + $s['to'];
					} else {
						if (($sfrom >= $s['from'] && $sto >= $s['from']) || ($sfrom < $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto > $s['to'])) {
							//ex. 25/12 - 30/12 with init season on 20/12 OR 27/12 for counting 28,29,30/12
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
							$ends = $tmpbase + $s['to'];
						} else {
							//ex. 03/01 - 09/01
							$ends = $basetwo + $s['to'];
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] - 1));
							$inits = $tmpbase + $s['from'];
						}
					}
				}
				// leap years
				if ($isleap == true) {
					$infoseason = getdate($inits);
					$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
					// VikBooking 1.6 added below && $infoseason['year'] == $one['year']
					// for those seasons like 2015 Dec 14 to 2016 Jan 5 and booking dates like 2016 Jan 1 to Jan 6 where 2015 is not leap
					if ($infoseason[0] > $leapts && $infoseason['year'] == $one['year']) {
						/**
						 * Timestamp must be greater than the leap-day of Feb 29th.
						 * It used to be checked for >= $leapts.
						 * 
						 * @since 	July 3rd 2019
						 */
						$inits += 86400;
						$ends += 86400;
					}
				}

				// promotions
				$promotion = array();
				if ($s['promo'] == 1) {
					$daysadv = (($inits - time()) / 86400);
					$daysadv = $daysadv > 0 ? (int)ceil($daysadv) : 0;
					if (!empty($s['promodaysadv']) && $s['promodaysadv'] > $daysadv) {
						continue;
					} elseif (!empty($s['promolastmin']) && $s['promolastmin'] > 0) {
						$secstocheckin = ($from - time());
						if ($s['promolastmin'] < $secstocheckin) {
							// VBO 1.11 - too many seconds to the check-in date, skip this last minute promotion
							continue;
						}
					}
					if ($s['promominlos'] > 1 && $booking_nights < $s['promominlos']) {
						/**
						 * The minimum length of stay parameter is also taken to exclude the promotion from the calculation.
						 * 
						 * @since 	1.13.5
						 */
						continue;
					}
					$promotion['todaydaysadv'] = $daysadv;
					$promotion['promodaysadv'] = $s['promodaysadv'];
					$promotion['promotxt'] = $s['promotxt'];
				}

				// occupancy override
				$occupancy_ovr = !empty($s['occupancy_ovr']) ? json_decode($s['occupancy_ovr'], true) : array();

				//week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && count($wdays) > 0) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}

				// checkin must be after the begin of the season
				$checkininclok = true;
				if ($s['checkinincl'] == 1) {
					$checkininclok = false;
					if ($s['from'] < $s['to']) {
						if ($sfrom >= $s['from'] && $sfrom <= $s['to']) {
							$checkininclok = true;
						}
					} else {
						if (($sfrom >= $s['from'] && $sfrom > $s['to']) || ($sfrom < $s['from'] && $sfrom <= $s['to'])) {
							$checkininclok = true;
						}
					}
				}
				if ($checkininclok !== true) {
					continue;
				}

				foreach ($arr as $k => $a) {
					// applied only to some types of price
					if (count($allprices) > 0 && !empty($allprices[0])) {
						// VikBooking 1.6: Price Calendar sets the idprice to -1
						if (!in_array("-" . $a['idprice'] . "-", $allprices) && $a['idprice'] > 0) {
							continue;
						}
					}
					// applied only to some room types
					if (!in_array("-" . $a['idroom'] . "-", $allrooms)) {
						continue;
					}

					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						if ($todayts >= $inits && $todayts <= $ends) {
							$checkwday = getdate($todayts);
							// week days
							if ($filterwdays == true) {
								if (in_array($checkwday['wday'], $wdays)) {
									if (!isset($arr[$k]['affdayslist'])) {
										$arr[$k]['affdayslist'] = array();
									}
									$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = isset($arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']]) && $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] > 0 ? $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] : 0;
									$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
									$affdayslistless[$s['id']][] = $checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon'];
									$affdays++;
								}
							} else {
								if (!isset($arr[$k]['affdayslist'])) {
									$arr[$k]['affdayslist'] = array();
								}
								$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = isset($arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']]) && $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] > 0 ? $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] : 0;
								$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
								$affdayslistless[$s['id']][] = $checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon'];
								$affdays++;
							}
							//
						}
					}
					
					if (!($affdays > 0)) {
						// no nights affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a['cost'] / $a['days'];

					// modification factor object
					$factor = new stdClass;
					
					// calculate new price progressively
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$factor->pcent = 1;
						$pctval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists($a['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$factor->type = '-';
							$cpercent = 100 - $pctval;
						}
						$factor->amount = $pctval;
						$dailysum = ($dailyprice * $cpercent / 100);
						$newprice = $dailysum * $affdays;
					} else {
						// absolute value
						$factor->pcent = 0;
						$absval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists($a['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$dailysum = ($dailyprice + $absval);
							$newprice = $dailysum * $affdays;
						} else {
							// discount
							$factor->type = '-';
							$dailysum = ($dailyprice - $absval);
							$newprice = $dailysum * $affdays;
						}
						$factor->amount = $absval;
					}
					
					// apply rounding
					$factor->roundmode = $s['roundmode'];
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					// define the promotion (only if no value overrides set the amount to 0)
					if (count($promotion) && ((isset($absval) && $absval > 0) || $pctval > 0)) {
						/**
						 * Include the discount information (if any). The cost re-calculated may not be
						 * precise if multiple special prices were applied over the same dates.
						 * 
						 * @since 	1.13
						 */
						if ($s['type'] == 2 && $s['diffcost'] > 0) {
							$promotion['discount'] = array(
								'amount' => $s['diffcost'],
								'pcent'	 => (int)($s['val_pcent'] == 2),
							);
						}
						//
						$mem[$k]['promotion'] = $promotion;
					}

					// define the occupancy override
					if (array_key_exists($a['idroom'], $occupancy_ovr) && count($occupancy_ovr[$a['idroom']]) > 0) {
						$mem[$k]['occupancy_ovr'] = $occupancy_ovr[$a['idroom']];
					}
					
					// affected days list
					if (isset($arr[$k]['affdayslist']) && is_array($arr[$k]['affdayslist'])) {
						foreach ($arr[$k]['affdayslist'] as $affk => $affv) {
							if (in_array($affk, $affdayslistless[$s['id']])) {
								$arr[$k]['affdayslist'][$affk] = !empty($arr[$k]['affdayslist'][$affk]) && $arr[$k]['affdayslist'][$affk] > 0 ? ($arr[$k]['affdayslist'][$affk] - $arr[$k]['origdailycost'] + $dailysum) : ($affv + $dailysum);
							}
						}
					}

					// push special price ID
					if (!in_array($s['id'], $mem[$k]['spids'])) {
						array_push($mem[$k]['spids'], $s['id']);
					}

					// push difference generated only if to be applied progressively
					if (!$s['promo'] || ($s['promo'] && !$s['promofinalprice'])) {
						/**
						 * Push the difference generated by this special price for later transliteration of final price,
						 * only if the special price is calculated progressively and not on the final price.
						 * 
						 * @since 	1.13.5
						 */
						array_push($mem[$k]['diffs'], ($newprice - ($dailyprice * $affdays)));
					} elseif ($s['promo'] && $s['promofinalprice'] && $factor->pcent) {
						/**
						 * This is a % promotion to be applied on the final price, so we need to save that this memory key 
						 * will need the transliteration, aka adjusting this new price by applying the charge/discount on
						 * all differences applied by the previous special pricing rules.
						 * 
						 * @since 	1.13.5
						 */
						array_push($mem[$k]['trans_keys'], count($mem[$k]['sum']));
						array_push($mem[$k]['trans_factors'], $factor);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
					array_push($roomschange, $a['idroom']);
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && count($v['sum'])) {
						$newprice = 0;
						$dailyprice = $arr[$k]['cost'] / $arr[$k]['days'];
						$restdays = $arr[$k]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;

						// calculate new final cost
						$redo_rounding = null;
						foreach ($v['sum'] as $sum_index => $add) {
							/**
							 * The application of the various special pricing rules is made in a progressive and cumulative way
							 * by always starting from the room base cost or its average daily cost. However, promotions may need
							 * to be applied on the room final cost, and not in a progresive way. In order to keep the progressive
							 * algorithm, for applying the special prices on the room final cost we need to apply the same promotion
							 * onto the differences generated by all the regular and progressively applied special pricing rules.
							 * 
							 * @since 	1.13.5
							 */
							if (in_array($sum_index, $v['trans_keys']) && count($v['diffs'])) {
								/**
								 * This progressive price difference must be applied on the room final cost, so we need to
								 * apply the transliteration over the other differences applied by other special prices.
								 */
								$transliterate_key = array_search($sum_index, $v['trans_keys']);
								if ($transliterate_key !== false && isset($v['trans_factors'][$transliterate_key])) {
									// this is the % promotion we are looking for applying it on the final cost
									$factor = $v['trans_factors'][$transliterate_key];
									if (is_object($factor) && $factor->pcent) {
										$final_factor = 0;
										foreach ($v['diffs'] as $diff_index => $prog_diff) {
											$final_factor += $prog_diff * $factor->amount / 100;
										}
										// update rounding
										$redo_rounding = !empty($factor->roundmode) ? $factor->roundmode : $redo_rounding;
										// apply the final transliteration to obtain a value like if it was applied on the room's final cost
										$add = $factor->type == '+' ? ($add + $final_factor) : ($add - $final_factor);
									}
								}
							}

							// apply new price progressively
							$newprice += $add;
						}

						// apply rounding from factor
						if (!empty($redo_rounding)) {
							$newprice = round($newprice, 0, constant($redo_rounding));
						}
						
						// set promotion (if any)
						if (isset($v['promotion'])) {
							$arr[$k]['promotion'] = $v['promotion'];
						}

						// set occupancy overrides (if any)
						if (isset($v['occupancy_ovr'])) {
							$arr[$k]['occupancy_ovr'] = $v['occupancy_ovr'];
						}

						// set new final cost and update nights affected
						$arr[$k]['cost'] = $newprice;
						$arr[$k]['affdays'] = $v['daysused'];
						if (array_key_exists('spids', $v) && count($v['spids']) > 0) {
							$arr[$k]['spids'] = $v['spids'];
						}
					}
				}
			}
		}
		
		// week days with no season
		$roomschange = array_unique($roomschange);
		$totspecials = 0;
		if (count($seasons_wdays) == 0) {
			$q = "SELECT * FROM `#__vikbooking_seasons` WHERE ((`from` = 0 AND `to` = 0) OR (`from` IS NULL AND `to` IS NULL));";
			$dbo->setQuery($q);
			$dbo->execute();
			$totspecials = $dbo->getNumRows();
		}
		if ($totspecials > 0 || count($seasons_wdays) > 0) {
			$specials = $totspecials > 0 ? $dbo->loadAssocList() : $seasons_wdays;
			$vbo_tn->translateContents($specials, '#__vikbooking_seasons');
			$applyseasons = false;
			/**
			 * We no longer unset the previous memory of the seasons with dates filters
			 * because we need the responses to be merged. We do it only if not set.
			 * We only keep the property 'spids' but the others should be unset.
			 * 
			 * @since 	1.11
			 */
			if (!isset($mem)) {
				$mem = array();
				foreach ($arr as $k => $a) {
					$mem[$k]['daysused'] = 0;
					$mem[$k]['sum'] = array();
					$mem[$k]['spids'] = array();
				}
			} else {
				foreach ($arr as $k => $a) {
					$mem[$k]['daysused'] = 0;
					$mem[$k]['sum'] = array();
				}
			}
			//
			foreach ($specials as $s) {
				// VBO 1.10 - double check that the 'from' and 'to' properties are empty (only weekdays), in case VCM passes an array of seasons already taken from the DB
				if (!empty($s['from']) || !empty($s['to'])) {
					continue;
				}

				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					if ($one['year'] != $s['year']) {
						continue;
					}
				}

				$allrooms = !empty($s['idrooms']) ? explode(",", $s['idrooms']) : array();
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : array();
				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && count($wdays) > 0) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}

				foreach ($arr as $k => $a) {
					// only rooms with no price modifications from seasons
					
					// applied only to some types of price
					if (count($allprices) > 0 && !empty($allprices[0])) {
						// VikBooking 1.6: Price Calendar sets the idprice to -1
						if (!in_array("-" . $a['idprice'] . "-", $allprices) && $a['idprice'] > 0) {
							continue;
						}
					}

					/**
					 * We should not exclude the rooms that already had a modification of the price through a season
					 * with a dates filter or we risk to get invalid prices by skipping a rule for just some weekdays.
					 * The control " || in_array($a['idroom'], $roomschange)" was removed from the IF below.
					 * 
					 * @since 	1.11
					 */
					if (!in_array("-" . $a['idroom'] . "-", $allrooms)) {
						continue;
					}
					
					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						// week days
						if ($filterwdays == true) {
							$checkwday = getdate($todayts);
							if (in_array($checkwday['wday'], $wdays)) {
								$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = 0;
								$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
								$affdays++;
							}
						}
						//
					}

					if (!($affdays > 0)) {
						// no nights affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a['cost'] / $a['days'];
					
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$pctval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists($a['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$cpercent = 100 - $pctval;
						}
						$dailysum = ($dailyprice * $cpercent / 100);
						$newprice = $dailysum * $affdays;
					} else {
						// absolute value
						$absval = $s['diffcost'];
						if (strlen($s['losoverride']) > 0) {
							// values overrides
							$arrvaloverrides = array();
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists($a['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$dailysum = ($dailyprice + $absval);
							$newprice = $dailysum * $affdays;
						} else {
							// discount
							$dailysum = ($dailyprice - $absval);
							$newprice = $dailysum * $affdays;
						}
					}
					
					// apply rounding
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					foreach($arr[$k]['affdayslist'] as $affk => $affv) {
						$arr[$k]['affdayslist'][$affk] = $affv + $dailysum;
					}
					if (!in_array($s['id'], $mem[$k]['spids'])) {
						$mem[$k]['spids'][] = $s['id'];
					}
					$mem[$k]['sum'][] = $newprice;
					$mem[$k]['daysused'] += $affdays;
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && @count($v['sum']) > 0) {
						$newprice = 0;
						$dailyprice = $arr[$k]['cost'] / $arr[$k]['days'];
						$restdays = $arr[$k]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;
						foreach ($v['sum'] as $add) {
							$newprice += $add;
						}
						$arr[$k]['cost'] = $newprice;
						$arr[$k]['affdays'] = $v['daysused'];
						if (array_key_exists('spids', $v) && count($v['spids']) > 0) {
							$arr[$k]['spids'] = $v['spids'];
						}
					}
				}
			}
		}
		// end week days with no season

		return $arr;
	}

	public static function getRoomRplansClosingDates($idroom) {
		$dbo = JFactory::getDbo();
		$closingd = array();
		$q = "SELECT * FROM `#__vikbooking_prices` WHERE `closingd` IS NOT NULL;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$price_records = $dbo->loadAssocList();
			foreach ($price_records as $prec) {
				if (empty($prec['closingd'])) {
					continue;
				}
				$price_closing = json_decode($prec['closingd'], true);
				if (!is_array($price_closing) || !count($price_closing) || !array_key_exists($idroom, $price_closing) || !count($price_closing[$idroom])) {
					continue;
				}
				//check expired dates and clean up
				$today_midnight = mktime(0, 0, 0);
				$cleaned = false;
				foreach ($price_closing[$idroom] as $k => $v) {
					if (strtotime($v) < $today_midnight) {
						$cleaned = true;
						unset($price_closing[$idroom][$k]);
					}
				}
				//
				if (!count($price_closing[$idroom])) {
					unset($price_closing[$idroom]);
				} elseif ($cleaned === true) {
					//reset array keys for smaller JSON size
					$price_closing[$idroom] = array_values($price_closing[$idroom]);
				}
				if ($cleaned === true) {
					$q = "UPDATE `#__vikbooking_prices` SET `closingd`=".(count($price_closing) > 0 ? $dbo->quote(json_encode($price_closing)) : "NULL")." WHERE `id`=".$prec['id'].";";
					$dbo->setQuery($q);
					$dbo->execute();
				}
				if (!isset($price_closing[$idroom]) || !count($price_closing[$idroom])) {
					continue;
				}
				$closingd[$prec['id']] = $price_closing[$idroom];
			}
		}
		return $closingd;
	}

	public static function getRoomRplansClosedInDates($roomids, $checkints, $numnights) {
		$dbo = JFactory::getDbo();
		$closingd = array();
		$q = "SELECT * FROM `#__vikbooking_prices` WHERE `closingd` IS NOT NULL;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0 && count($roomids) > 0) {
			$price_records = $dbo->loadAssocList();
			$info_start = getdate($checkints);
			$checkin_midnight = mktime(0, 0, 0, $info_start['mon'], $info_start['mday'], $info_start['year']);
			$all_nights = array();
			for ($i=0; $i < (int)$numnights; $i++) {
				$next_midnight = mktime(0, 0, 0, $info_start['mon'], ($info_start['mday'] + $i), $info_start['year']);
				$all_nights[] = date('Y-m-d', $next_midnight);
			}
			foreach ($price_records as $prec) {
				if (empty($prec['closingd'])) {
					continue;
				}
				$price_closing = json_decode($prec['closingd'], true);
				if (!is_array($price_closing) || !count($price_closing)) {
					continue;
				}
				foreach ($price_closing as $idroom => $rclosedd) {
					if (!in_array($idroom, $roomids) || !is_array($rclosedd)) {
						continue;
					}
					if (!array_key_exists($idroom, $closingd)) {
						$closingd[$idroom] = array();
					}
					foreach ($all_nights as $night) {
						if (in_array($night, $rclosedd)) {
							if (array_key_exists($prec['id'], $closingd[$idroom])) {
								$closingd[$idroom][$prec['id']][] = $night;
							} else {
								$closingd[$idroom][$prec['id']] = array($night);
							}
						}
					}
				}
			}
		}

		return $closingd;
	}

	public static function areTherePayments() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id` FROM `#__vikbooking_gpayments` WHERE `published`='1';";
		$dbo->setQuery($q);
		$dbo->execute();
		return $dbo->getNumRows() > 0 ? true : false;
	}

	public static function getPayment($idp, $vbo_tn = null) {
		if (!empty($idp)) {
			if (strstr($idp, '=') !== false) {
				$parts = explode('=', $idp);
				$idp = $parts[0];
			}
			$dbo = JFactory::getDbo();
			$q = "SELECT * FROM `#__vikbooking_gpayments` WHERE `id`=" . $dbo->quote($idp) . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$payment = $dbo->loadAssocList();
				if (is_object($vbo_tn)) {
					$vbo_tn->translateContents($payment, '#__vikbooking_gpayments');
				}
				return $payment[0];
			} else {
				return false;
			}
		}
		return false;
	}

	public static function getCronKey() {
		$dbo = JFactory::getDbo();
		$ckey = '';
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='cronkey';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			$ckey = $cval[0]['setting'];
		}
		return $ckey;
	}

	public static function getNextInvoiceNumber () {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='invoiceinum';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) + 1);
	}
	
	public static function getInvoiceNumberSuffix () {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='invoicesuffix';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}
	
	public static function getInvoiceCompanyInfo () {
		$dbo = JFactory::getDbo();
		$q="SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='invcompanyinfo';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s=$dbo->loadAssocList();
		return $s[0]['setting'];
	}

	/**
	 * Gets the number for the next booking receipt generation,
	 * updates the last receipt number used for the later iterations,
	 * stores a new receipt record to keep track of the receipts issued.
	 *
	 * @param 	int 	$bid 		the Booking ID for which we are/want to generating the receipt.
	 * @param 	[int]	$newnum 	the last number used for generating the receipt.
	 *
	 * @return 	int
	 */
	public static function getNextReceiptNumber ($bid, $newnum = false) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='receiptinum';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			//check if this booking has already a receipt, and return that number
			$prev_receipt = array();
			$q = "SELECT * FROM `#__vikbooking_receipts` WHERE `idorder`=".(int)$bid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$prev_receipt = $dbo->loadAssoc();
			}
			//update value (receipt generated)
			if ($newnum !== false && $newnum > 0) {
				$s = (int)$newnum;
				if (!(count($prev_receipt) > 0)) {
					$q = "UPDATE `#__vikbooking_config` SET `setting`=".$s." WHERE `param`='receiptinum';";
					$dbo->setQuery($q);
					$dbo->execute();
					//insert the new receipt record
					$q = "INSERT INTO `#__vikbooking_receipts` (`number`,`idorder`,`created_on`) VALUES (".(int)$newnum.", ".(int)$bid.", ".time().");";
					$dbo->setQuery($q);
					$dbo->execute();
				} else {
					//update receipt record
					$q = "UPDATE `#__vikbooking_receipts` SET `number`=".(int)$newnum.",`created_on`=".time()." WHERE `idorder`=".(int)$bid.";";
					$dbo->setQuery($q);
					$dbo->execute();
				}
			}
			//
			return count($prev_receipt) > 0 ? (int)$prev_receipt['number'] : ((int)$s + 1);
		}
		//first execution of the method should create the configuration record
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('receiptinum', '0');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	public static function getReceiptNotes ($newnotes = false) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='receiptnotes';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			//update value
			if ($newnotes !== false) {
				$s = $newnotes;
				$q = "UPDATE `#__vikbooking_config` SET `setting`=".$dbo->quote($s)." WHERE `param`='receiptnotes';";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			//
			return $s;
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('receiptnotes', '');";
		$dbo->setQuery($q);
		$dbo->execute();
		return "";
	}

	public static function loadColorTagsRules() {
		return array(
			0 => 'VBOCOLORTAGRULECUSTOMCOLOR',
			'pend1' => 'VBWAITINGFORPAYMENT',
			'conf1' => 'VBDBTEXTROOMCLOSED',
			'conf2' => 'VBOCOLORTAGRULECONFTWO',
			'conf3' => 'VBOCOLORTAGRULECONFTHREE',
			'inv1' => 'VBOCOLORTAGRULEINVONE',
			'rcp1' => 'VBOCOLORTAGRULERCPONE',
			'conf4' => 'VBOCOLORTAGRULECONFFOUR',
			'conf5' => 'VBOCOLORTAGRULECONFFIVE',
			'inv2' => 'VBOCOLORTAGRULEINVTWO'
		);
	}

	public static function loadDefaultColorTags() {
		return array(
			array(
				'color' => '#9b9b9b',
				'name' => 'VBWAITINGFORPAYMENT',
				'rule' => 'pend1'
			),
			array(
				'color' => '#333333',
				'name' => 'VBDBTEXTROOMCLOSED',
				'rule' => 'conf1'
			),
			array(
				'color' => '#ff8606',
				'name' => 'VBOCOLORTAGRULECONFTWO',
				'rule' => 'conf2'
			),
			array(
				'color' => '#0418c9',
				'name' => 'VBOCOLORTAGRULECONFTHREE',
				'rule' => 'conf3'
			),
			array(
				'color' => '#bed953',
				'name' => 'VBOCOLORTAGRULEINVONE',
				'rule' => 'inv1'
			),
			array(
				'color' => '#67f5b5',
				'name' => 'VBOCOLORTAGRULERCPONE',
				'rule' => 'rcp1'
			),
			array(
				'color' => '#04d2c2',
				'name' => 'VBOCOLORTAGRULECONFFOUR',
				'rule' => 'conf4'
			),
			array(
				'color' => '#00b316',
				'name' => 'VBOCOLORTAGRULECONFFIVE',
				'rule' => 'conf5'
			),
			array(
				'color' => '#00f323',
				'name' => 'VBOCOLORTAGRULEINVTWO',
				'rule' => 'inv2'
			)
		);
	}

	public static function loadBookingsColorTags() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='bookingsctags';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadResult();
			if (!empty($cval)) {
				$arr_tags = json_decode($cval, true);
				if (is_array($arr_tags)) {
					return $arr_tags;
				}
			}
		}
		return self::loadDefaultColorTags();
	}

	public static function getBestColorContrast($hexcolor) {
		$hexcolor = str_replace('#', '', $hexcolor);
		if (empty($hexcolor) || strlen($hexcolor) != 6) {
			return '#000000';
		}
		$r = hexdec(substr($hexcolor, 0, 2));
		$g = hexdec(substr($hexcolor, 2, 2));
		$b = hexdec(substr($hexcolor, 4, 2));
		//Counting the perceptive luminance - human eye favors green color
		// < 0.5 bright colors
		// > 0.5 dark colors
		return (1 - ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255) < 0.5 ? '#000000' : '#ffffff';
	}

	public static function applyBookingColorTag($booking, $tags = array()) {
		if (!is_array($tags) || !(count($tags) > 0)) {
			$tags = self::loadBookingsColorTags();
		}
		if (array_key_exists('colortag', $booking) && !empty($booking['colortag'])) {
			$color_tag_arr = json_decode($booking['colortag'], true);
			if (is_array($color_tag_arr) && array_key_exists('color', $color_tag_arr)) {
				$color_tag_arr['fontcolor'] = self::getBestColorContrast($color_tag_arr['color']);
				return $color_tag_arr;
			}
		}
		$dbo = JFactory::getDbo();
		$bid = array_key_exists('idorder', $booking) ? $booking['idorder'] : $booking['id'];
		$invoice_numb = false;
		$receipt_numb = false;
		if ($booking['status'] == 'confirmed') {
			$q = "SELECT `b`.`id` AS `o_id`, `i`.`id` AS `inv_id`, `i`.`number` AS `inv_number`, `i`.`file_name` AS `inv_file_name`, `r`.`id` AS `rcp_id`, `r`.`number` AS `rcp_number` FROM `#__vikbooking_orders` AS `b` LEFT JOIN `#__vikbooking_invoices` AS `i` ON `b`.`id`=`i`.`idorder` LEFT JOIN `#__vikbooking_receipts` AS `r` ON `b`.`id`=`r`.`idorder` WHERE `b`.`id`=".(int)$bid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$docs_data = $dbo->loadAssoc();
				$invoice_numb = (!empty($docs_data['inv_id']));
				$receipt_numb = (!empty($docs_data['rcp_id']));

				/**
				 * We inject the invoice number or receipt number into the color tag rules.
				 * This is useful for the Tableaux page to show the invoice/receipt number.
				 * 
				 * @since 	1.1.7
				 */
				if ($invoice_numb || $receipt_numb) {
					foreach ($tags as &$tval) {
						if ($invoice_numb) {
							$tval['invoice_number'] = $docs_data['inv_number'];
							$tval['invoice_file_name'] = $docs_data['inv_file_name'];
						}
						if ($receipt_numb) {
							$tval['receipt_number'] = $docs_data['rcp_number'];
						}
					}
				}
				//
			}
		}
		foreach ($tags as $tkey => $tval) {
			if (empty($tval['rule'])) {
				continue;
			}
			switch ($tval['rule']) {
				case 'pend1':
					//Room is waiting for the payment (locked record)
					if ($booking['status'] == 'standby') {
						$q = "SELECT `id` FROM `#__vikbooking_tmplock` WHERE `idorder`=".(int)$bid." AND `until`>=".time().";";
						$dbo->setQuery($q);
						$dbo->execute();
						if ($dbo->getNumRows() > 0) {
							$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
							return $tval;
						}
					}
					break;
				case 'conf1':
					//Confirmed (Room Closed)
					if ($booking['status'] == 'confirmed' && $booking['custdata'] == JText::translate('VBDBTEXTROOMCLOSED')) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'conf2':
					//Confirmed (No Rate 0.00/NULL Total)
					if ($booking['status'] == 'confirmed' && (empty($booking['total']) || $booking['total'] <= 0.00 || $booking['total'] === null)) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'conf3':
					//Confirmed (Total > 0 && Total Paid = 0 && No Invoice && No Receipt)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && (empty($booking['totpaid']) || $booking['totpaid'] <= 0.00 || $booking['totpaid'] === null) && $invoice_numb === false && $receipt_numb === false) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'inv1':
					//Confirmed + Invoice (Total > 0 && Total Paid = 0 && Invoice Exists)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && (empty($booking['totpaid']) || $booking['totpaid'] <= 0.00 || $booking['totpaid'] === null) && $invoice_numb !== false) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'rcp1':
					//Confirmed + Receipt Issued (Total > 0 && Total Paid = 0 && Receipt Issued)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && (empty($booking['totpaid']) || $booking['totpaid'] <= 0.00 || $booking['totpaid'] === null) && $receipt_numb !== false) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'conf4':
					//Confirmed (Total > 0 && Total Paid > 0 && Total Paid < Total)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && $booking['totpaid'] > 0 && $booking['totpaid'] < $booking['total']) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'conf5':
					//Confirmed (Total > 0 && Total Paid >= Total)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && $booking['totpaid'] > 0 && $booking['totpaid'] >= $booking['total']) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'inv2':
					//Confirmed + Invoice + Paid (Total > 0 && Total Paid >= Total && Invoice Exists)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && $booking['totpaid'] > 0 && $booking['totpaid'] >= $booking['total'] && $invoice_numb !== false) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				default:
					break;
			}
		}
		return array();
	}

	public static function getBookingInfoFromID($bid) {
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".(int)$bid.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$booking_info = $dbo->loadAssoc();
			return $booking_info;
		}
		return array();
	}

	public static function loadRoomIndexesBookings($roomid, $room_bids_pool) {
		$dbo = JFactory::getDbo();
		$room_features_bookings = array();
		if (!empty($roomid) && count($room_bids_pool) > 0) {
			$all_bids = array();
			foreach ($room_bids_pool as $day => $bids) {
				$all_bids = array_merge($all_bids, $bids);
			}
			$all_bids = array_unique($all_bids);
			$q = "SELECT `id`,`idorder`,`roomindex` FROM `#__vikbooking_ordersrooms` WHERE `idroom`=".(int)$roomid." AND `idorder` IN (".implode(', ', $all_bids).");";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$rbookings = $dbo->loadAssocList();
				foreach ($rbookings as $k => $v) {
					if (empty($v['roomindex'])) {
						continue;
					}
					if (!array_key_exists($v['roomindex'], $room_features_bookings)) {
						$room_features_bookings[$v['roomindex']] = array();
					}
					$room_features_bookings[$v['roomindex']][] = $v['idorder'];
				}
			}
		}

		return $room_features_bookings;
	}

	public static function getSendEmailWhen() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='emailsendwhen';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			return intval($cval[0]['setting']) > 1 ? 2 : 1;
		} else {
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('emailsendwhen','1');";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		return 1;
	}

	public static function getMinutesAutoRemove() {
		$dbo = JFactory::getDbo();
		$minar = 0;
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='minautoremove';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$minar = (int)$dbo->loadResult();
		}
		return $minar;
	}

	public static function getSMSAPIClass() {
		$dbo = JFactory::getDbo();
		$cfile = '';
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smsapi';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			$cfile = $cval[0]['setting'];
		}
		return $cfile;
	}

	public static function autoSendSMSEnabled() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smsautosend';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			return intval($cval[0]['setting']) > 0 ? true : false;
		}
		return false;
	}

	public static function getSendSMSTo() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smssendto';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			if (!empty($cval[0]['setting'])) {
				$sto = json_decode($cval[0]['setting'], true);
				if (is_array($sto)) {
					return $sto;
				}
			}
		}
		return array();
	}

	public static function getSendSMSWhen() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smssendwhen';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			return intval($cval[0]['setting']) > 1 ? 2 : 1;
		}
		return 1;
	}

	public static function getSMSAdminPhone() {
		$dbo = JFactory::getDbo();
		$pnum = '';
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smsadminphone';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			$pnum = $cval[0]['setting'];
		}
		return $pnum;
	}

	public static function getSMSParams($as_array = true) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smsparams';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			if (!empty($cval[0]['setting'])) {
				if (!$as_array) {
					return $cval[0]['setting'];
				}
				$sparams = json_decode($cval[0]['setting'], true);
				if (is_array($sparams)) {
					return $sparams;
				}
			}
		}
		return array();
	}

	public static function getSMSTemplate($vbo_tn = null, $booking_status = 'confirmed', $type = 'admin') {
		$dbo = JFactory::getDbo();
		switch (strtolower($booking_status)) {
			case 'standby':
				$status = 'pend';
				break;
			case 'cancelled':
				$status = 'canc';
				break;
			default:
				$status = '';
				break;
		}
		$paramtype = 'sms'.$type.'tpl'.$status;
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='".$paramtype."';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			if ($status == 'canc') {
				//Type cancelled is used by VCM since v1.6.6
				$q = "INSERT INTO `#__vikbooking_texts` (`param`,`exp`,`setting`) VALUES ('".$paramtype."','".($type == 'admin' ? 'Administrator' : 'Customer')." SMS Template (Cancelled)','');";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			return '';
		}
		$ft = $dbo->loadAssocList();
		if (is_object($vbo_tn)) {
			$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		}
		return $ft[0]['setting'];
	}

	public static function getSMSAdminTemplate($vbo_tn = null, $booking_status = 'confirmed') {
		return self::getSMSTemplate($vbo_tn, $booking_status, 'admin');
	}

	public static function getSMSCustomerTemplate($vbo_tn = null, $booking_status = 'confirmed') {
		return self::getSMSTemplate($vbo_tn, $booking_status, 'customer');
	}

	public static function checkPhonePrefixCountry($phone, $country_threecode) {
		$dbo = JFactory::getDbo();
		$phone = str_replace(" ", '', trim($phone));
		$cprefix = '';
		if (!empty($country_threecode)) {
			$q = "SELECT `phone_prefix` FROM `#__vikbooking_countries` WHERE `country_3_code`=".$dbo->quote($country_threecode).";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$cprefix = $dbo->loadResult();
				$cprefix = str_replace(" ", '', trim($cprefix));
			}
		}
		if (!empty($cprefix)) {
			if (substr($phone, 0, 1) != '+') {
				if (substr($phone, 0, 2) == '00') {
					$phone = '+'.substr($phone, 2);
				} else {
					$phone = $cprefix.$phone;
				}
			}
		}
		return $phone;
	}

	public static function parseAdminSMSTemplate($booking, $booking_rooms, $vbo_tn = null) {
		$tpl = self::getSMSAdminTemplate($vbo_tn, $booking['status']);

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($booking, $booking_rooms))
			->parseTokens($tpl);
		//

		$vbo_df = self::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
		$datesep = self::getDateSeparator();
		$tpl = str_replace('{customer_name}', $booking['customer_name'], $tpl);
		$tpl = str_replace('{booking_id}', $booking['id'], $tpl);
		$tpl = str_replace('{checkin_date}', date(str_replace("/", $datesep, $df), $booking['checkin']), $tpl);
		$tpl = str_replace('{checkout_date}', date(str_replace("/", $datesep, $df), $booking['checkout']), $tpl);
		$tpl = str_replace('{num_nights}', $booking['days'], $tpl);
		$rooms_booked = array();
		$rooms_names = array();
		$tot_adults = 0;
		$tot_children = 0;
		$tot_guests = 0;
		foreach ($booking_rooms as $broom) {
			$rooms_names[] = $broom['room_name'];
			if (array_key_exists($broom['room_name'], $rooms_booked)) {
				$rooms_booked[$broom['room_name']] += 1;
			} else {
				$rooms_booked[$broom['room_name']] = 1;
			}
			$tot_adults += (int)$broom['adults'];
			$tot_children += (int)$broom['children'];
			$tot_guests += ((int)$broom['adults'] + (int)$broom['children']);
		}
		$tpl = str_replace('{tot_adults}', $tot_adults, $tpl);
		$tpl = str_replace('{tot_children}', $tot_children, $tpl);
		$tpl = str_replace('{tot_guests}', $tot_guests, $tpl);
		$rooms_booked_quant = array();
		foreach ($rooms_booked as $rname => $quant) {
			$rooms_booked_quant[] = ($quant > 1 ? $quant.' ' : '').$rname;
		}
		$tpl = str_replace('{rooms_booked}', implode(', ', $rooms_booked_quant), $tpl);
		$tpl = str_replace('{rooms_names}', implode(', ', $rooms_names), $tpl);
		$tpl = str_replace('{customer_country}', $booking['country_name'], $tpl);
		$tpl = str_replace('{customer_email}', $booking['custmail'], $tpl);
		$tpl = str_replace('{customer_phone}', $booking['phone'], $tpl);
		$tpl = str_replace('{total}', self::numberFormat($booking['total']), $tpl);
		$tpl = str_replace('{total_paid}', self::numberFormat($booking['totpaid']), $tpl);
		$remaining_bal = $booking['total'] - $booking['totpaid'];
		$tpl = str_replace('{remaining_balance}', self::numberFormat($remaining_bal), $tpl);

		return $tpl;
	}

	public static function parseCustomerSMSTemplate($booking, $booking_rooms, $vbo_tn = null, $force_text = null) {
		$tpl = !empty($force_text) ? $force_text : self::getSMSCustomerTemplate($vbo_tn, $booking['status']);

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($booking, $booking_rooms))
			->parseTokens($tpl);
		//

		$vbo_df = self::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
		$datesep = self::getDateSeparator();
		$tpl = str_replace('{customer_name}', $booking['customer_name'], $tpl);
		$tpl = str_replace('{booking_id}', $booking['id'], $tpl);
		$tpl = str_replace('{checkin_date}', date(str_replace("/", $datesep, $df), $booking['checkin']), $tpl);
		$tpl = str_replace('{checkout_date}', date(str_replace("/", $datesep, $df), $booking['checkout']), $tpl);
		$tpl = str_replace('{num_nights}', $booking['days'], $tpl);
		$rooms_booked = array();
		$rooms_names = array();
		$tot_adults = 0;
		$tot_children = 0;
		$tot_guests = 0;
		foreach ($booking_rooms as $broom) {
			$rooms_names[] = $broom['room_name'];
			if (array_key_exists($broom['room_name'], $rooms_booked)) {
				$rooms_booked[$broom['room_name']] += 1;
			} else {
				$rooms_booked[$broom['room_name']] = 1;
			}
			$tot_adults += (int)$broom['adults'];
			$tot_children += (int)$broom['children'];
			$tot_guests += ((int)$broom['adults'] + (int)$broom['children']);
		}
		$tpl = str_replace('{tot_adults}', $tot_adults, $tpl);
		$tpl = str_replace('{tot_children}', $tot_children, $tpl);
		$tpl = str_replace('{tot_guests}', $tot_guests, $tpl);
		$rooms_booked_quant = array();
		foreach ($rooms_booked as $rname => $quant) {
			$rooms_booked_quant[] = ($quant > 1 ? $quant.' ' : '').$rname;
		}
		$tpl = str_replace('{rooms_booked}', implode(', ', $rooms_booked_quant), $tpl);
		$tpl = str_replace('{rooms_names}', implode(', ', $rooms_names), $tpl);
		$tpl = str_replace('{total}', self::numberFormat($booking['total']), $tpl);
		$tpl = str_replace('{total_paid}', self::numberFormat($booking['totpaid']), $tpl);
		$remaining_bal = $booking['total'] - $booking['totpaid'];
		$tpl = str_replace('{remaining_balance}', self::numberFormat($remaining_bal), $tpl);
		$tpl = str_replace('{customer_pin}', $booking['customer_pin'], $tpl);
		$book_link = JUri::root().'index.php?option=com_vikbooking&view=booking&sid='.$booking['sid'].'&ts='.$booking['ts'];
		/**
		 * @wponly 	Rewrite URI for front-end depending on the client
		 */
		$book_link 	= str_replace(JUri::root(), '', $book_link);
		if (self::isSite()) {
			$model 	= JModel::getInstance('vikbooking', 'shortcodes', 'admin');
			$itemid = $model->best(array('booking'));
			if ($itemid) {
				$book_link = JRoute::rewrite($book_link . "&Itemid={$itemid}", false);
			}
		} else {
			$model 	= JModel::getInstance('vikbooking', 'shortcodes');
			$itemid = $model->best(array('booking'));
			if ($itemid) {
				$book_link = JRoute::rewrite($book_link . "&Itemid={$itemid}", false);
			}
		}
		//
		$tpl = str_replace('{booking_link}', $book_link, $tpl);

		return $tpl;
	}

	public static function sendBookingSMS($oid, $skip_send_to = array(), $force_send_to = array(), $force_text = null) {
		$dbo = JFactory::getDbo();
		if (!class_exists('VboApplication')) {
			require_once(VBO_ADMIN_PATH.DS.'helpers'.DS.'jv_helper.php');
		}
		$vbo_app = new VboApplication;
		if (empty($oid)) {
			return false;
		}
		$sms_api = self::getSMSAPIClass();
		if (empty($sms_api)) {
			return false;
		}
		if (!is_file(VBO_ADMIN_PATH.DS.'smsapi'.DS.$sms_api)) {
			return false;
		}
		$sms_api_params = self::getSMSParams();
		if (!is_array($sms_api_params) || !(count($sms_api_params) > 0)) {
			return false;
		}
		if (!self::autoSendSMSEnabled() && !(count($force_send_to) > 0)) {
			return false;
		}
		$send_sms_to = self::getSendSMSTo();
		if (!(count($send_sms_to) > 0) && !(count($force_send_to) > 0)) {
			return false;
		}
		$booking = array();
		$q = "SELECT `o`.*,`co`.`idcustomer`,CONCAT_WS(' ',`c`.`first_name`,`c`.`last_name`) AS `customer_name`,`c`.`pin` AS `customer_pin`,`nat`.`country_name` FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `co`.`idorder`=`o`.`id` AND `co`.`idorder`=".(int)$oid." LEFT JOIN `#__vikbooking_customers` `c` ON `c`.`id`=`co`.`idcustomer` LEFT JOIN `#__vikbooking_countries` `nat` ON `nat`.`country_3_code`=`o`.`country` WHERE `o`.`id`=".(int)$oid.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$booking = $dbo->loadAssoc();
		}
		if (!(count($booking) > 0)) {
			return false;
		}
		if (strtolower($booking['status']) == 'standby' && self::getSendSMSWhen() < 2) {
			return false;
		}
		$booking_rooms = array();
		$q = "SELECT `or`.*,`r`.`name` AS `room_name` FROM `#__vikbooking_ordersrooms` AS `or` LEFT JOIN `#__vikbooking_rooms` `r` ON `r`.`id`=`or`.`idroom` WHERE `or`.`idorder`=".(int)$booking['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$booking_rooms = $dbo->loadAssocList();
		}
		$admin_phone = self::getSMSAdminPhone();
		$admin_sendermail = self::getSenderMail();
		$admin_email = self::getAdminMail();
		$f_result = false;
		require_once(VBO_ADMIN_PATH.DS.'smsapi'.DS.$sms_api);
		if ((in_array('admin', $send_sms_to) && !empty($admin_phone) && !in_array('admin', $skip_send_to)) || in_array('admin', $force_send_to)) {
			//SMS for the administrator
			$sms_text = self::parseAdminSMSTemplate($booking, $booking_rooms);
			if (!empty($sms_text)) {
				$sms_obj = new VikSmsApi($booking, $sms_api_params);
				//administrator phone can contain multiple numbers separated by comma or semicolon
				$admin_phones = array();
				if (strpos($admin_phone, ',') !== false) {
					$all_phones = explode(',', $admin_phone);
					foreach ($all_phones as $aph) {
						if (!empty($aph)) {
							$admin_phones[] = trim($aph);
						}
					}
				} elseif (strpos($admin_phone, ';') !== false) {
					$all_phones = explode(';', $admin_phone);
					foreach ($all_phones as $aph) {
						if (!empty($aph)) {
							$admin_phones[] = trim($aph);
						}
					}
				} else {
					$admin_phones[] = $admin_phone;
				}
				foreach ($admin_phones as $admphone) {
					$response_obj = $sms_obj->sendMessage($admphone, $sms_text);
					if ( !$sms_obj->validateResponse($response_obj) ) {
						//notify the administrator via email with the error of the SMS sending
						$vbo_app->sendMail($admin_sendermail, $admin_sendermail, $admin_email, $admin_sendermail, JText::translate('VBOSENDSMSERRMAILSUBJ'), JText::translate('VBOSENDADMINSMSERRMAILTXT')."<br />".$sms_obj->getLog(), true);
					} else {
						$f_result = true;
					}
				}
			}
		}
		if ((in_array('customer', $send_sms_to) && !empty($booking['phone']) && !in_array('customer', $skip_send_to)) || in_array('customer', $force_send_to)) {
			//SMS for the Customer
			$vbo_tn = self::getTranslator();
			$vbo_tn->translateContents($booking_rooms, '#__vikbooking_rooms', array('id' => 'idroom', 'room_name' => 'name'));
			$sms_text = self::parseCustomerSMSTemplate($booking, $booking_rooms, $vbo_tn, $force_text);
			if (!empty($sms_text)) {
				$sms_obj = new VikSmsApi($booking, $sms_api_params);
				$response_obj = $sms_obj->sendMessage($booking['phone'], $sms_text);
				if ( !$sms_obj->validateResponse($response_obj) ) {
					//notify the administrator via email with the error of the SMS sending
					$vbo_app->sendMail($admin_sendermail, $admin_sendermail, $admin_email, $admin_sendermail, JText::translate('VBOSENDSMSERRMAILSUBJ'), JText::translate('VBOSENDCUSTOMERSMSERRMAILTXT')."<br />".$sms_obj->getLog(), true);
				} else {
					$f_result = true;
				}
			}
		}
		return $f_result;
	}

	public static function loadInvoiceTmpl($booking_info = array(), $booking_rooms = array()) {
		if (!defined('_VIKBOOKINGEXEC')) {
			define('_VIKBOOKINGEXEC', '1');
		}
		ob_start();
		include VBO_SITE_PATH . DS . "helpers" . DS . "invoices" . DS . "invoice_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		$default_params = array(
			'show_header' => 0,
			'header_data' => array(),
			'show_footer' => 0,
			'pdf_page_orientation' => 'PDF_PAGE_ORIENTATION',
			'pdf_unit' => 'PDF_UNIT',
			'pdf_page_format' => 'PDF_PAGE_FORMAT',
			'pdf_margin_left' => 'PDF_MARGIN_LEFT',
			'pdf_margin_top' => 'PDF_MARGIN_TOP',
			'pdf_margin_right' => 'PDF_MARGIN_RIGHT',
			'pdf_margin_header' => 'PDF_MARGIN_HEADER',
			'pdf_margin_footer' => 'PDF_MARGIN_FOOTER',
			'pdf_margin_bottom' => 'PDF_MARGIN_BOTTOM',
			'pdf_image_scale_ratio' => 'PDF_IMAGE_SCALE_RATIO',
			'header_font_size' => '10',
			'body_font_size' => '10',
			'footer_font_size' => '8'
		);
		if (defined('_VIKBOOKING_INVOICE_PARAMS') && isset($invoice_params) && @count($invoice_params) > 0) {
			$default_params = array_merge($default_params, $invoice_params);
		}
		return array($content, $default_params);
	}

	/**
	 * Includes within a buffer the template file for the custom (manual) invoice.
	 * 
	 * @param 	array 	$invoice 	the record of the custom invoice
	 * @param 	array 	$customer 	the customer record
	 * 
	 * @return 	string 	the HTML content of the template file
	 *
	 * @since 	1.11.1
	 */
	public static function loadCustomInvoiceTmpl($invoice, $customer) {
		if (!defined('_VIKBOOKINGEXEC')) {
			define('_VIKBOOKINGEXEC', '1');
		}
		ob_start();
		include VBO_SITE_PATH . DS . "helpers" . DS . "invoices" . DS . "custom_invoice_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		$default_params = array(
			'show_header' => 0,
			'header_data' => array(),
			'show_footer' => 0,
			'pdf_page_orientation' => 'PDF_PAGE_ORIENTATION',
			'pdf_unit' => 'PDF_UNIT',
			'pdf_page_format' => 'PDF_PAGE_FORMAT',
			'pdf_margin_left' => 'PDF_MARGIN_LEFT',
			'pdf_margin_top' => 'PDF_MARGIN_TOP',
			'pdf_margin_right' => 'PDF_MARGIN_RIGHT',
			'pdf_margin_header' => 'PDF_MARGIN_HEADER',
			'pdf_margin_footer' => 'PDF_MARGIN_FOOTER',
			'pdf_margin_bottom' => 'PDF_MARGIN_BOTTOM',
			'pdf_image_scale_ratio' => 'PDF_IMAGE_SCALE_RATIO',
			'header_font_size' => '10',
			'body_font_size' => '10',
			'footer_font_size' => '8'
		);
		if (defined('_VIKBOOKING_INVOICE_PARAMS') && isset($invoice_params) && @count($invoice_params) > 0) {
			$default_params = array_merge($default_params, $invoice_params);
		}
		return array($content, $default_params);
	}

	public static function parseInvoiceTemplate($invoicetpl, $booking, $booking_rooms, $invoice_num, $invoice_suff, $invoice_date, $company_info, $vbo_tn = null, $is_front = false) {
		$parsed = $invoicetpl;

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($booking, $booking_rooms))
			->parseTokens($parsed);
		//

		$dbo = JFactory::getDbo();
		if (is_null($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$nowdf = self::getDateFormat();
		if ($nowdf=="%d/%m/%Y") {
			$df='d/m/Y';
		} elseif ($nowdf=="%m/%d/%Y") {
			$df='m/d/Y';
		} else {
			$df='Y/m/d';
		}
		$datesep = self::getDateSeparator();
		$companylogo = self::getSiteLogo();
		$uselogo = '';
		if (!empty($companylogo)) {
			/**
			 * Let's try to prevent TCPDF errors, as we cannot rely just on the client var $is_front.
			 * 
			 * @deprecated 	usage of the variable $is_front.
			 * 
			 * @since 		August 2nd 2019
			 */
			if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				$uselogo = '<img src="' . VBO_ADMIN_URI_REL . 'resources/' . $companylogo . '"/>';
			} elseif (is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				$uselogo = '<img src="' . VBO_SITE_URI_REL . 'resources/' . $companylogo . '"/>';
			}
		}
		$parsed = str_replace("{company_logo}", $uselogo, $parsed);
		$parsed = str_replace("{company_info}", $company_info, $parsed);
		$parsed = str_replace("{invoice_number}", $invoice_num, $parsed);
		$parsed = str_replace("{invoice_suffix}", $invoice_suff, $parsed);
		$parsed = str_replace("{invoice_date}", $invoice_date, $parsed);
		$parsed = str_replace("{customer_info}", nl2br(rtrim($booking['custdata'], "\n")), $parsed);
		//custom fields replace
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $cmatches);
		if (is_array($cmatches[1]) && @count($cmatches[1]) > 0) {
			$cfids = array();
			foreach($cmatches[1] as $cfid ){
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$dbo->execute();
			$cfields = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
			$vbo_tn->translateContents($cfields, '#__vikbooking_custfields');
			$cfmap = array();
			if (is_array($cfields)) {
				foreach($cfields as $cf) {
					$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
				}
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $booking['custdata']);
			if (count($partsreceived) > 0) {
				foreach($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach($cmatches[1] as $cfid ){
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}
		//end custom fields replace
		//invoice price description - Start
		$rooms = array();
		$tars = array();
		$arrpeople = array();
		$is_package = !empty($booking['pkg']) ? true : false;
		$tot_adults = 0;
		$tot_children = 0;
		$tot_guests = 0;
		foreach($booking_rooms as $kor => $or) {
			$num = $kor + 1;
			$rooms[$num] = $or;
			$arrpeople[$num]['adults'] = $or['adults'];
			$arrpeople[$num]['children'] = $or['children'];
			$tot_adults += $or['adults'];
			$tot_children += $or['children'];
			$tot_guests += ($or['adults'] + $or['children']);
			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				//package or custom cost set from the back-end
				continue;
			}
			$q="SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=" . (int)$or['idtar'] . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$tar = $dbo->loadAssocList();
				$tar = self::applySeasonsRoom($tar, $booking['checkin'], $booking['checkout']);
				//different usage
				if ($or['fromadult'] <= $or['adults'] && $or['toadult'] >= $or['adults']) {
					$diffusageprice = self::loadAdultsDiff($or['idroom'], $or['adults']);
					//Occupancy Override
					$occ_ovr = self::occupancyOverrideExists($tar, $or['adults']);
					$diffusageprice = $occ_ovr !== false ? $occ_ovr : $diffusageprice;
					//
					if (is_array($diffusageprice)) {
						//set a charge or discount to the price(s) for the different usage of the room
						foreach($tar as $kpr => $vpr) {
							$tar[$kpr]['diffusage'] = $or['adults'];
							if ($diffusageprice['chdisc'] == 1) {
								//charge
								if ($diffusageprice['valpcent'] == 1) {
									//fixed value
									$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
									$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
									$tar[$kpr]['diffusagecost'] = "+".$aduseval;
									$tar[$kpr]['room_base_cost'] = $vpr['cost'];
									$tar[$kpr]['cost'] = $vpr['cost'] + $aduseval;
								} else {
									//percentage value
									$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
									$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $tar[$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
									$tar[$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
									$tar[$kpr]['room_base_cost'] = $vpr['cost'];
									$tar[$kpr]['cost'] = $aduseval;
								}
							} else {
								//discount
								if ($diffusageprice['valpcent'] == 1) {
									//fixed value
									$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
									$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
									$tar[$kpr]['diffusagecost'] = "-".$aduseval;
									$tar[$kpr]['room_base_cost'] = $vpr['cost'];
									$tar[$kpr]['cost'] = $vpr['cost'] - $aduseval;
								} else {
									//percentage value
									$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
									$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $tar[$kpr]['days']) * $diffusageprice['value'] / 100) * $tar[$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
									$tar[$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
									$tar[$kpr]['room_base_cost'] = $vpr['cost'];
									$tar[$kpr]['cost'] = $aduseval;
								}
							}
						}
					}
				}
				//
				$tars[$num] = $tar[0];
			}
		}
		$parsed = str_replace("{checkin_date}", date(str_replace("/", $datesep, $df), $booking['checkin']), $parsed);
		$parsed = str_replace("{checkout_date}", date(str_replace("/", $datesep, $df), $booking['checkout']), $parsed);
		$parsed = str_replace("{num_nights}", $booking['days'], $parsed);
		$parsed = str_replace("{tot_guests}", $tot_guests, $parsed);
		$parsed = str_replace("{tot_adults}", $tot_adults, $parsed);
		$parsed = str_replace("{tot_children}", $tot_children, $parsed);
		$isdue = 0;
		$tot_taxes = 0;
		$tot_city_taxes = 0;
		$tot_fees = 0;
		$pricestr = array();
		$optstr = array();
		foreach ($booking_rooms as $kor => $or) {
			$num = $kor + 1;
			$pricestr[$num] = array();
			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				// package cost or cust_cost may not be inclusive of taxes if prices tax included is off
				$calctar = self::sayPackagePlusIva($or['cust_cost'], $or['cust_idiva']);
				$cost_minus_tax = self::sayPackageMinusIva($or['cust_cost'], $or['cust_idiva']);
				$pricestr[$num]['name'] = (!empty($or['pkg_name']) ? $or['pkg_name'] : (!empty($or['otarplan']) ? ucwords($or['otarplan']) : JText::translate('VBOROOMCUSTRATEPLAN')));
				$pricestr[$num]['tot'] = $calctar;
				$pricestr[$num]['tax'] = ($calctar - $cost_minus_tax);
				$tot_taxes += ($calctar - $cost_minus_tax);
				$isdue += $calctar;
			} elseif (array_key_exists($num, $tars) && is_array($tars[$num])) {
				$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
				$calctar = self::sayCostPlusIva($display_rate, $tars[$num]['idprice']);
				$pricestr[$num]['name'] = self::getPriceName($tars[$num]['idprice'], $vbo_tn) . (!empty($tars[$num]['attrdata']) ? "\n" . self::getPriceAttr($tars[$num]['idprice'], $vbo_tn) . ": " . $tars[$num]['attrdata'] : "");
				$pricestr[$num]['tot'] = $calctar;
				$tars[$num]['calctar'] = $calctar;
				$isdue += $calctar;
				if ($calctar == $display_rate) {
					$cost_minus_tax = self::sayCostMinusIva($display_rate, $tars[$num]['idprice']);
					$tot_taxes += ($display_rate - $cost_minus_tax);
					$pricestr[$num]['tax'] = ($display_rate - $cost_minus_tax);
				} else {
					$tot_taxes += ($calctar - $display_rate);
					$pricestr[$num]['tax'] = ($calctar - $display_rate);
				}
			}
			$optstr[$num] = array();
			$opt_ind = 0;
			if (!empty($or['optionals'])) {
				$stepo = explode(";", $or['optionals']);
				foreach ($stepo as $roptkey => $oo) {
					if (!empty($oo)) {
						$stept = explode(":", $oo);
						$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
						$dbo->setQuery($q);
						$dbo->execute();
						if ($dbo->getNumRows() == 1) {
							$actopt = $dbo->loadAssocList();
							if (is_object($vbo_tn)) {
								$vbo_tn->translateContents($actopt, '#__vikbooking_optionals');
							}
							$optstr[$num][$opt_ind] = array();
							$chvar = '';
							if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
								$optagenames = self::getOptionIntervalsAges($actopt[0]['ageintervals']);
								$optagepcent = self::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
								$optageovrct = self::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
								$child_num 	 = self::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
								$optagecosts = self::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
								$agestept = explode('-', $stept[1]);
								$stept[1] = $agestept[0];
								$chvar = $agestept[1];
								if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
									//percentage value of the adults tariff
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
									} else {
										$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
								} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
									//VBO 1.10 - percentage value of room base cost
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
									} else {
										$display_rate = isset($tars[$num]['room_base_cost']) ? $tars[$num]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost']);
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
								}
								$actopt[0]['chageintv'] = $chvar;
								$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
								$actopt[0]['quan'] = $stept[1];
								$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $booking['days'] * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
							} else {
								$actopt[0]['quan'] = $stept[1];
								// VBO 1.11 - options percentage cost of the room total fee
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$deftar_basecosts = $or['cust_cost'];
								} else {
									$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
								}
								$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
								//
								$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $booking['days'] * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
							}
							if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
								$realcost = $actopt[0]['maxprice'];
								if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
									$realcost = $actopt[0]['maxprice'] * $stept[1];
								}
							}
							if ($actopt[0]['perperson'] == 1) {
								$realcost = $realcost * $or['adults'];
							}
							$tmpopr = self::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
							$optstr[$num][$opt_ind]['name'] = ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'];
							$optstr[$num][$opt_ind]['tot'] = $tmpopr;
							$optstr[$num][$opt_ind]['tax'] = 0;
							if ($actopt[0]['is_citytax'] == 1) {
								$tot_city_taxes += $tmpopr;
							} elseif ($actopt[0]['is_fee'] == 1) {
								$tot_fees += $tmpopr;
							}
							// VBO 1.11 - always calculate the amount of tax no matter if this is already a tax or a fee
							if ($tmpopr == $realcost) {
								$opt_minus_tax = self::sayOptionalsMinusIva($realcost, $actopt[0]['idiva']);
								$tot_taxes += ($realcost - $opt_minus_tax);
								$optstr[$num][$opt_ind]['tax'] = ($realcost - $opt_minus_tax);
							} else {
								$tot_taxes += ($tmpopr - $realcost);
								$optstr[$num][$opt_ind]['tax'] = ($tmpopr - $realcost);
							}
							//
							$opt_ind++;
							$isdue += $tmpopr;
						}
					}
				}
			}
			//custom extra costs
			if (!empty($or['extracosts'])) {
				$cur_extra_costs = json_decode($or['extracosts'], true);
				foreach ($cur_extra_costs as $eck => $ecv) {
					$ecplustax = !empty($ecv['idtax']) ? self::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
					$isdue += $ecplustax;
					$optstr[$num][$opt_ind]['name'] = $ecv['name'];
					$optstr[$num][$opt_ind]['tot'] = $ecplustax;
					$optstr[$num][$opt_ind]['tax'] = 0;
					if ($ecplustax == $ecv['cost']) {
						$ec_minus_tax = !empty($ecv['idtax']) ? self::sayOptionalsMinusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
						$tot_taxes += ($ecv['cost'] - $ec_minus_tax);
						$optstr[$num][$opt_ind]['tax'] = ($ecv['cost'] - $ec_minus_tax);
					} else {
						$tot_taxes += ($ecplustax - $ecv['cost']);
						$optstr[$num][$opt_ind]['tax'] = ($ecplustax - $ecv['cost']);
					}
					$opt_ind++;
				}
			}
			//
		}
		$usedcoupon = false;
		if (strlen($booking['coupon']) > 0) {
			$orig_isdue = $isdue;
			$expcoupon = explode(";", $booking['coupon']);
			$usedcoupon = $expcoupon;
			$isdue = $isdue - (float)$expcoupon[1];
			if ($isdue != $orig_isdue) {
				//lower taxes proportionally
				$tot_taxes = $isdue * $tot_taxes / $orig_isdue;
			}
		}
		if ($booking['refund'] > 0) {
			$orig_isdue = $isdue;
			$isdue -= $booking['refund'];
			if ($isdue != $orig_isdue) {
				//lower taxes proportionally
				$tot_taxes = $isdue * $tot_taxes / $orig_isdue;
			}
		}
		$rows_written = 0;
		$inv_rows = '';
		foreach ($pricestr as $num => $price_descr) {
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td>'.$rooms[$num]['room_name'].'<br/>'.nl2br(rtrim($price_descr['name'], "\n")).'</td>'."\n";
			$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberformat(($price_descr['tot'] - $price_descr['tax'])).'</td>'."\n";
			$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberformat($price_descr['tax']).'</td>'."\n";
			$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberformat($price_descr['tot']).'</td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$rows_written++;
			if (array_key_exists($num, $optstr) && count($optstr[$num]) > 0) {
				foreach ($optstr[$num] as $optk => $optv) {
					$inv_rows .= '<tr>'."\n";
					$inv_rows .= '<td>'.$optv['name'].'</td>'."\n";
					$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberformat(($optv['tot'] - $optv['tax'])).'</td>'."\n";
					$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberformat($optv['tax']).'</td>'."\n";
					$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberformat($optv['tot']).'</td>'."\n";
					$inv_rows .= '</tr>'."\n";
					$rows_written++;
				}
			}
		}
		//if discount print row
		if ($usedcoupon !== false) {
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td></td><td></td><td></td><td></td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td>'.$usedcoupon[2].'</td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td>- '.$booking['currencyname'].' '.self::numberformat($usedcoupon[1]).'</td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$rows_written += 2;
		}
		// if refunded amount, print row
		if ($booking['refund'] > 0) {
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td></td><td></td><td></td><td></td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td>' . JText::translate('VBO_AMOUNT_REFUNDED') . '</td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td>- '.$booking['currencyname'].' '.self::numberformat($booking['refund']).'</td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$rows_written += 2;
		}
		//
		$min_records = 10;
		if ($rows_written < $min_records) {
			for ($i=1; $i <= ($min_records - $rows_written); $i++) { 
				$inv_rows .= '<tr>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '</tr>'."\n";
			}
		}
		//invoice price description - End
		$parsed = str_replace("{invoice_products_descriptions}", $inv_rows, $parsed);
		$parsed = str_replace("{invoice_totalnet}", $booking['currencyname'].' '.self::numberformat(($isdue - $tot_taxes)), $parsed);
		$parsed = str_replace("{invoice_totaltax}", $booking['currencyname'].' '.self::numberformat($tot_taxes), $parsed);
		$parsed = str_replace("{invoice_grandtotal}", $booking['currencyname'].' '.self::numberformat($isdue), $parsed);
		$parsed = str_replace("{inv_notes}", $booking['inv_notes'], $parsed);

		return $parsed;
	}

	/**
	 * Specific method for parsing the template file for the custom (manual) invoices.
	 * 
	 * @param 	string 	$invoicetpl the plain custom invoice template file before parsing
	 * @param 	array 	$invoice 	the invoice record
	 * @param 	array 	$customer	the customer record
	 *
	 * @return 	string 	the HTML content of the parsed custom invoice template
	 * 
	 * @since 	1.11.1
	 */
	public static function parseCustomInvoiceTemplate($invoicetpl, $invoice, $customer) {
		$nowdf = self::getDateFormat(true);
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator(true);
		$companylogo = self::getSiteLogo();
		$uselogo = '';
		if (!empty($companylogo)) {
			/**
			 * Let's try to prevent TCPDF errors by checking if the file exists.
			 * 
			 * @since 		August 2nd 2019
			 */
			if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				$uselogo = '<img src="' . VBO_ADMIN_URI_REL . 'resources/' . $companylogo . '"/>';
			} elseif (is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				$uselogo = '<img src="' . VBO_SITE_URI_REL . 'resources/' . $companylogo . '"/>';
			}
		}
		$invoicetpl = str_replace("{company_logo}", $uselogo, $invoicetpl);
		$invoicetpl = str_replace("{company_info}", self::getInvoiceCompanyInfo(), $invoicetpl);
		$invoicetpl = str_replace("{invoice_number}", $invoice['number'], $invoicetpl);
		$invoicetpl = str_replace("{invoice_date}", date(str_replace("/", $datesep, $df), $invoice['for_date']), $invoicetpl);
		// customer information
		$custinfo = '';
		$custinfo .= $customer['first_name'] . ' ' . $customer['last_name'] . "\n";
		$custinfo .= $customer['email'] . "\n";
		$custinfo .= !empty($customer['company']) ? $customer['company'] . "\n" : '';
		$custinfo .= !empty($customer['vat']) ? $customer['vat'] . "\n" : '';
		$custinfo .= !empty($customer['address']) ? $customer['address'] . "\n" : '';
		$custinfo .= (!empty($customer['zip']) ? $customer['zip'] . " " : '') . (!empty($customer['city']) ? $customer['city'] . "\n" : '');
		$custinfo .= (!empty($customer['country_name']) ? $customer['country_name'] . "\n" : (!empty($customer['country']) ? $customer['country'] . "\n" : ''));
		$custinfo .= !empty($customer['fisccode']) ? $customer['fisccode'] . "\n" : '';
		$invoicetpl = str_replace("{customer_info}", nl2br($custinfo), $invoicetpl);
		// invoice notes
		$invoicetpl = str_replace("{invoice_notes}", (isset($invoice['rawcont']) && isset($invoice['rawcont']['notes']) ? $invoice['rawcont']['notes'] : ''), $invoicetpl);

		return $invoicetpl;
	}

	public static function generateBookingInvoice($booking, $invoice_num = 0, $invoice_suff = '', $invoice_date = '', $company_info = '', $translate = false, $is_front = false) {
		$invoice_num = empty($invoice_num) ? self::getNextInvoiceNumber() : $invoice_num;
		$invoice_suff = empty($invoice_suff) ? self::getInvoiceNumberSuffix() : $invoice_suff;
		$company_info = empty($company_info) ? self::getInvoiceCompanyInfo() : $company_info;
		if (!(count($booking) > 0)) {
			return false;
		}
		if (!($booking['total'] > 0)) {
			return false;
		}
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();
		$currencyname = self::getCurrencyName();
		$booking['currencyname'] = $currencyname;
		$nowdf = self::getDateFormat(true);
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator(true);
		if (empty($invoice_date)) {
			$invoice_date = date(str_replace("/", $datesep, $df), $booking['ts']);
			$used_date = $booking['ts'];
		} else {
			/**
			 * We could be re-generating an invoice for a booking that already had a invoice.
			 * In order to modify some entries in the invoice, the whole PDF is re-generated.
			 * It is now possible to keep the same invoice date as the previous one, so check
			 * what value contains $invoice_date to see if it's different from today's date.
			 * The cron jobs may be calling this method with a $invoice_date = 1, so we need
			 * to also check the length of the string $invoice_date before using that date.
			 * 
			 * @since 	1.10 - August 2018
			 */
			$base_ts = time();
			if (date($df, $base_ts) != $invoice_date && strlen($invoice_date) >= 6) {
				$base_ts = self::getDateTimestamp($invoice_date, 0, 0);
			}
			$invoice_date = date(str_replace("/", $datesep, $df), $base_ts);
			$used_date = $base_ts;
			//
		}
		$booking_rooms = array();
		$q = "SELECT `or`.*,`r`.`name` AS `room_name`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or` LEFT JOIN `#__vikbooking_rooms` `r` ON `r`.`id`=`or`.`idroom` WHERE `or`.`idorder`=".(int)$booking['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$booking_rooms = $dbo->loadAssocList();
		}
		if (!(count($booking_rooms) > 0)) {
			return false;
		}
		//Translations for the invoices are disabled by default as well as the language definitions for the customer language
		if ($translate === true) {
			if (!empty($booking['lang'])) {
				$lang = JFactory::getLanguage();
				if ($lang->getTag() != $booking['lang']) {
					/**
					 * @wponly 	constant for path is different
					 */
					$lang->load('com_vikbooking', VIKBOOKING_LANG, $booking['lang'], true);
				}
				if ($vbo_tn->getDefaultLang() != $booking['lang']) {
					// force the translation to start because contents should be translated
					$vbo_tn::$force_tolang = $booking['lang'];
				}
				$vbo_tn->translateContents($booking_rooms, '#__vikbooking_rooms', array('id' => 'idroom', 'room_name' => 'name'), array(), $booking['lang']);
			}
		}
		//
		if (!class_exists('TCPDF')) {
			require_once(VBO_SITE_PATH . DS . "helpers" . DS . "tcpdf" . DS . 'tcpdf.php');
		}
		$usepdffont = file_exists(VBO_SITE_PATH . DS . "helpers" . DS . "tcpdf" . DS . "fonts" . DS . "dejavusans.php") ? 'dejavusans' : 'helvetica';
		//vikbooking 1.8 - set array variable to the template file
		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`='".(int)$booking['id']."';";
		$dbo->setQuery($q);
		$dbo->execute();
		$booking_info = $dbo->loadAssoc();
		list($invoicetpl, $pdfparams) = self::loadInvoiceTmpl($booking_info, $booking_rooms);
		//
		$invoice_body = self::parseInvoiceTemplate($invoicetpl, $booking, $booking_rooms, $invoice_num, $invoice_suff, $invoice_date, $company_info, ($translate === true ? $vbo_tn : null), $is_front);
		$pdffname = $booking['id'] . '_' . $booking['sid'] . '.pdf';
		$pathpdf = VBO_SITE_PATH . DS . "helpers" . DS . "invoices" . DS . "generated" . DS . $pdffname;
		if (file_exists($pathpdf)) @unlink($pathpdf);
		$pdf_page_format = is_array($pdfparams['pdf_page_format']) ? $pdfparams['pdf_page_format'] : constant($pdfparams['pdf_page_format']);
		$pdf = new TCPDF(constant($pdfparams['pdf_page_orientation']), constant($pdfparams['pdf_unit']), $pdf_page_format, true, 'UTF-8', false);
		$pdf->SetTitle(JText::translate('VBOINVNUM').' '.$invoice_num);
		//Header for each page of the pdf
		if ($pdfparams['show_header'] == 1 && count($pdfparams['header_data']) > 0) {
			$pdf->SetHeaderData($pdfparams['header_data'][0], $pdfparams['header_data'][1], $pdfparams['header_data'][2], $pdfparams['header_data'][3], $pdfparams['header_data'][4], $pdfparams['header_data'][5]);
		}
		//Change some currencies to their unicode (decimal) value
		$unichr_map = array('EUR' => 8364, 'USD' => 36, 'AUD' => 36, 'CAD' => 36, 'GBP' => 163);
		if (array_key_exists($booking['currencyname'], $unichr_map)) {
			$invoice_body = str_replace($booking['currencyname'], $pdf->unichr($unichr_map[$booking['currencyname']]), $invoice_body);
		}
		//header and footer fonts
		$pdf->setHeaderFont(array($usepdffont, '', $pdfparams['header_font_size']));
		$pdf->setFooterFont(array($usepdffont, '', $pdfparams['footer_font_size']));
		//margins
		$pdf->SetMargins(constant($pdfparams['pdf_margin_left']), constant($pdfparams['pdf_margin_top']), constant($pdfparams['pdf_margin_right']));
		$pdf->SetHeaderMargin(constant($pdfparams['pdf_margin_header']));
		$pdf->SetFooterMargin(constant($pdfparams['pdf_margin_footer']));
		//
		$pdf->SetAutoPageBreak(true, constant($pdfparams['pdf_margin_bottom']));
		$pdf->setImageScale(constant($pdfparams['pdf_image_scale_ratio']));
		$pdf->SetFont($usepdffont, '', (int)$pdfparams['body_font_size']);
		if ($pdfparams['show_header'] == 0 || !(count($pdfparams['header_data']) > 0)) {
			$pdf->SetPrintHeader(false);
		}
		if ($pdfparams['show_footer'] == 0) {
			$pdf->SetPrintFooter(false);
		}
		$pdf->AddPage();
		$pdf->writeHTML($invoice_body, true, false, true, false, '');
		$pdf->lastPage();
		$pdf->Output($pathpdf, 'F');
		if (!file_exists($pathpdf)) {
			return false;
		} else {
			/**
			 * @wponly - trigger files mirroring
			 */
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerUploadBackup($pathpdf);
			//
		}
		//insert or update record for this invoice
		$invoice_id = 0;
		$q = "SELECT `id` FROM `#__vikbooking_invoices` WHERE `idorder`=".(int)$booking['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$invoice_data = $dbo->loadAssocList();
			$invoice_id = $invoice_data[0]['id'];
		}
		//Booking History
		self::getBookingHistoryInstance()->setBid($booking['id'])->store('BI', '#'.$invoice_num.$invoice_suff);
		//
		if ($invoice_id > 0) {
			//update
			$q = "UPDATE `#__vikbooking_invoices` SET `number`=".$dbo->quote($invoice_num.$invoice_suff).", `file_name`=".$dbo->quote($pdffname).", `created_on`=".time().", `for_date`=".(int)$used_date." WHERE `id`=".(int)$invoice_id.";";
			$dbo->setQuery($q);
			$dbo->execute();
			$retval = $invoice_id;
		} else {
			//insert
			$q = "INSERT INTO `#__vikbooking_invoices` (`number`,`file_name`,`idorder`,`idcustomer`,`created_on`,`for_date`) VALUES(".$dbo->quote($invoice_num.$invoice_suff).", ".$dbo->quote($pdffname).", ".(int)$booking['id'].", ".(int)$booking['idcustomer'].", ".time().", ".(int)$used_date.");";
			$dbo->setQuery($q);
			$dbo->execute();
			$lid = $dbo->insertid();
			$retval = $lid > 0 ? $lid : false;
		}

		/**
		 * The generation of the analogic invoices can trigger the drivers for the
		 * generation of the e-Invoices if they are set to automatically run.
		 * However, the e-Invoicing classes may be calling this method, so we need
		 * to make sure the eInvoicing class is not running before proceeding.
		 * This method could be called within a loop, and so the second iterations
		 * may already have loaded the eInvocing class. For this we use a static variable.
		 * This piece of code should run after updating the information of the PDF invoice.
		 *
		 * @since 	1.11
		 */
		static $einvocing_can_run = false;
		if (!defined('VBO_EINVOICING_RUN') && !$einvocing_can_run) {
			// allow second iterations calling this method to run
			$einvocing_can_run = true;
		}
		if ($einvocing_can_run === true) {
			$q = "SELECT * FROM `#__vikbooking_einvoicing_config` WHERE `automatic`=1;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$drivers = $dbo->loadAssocList();
				// require the parent abstract class
				$driver_base = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'einvoicing' . DIRECTORY_SEPARATOR;
				require_once $driver_base . 'einvoicing.php';
				// invoke all drivers that should run automatically
				$driver_base .= 'drivers' . DIRECTORY_SEPARATOR;
				foreach ($drivers as $driver) {
					$driver_file = $driver['driver'].'.php';
					$driver_path = $driver_base . $driver_file;
					if (!is_file($driver_path)) {
						continue;
					}
					// require the driver sub-class
					require_once $driver_path;
					// invoke the class
					$classname = 'VikBookingEInvoicing'.str_replace(' ', '', ucwords(str_replace('.php', '', str_replace('_', ' ', $driver_file))));
					if (!class_exists($classname)) {
						continue;
					}
					$driver_obj = new $classname;
					// set the flag of the external call
					$driver_obj->externalCall = __METHOD__;
					// inject invoice number to avoid discrepanices between analogic and electronic, maybe due to missing information for the e-Invoices
					$driver_obj->externalData['einvnum'] = $invoice_num;
					// generate the e-Invoice
					$driver_err = '';
					if (!$driver_obj->generateEInvoice((int)$booking['id'])) {
						$driver_err = $driver_obj->getError();
						VikError::raiseWarning('', $driver_err);
					}
					// Booking History - store log for the e-Invoice result
					self::getBookingHistoryInstance()->setBid($booking['id'])->store('BI', $driver_obj->getName().(!empty($driver_err) ? ': '.$driver_err : ''));
				}
			}
		}
		//

		// return the result of the generation of the PDF invoice
		return $retval;
	}

	/**
	 * Generates an analogic invoice in PDF format for a custom list of services.
	 * No bookings are assigned to this custom invoice. The method parses the same
	 * invoice template as for the regular process with real booking IDs.
	 * The invoice number must be stored before calling this method.
	 * 
	 * @param 	int 	$invoice_id 	the ID of the custom invoice record
	 * 
	 * @return 	boolean
	 * 
	 * @since 	1.11.1
	 */
	public static function generateCustomInvoice($invoice_id) {
		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$q = "SELECT * FROM `#__vikbooking_invoices` WHERE `id`=".(int)$invoice_id." AND `idorder` < 0;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return false;
		}
		$invoice = $dbo->loadAssoc();
		$rawcont = !empty($invoice['rawcont']) ? json_decode($invoice['rawcont'], true) : array();
		$rawcont = is_array($rawcont) ? $rawcont : array();
		$rows = isset($rawcont['rows']) ? $rawcont['rows'] : array();
		$invoice['rawcont'] = $rawcont;
		$customer = self::getCPinIstance()->getCustomerByID($invoice['idcustomer']);
		if (!count($rawcont) || !count($rows) || !count($customer)) {
			// at least one invoice raw is mandatory as well as the customer
			return false;
		}
		$company_info = self::getInvoiceCompanyInfo();
		// load invoice template file
		list($invoicetpl, $pdfparams) = self::loadCustomInvoiceTmpl($invoice, $customer);
		// parse invoice template file
		$invoice_body = self::parseCustomInvoiceTemplate($invoicetpl, $invoice, $customer);
		//
		if (!class_exists('TCPDF')) {
			require_once(VBO_SITE_PATH . DS . "helpers" . DS . "tcpdf" . DS . 'tcpdf.php');
		}
		$usepdffont = file_exists(VBO_SITE_PATH . DS . "helpers" . DS . "tcpdf" . DS . "fonts" . DS . "dejavusans.php") ? 'dejavusans' : 'helvetica';
		$pdffname = $invoice['file_name'];
		$pathpdf = VBO_SITE_PATH . DS . "helpers" . DS . "invoices" . DS . "generated" . DS . $pdffname;
		if (file_exists($pathpdf)) @unlink($pathpdf);
		$pdf_page_format = is_array($pdfparams['pdf_page_format']) ? $pdfparams['pdf_page_format'] : constant($pdfparams['pdf_page_format']);
		$pdf = new TCPDF(constant($pdfparams['pdf_page_orientation']), constant($pdfparams['pdf_unit']), $pdf_page_format, true, 'UTF-8', false);
		$pdf->SetTitle(JText::translate('VBOINVNUM').' '.$invoice['number']);
		//Header for each page of the pdf
		if ($pdfparams['show_header'] == 1 && count($pdfparams['header_data']) > 0) {
			$pdf->SetHeaderData($pdfparams['header_data'][0], $pdfparams['header_data'][1], $pdfparams['header_data'][2], $pdfparams['header_data'][3], $pdfparams['header_data'][4], $pdfparams['header_data'][5]);
		}
		//Change some currencies to their unicode (decimal) value
		$currencyname = self::getCurrencyName();
		$unichr_map = array('EUR' => 8364, 'USD' => 36, 'AUD' => 36, 'CAD' => 36, 'GBP' => 163);
		if (array_key_exists($currencyname, $unichr_map)) {
			$invoice_body = str_replace($currencyname, $pdf->unichr($unichr_map[$currencyname]), $invoice_body);
		}
		//header and footer fonts
		$pdf->setHeaderFont(array($usepdffont, '', $pdfparams['header_font_size']));
		$pdf->setFooterFont(array($usepdffont, '', $pdfparams['footer_font_size']));
		//margins
		$pdf->SetMargins(constant($pdfparams['pdf_margin_left']), constant($pdfparams['pdf_margin_top']), constant($pdfparams['pdf_margin_right']));
		$pdf->SetHeaderMargin(constant($pdfparams['pdf_margin_header']));
		$pdf->SetFooterMargin(constant($pdfparams['pdf_margin_footer']));
		//
		$pdf->SetAutoPageBreak(true, constant($pdfparams['pdf_margin_bottom']));
		$pdf->setImageScale(constant($pdfparams['pdf_image_scale_ratio']));
		$pdf->SetFont($usepdffont, '', (int)$pdfparams['body_font_size']);
		if ($pdfparams['show_header'] == 0 || !(count($pdfparams['header_data']) > 0)) {
			$pdf->SetPrintHeader(false);
		}
		if ($pdfparams['show_footer'] == 0) {
			$pdf->SetPrintFooter(false);
		}
		$pdf->AddPage();
		$pdf->writeHTML($invoice_body, true, false, true, false, '');
		$pdf->lastPage();
		$pdf->Output($pathpdf, 'F');
		if (is_file($pathpdf)) {
			/**
			 * @wponly - trigger files mirroring
			 */
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerUploadBackup($pathpdf);
			//
		}

		/**
		 * The generation of the analogic invoices can trigger the drivers for the
		 * generation of the e-Invoices if they are set to automatically run.
		 * However, the e-Invoicing classes may be calling this method, so we need
		 * to make sure the eInvoicing class is not running before proceeding.
		 * This method could be called within a loop, and so the second iterations
		 * may already have loaded the eInvocing class. For this we use a static variable.
		 * This piece of code should run after updating the information of the PDF invoice.
		 *
		 * @since 	1.11.1
		 */
		$q = "SELECT * FROM `#__vikbooking_einvoicing_config` WHERE `automatic`=1;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$drivers = $dbo->loadAssocList();
			// make sure the invoice number is just a number
			$invoice['number'] = str_replace(self::getInvoiceNumberSuffix(), '', $invoice['number']);
			// require the parent abstract class
			$driver_base = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'einvoicing' . DIRECTORY_SEPARATOR;
			require_once $driver_base . 'einvoicing.php';
			// invoke all drivers that should run automatically
			$driver_base .= 'drivers' . DIRECTORY_SEPARATOR;
			foreach ($drivers as $driver) {
				$driver_file = $driver['driver'].'.php';
				$driver_path = $driver_base . $driver_file;
				if (!is_file($driver_path)) {
					continue;
				}
				// require the driver sub-class
				require_once $driver_path;
				// invoke the class
				$classname = 'VikBookingEInvoicing'.str_replace(' ', '', ucwords(str_replace('.php', '', str_replace('_', ' ', $driver_file))));
				if (!class_exists($classname)) {
					continue;
				}
				$driver_obj = new $classname;
				// set the flag of the external call
				$driver_obj->externalCall = __METHOD__;
				// inject custom invoice number, date and details to avoid discrepanices between analogic and electronic
				$driver_obj->externalData['einvnum'] = $invoice['number'];
				$driver_obj->externalData['einvdate'] = (int)$invoice['for_date'];
				$driver_obj->externalData['einvcustom'] = $invoice;
				// prepare data array for the generation of the e-Invoice
				$einvdata = $driver_obj->prepareCustomInvoiceData($invoice, $customer);
				// before generating the e-invoice, make sure to obliterate it if exists already (case of update custom invoice)
				$preveinv = $driver_obj->eInvoiceExists(array('idorder' => $einvdata[0]['id']));
				if ($preveinv !== false) {
					$driver_obj->obliterateEInvoice(array('id' => $preveinv));
				}
				// generate the e-Invoice
				$driver_err = '';
				if (!$driver_obj->generateEInvoice($einvdata)) {
					$driver_err = $driver_obj->getError();
					VikError::raiseWarning('', $driver_err);
				}
			}
		}
		//
		
		// return the result of the generation of the PDF invoice
		return is_file($pathpdf);
	}

	public static function sendBookingInvoice($invoice_id, $booking, $text = '', $subject = '') {
		if (!(count($booking) > 0) || empty($invoice_id) || empty($booking['custmail'])) {
			return false;
		}
		$dbo = JFactory::getDbo();
		$invoice_data = array();
		$q = "SELECT * FROM `#__vikbooking_invoices` WHERE `id`=".(int)$invoice_id.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$invoice_data = $dbo->loadAssoc();
		}
		if (!(count($invoice_data) > 0)) {
			return false;
		}
		$mail_text = empty($text) ? JText::translate('VBOEMAILINVOICEATTACHTXT') : $text;
		$mail_subject = empty($subject) ? JText::translate('VBOEMAILINVOICEATTACHSUBJ') : $subject;
		$invoice_file_path = VBO_SITE_PATH . DS . "helpers" . DS . "invoices" . DS . "generated" . DS . $invoice_data['file_name'];
		if (!file_exists($invoice_file_path)) {
			return false;
		}
		if (!class_exists('VboApplication')) {
			require_once(VBO_ADMIN_PATH.DS.'helpers'.DS.'jv_helper.php');
		}
		$vbo_app = new VboApplication;
		$admin_sendermail = self::getSenderMail();
		$vbo_app->sendMail($admin_sendermail, $admin_sendermail, $booking['custmail'], $admin_sendermail, $mail_subject, $mail_text, (strpos($mail_text, '<') !== false && strpos($mail_text, '/>') !== false ? true : false), 'base64', $invoice_file_path);
		//update record
		$q = "UPDATE `#__vikbooking_invoices` SET `emailed`=1, `emailed_to`=".$dbo->quote($booking['custmail'])." WHERE `id`=".(int)$invoice_id.";";
		$dbo->setQuery($q);
		$dbo->execute();
		//
		return true;
	}

	public static function loadCheckinDocTmpl($booking_info = array(), $booking_rooms = array(), $customer = array()) {
		if (!defined('_VIKBOOKINGEXEC')) {
			define('_VIKBOOKINGEXEC', '1');
		}
		ob_start();
		include VBO_SITE_PATH . DS . "helpers" . DS . "checkins" . DS . "checkin_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		$default_params = array(
			'show_header' => 0,
			'header_data' => array(),
			'show_footer' => 0,
			'pdf_page_orientation' => 'PDF_PAGE_ORIENTATION',
			'pdf_unit' => 'PDF_UNIT',
			'pdf_page_format' => 'PDF_PAGE_FORMAT',
			'pdf_margin_left' => 'PDF_MARGIN_LEFT',
			'pdf_margin_top' => 'PDF_MARGIN_TOP',
			'pdf_margin_right' => 'PDF_MARGIN_RIGHT',
			'pdf_margin_header' => 'PDF_MARGIN_HEADER',
			'pdf_margin_footer' => 'PDF_MARGIN_FOOTER',
			'pdf_margin_bottom' => 'PDF_MARGIN_BOTTOM',
			'pdf_image_scale_ratio' => 'PDF_IMAGE_SCALE_RATIO',
			'header_font_size' => '10',
			'body_font_size' => '10',
			'footer_font_size' => '8'
		);
		if (defined('_VIKBOOKING_CHECKIN_PARAMS') && isset($checkin_params) && @count($checkin_params) > 0) {
			$default_params = array_merge($default_params, $checkin_params);
		}
		return array($content, $default_params);
	}

	public static function parseCheckinDocTemplate($checkintpl, $booking, $booking_rooms, $customer) {
		$parsed = $checkintpl;
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$nowdf = self::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df='d/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df='m/d/Y';
		} else {
			$df='Y/m/d';
		}
		$datesep = self::getDateSeparator();
		$companylogo = self::getSiteLogo();
		$uselogo = '';
		if (!empty($companylogo)) {
			/**
			 * Let's try to prevent TCPDF errors, as custom logos are always uploaded in /admin
			 * 
			 * @since 		March 16th 2021
			 */
			if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				// $uselogo = '<img src="' . VBO_ADMIN_URI_REL . 'resources/' . $companylogo . '"/>';
				$uselogo = '<img src="' . VBO_ADMIN_URI . 'resources/' . $companylogo . '"/>';
			} elseif (is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				// $uselogo = '<img src="' . VBO_SITE_URI_REL . 'resources/' . $companylogo . '"/>';
				$uselogo = '<img src="' . VBO_SITE_URI . 'resources/' . $companylogo . '"/>';
			}
		}
		$company_name = self::getFrontTitle();
		$company_info = self::getInvoiceCompanyInfo();
		$parsed = str_replace("{company_name}", $company_name, $parsed);
		$parsed = str_replace("{company_logo}", $uselogo, $parsed);
		$parsed = str_replace("{company_info}", $company_info, $parsed);
		$parsed = str_replace("{customer_info}", nl2br(rtrim($booking['custdata'], "\n")), $parsed);
		$parsed = str_replace("{checkin_date}", date(str_replace("/", $datesep, $df), $booking['checkin']), $parsed);
		$parsed = str_replace("{checkout_date}", date(str_replace("/", $datesep, $df), $booking['checkout']), $parsed);
		$parsed = str_replace("{num_nights}", $booking['days'], $parsed);
		$tot_guests = 0;
		$tot_adults = 0;
		$tot_children = 0;
		foreach($booking_rooms as $kor => $or) {
			$tot_guests += ($or['adults'] + $or['children']);
			$tot_adults += $or['adults'];
			$tot_children += $or['children'];
		}
		$parsed = str_replace("{tot_guests}", $tot_guests, $parsed);
		$parsed = str_replace("{tot_adults}", $tot_adults, $parsed);
		$parsed = str_replace("{tot_children}", $tot_children, $parsed);
		if (count($customer) && isset($customer['comments'])) {
			$parsed = str_replace("{checkin_comments}", $customer['comments'], $parsed);
		}
		$termsconds = self::getTermsConditions();
		$parsed = str_replace("{terms_and_conditions}", $termsconds, $parsed);
		//custom fields replace
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $cmatches);
		if (is_array($cmatches[1]) && @count($cmatches[1]) > 0) {
			$cfids = array();
			foreach ($cmatches[1] as $cfid ){
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$dbo->execute();
			$cfields = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
			$vbo_tn->translateContents($cfields, '#__vikbooking_custfields');
			$cfmap = array();
			if (is_array($cfields)) {
				foreach ($cfields as $cf) {
					$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
				}
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $booking['custdata']);
			if (count($partsreceived) > 0) {
				foreach ($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach($cmatches[1] as $cfid ){
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}
		//end custom fields replace

		return $parsed;
	}

	/**
	 * Returns an array of key-value pairs to be used for
	 * building the Guests Details in the Check-in process.
	 * The keys will be compared to the fields of the table
	 * _customers to see if some values already exist.
	 * The values can use lang defs of both front-end or back-end.
	 * To be called as list(fields, attributes).
	 * 
	 * @param 	boolean 	$precheckin 	true if requested for front-end pre check-in.
	 *
	 * @return 	array 		key-value pairs for showing and collecting details.
	 */
	public static function getPaxFields($precheckin = false) {
		if (!$precheckin) {
			// back-end key-value pairs
			return array(
				array(
					'first_name' => JText::translate('VBCUSTOMERFIRSTNAME'),
					'last_name'  => JText::translate('VBCUSTOMERLASTNAME'),
					'country' 	 => JText::translate('VBCUSTOMERCOUNTRY'),
					'docnum' 	 => JText::translate('VBCUSTOMERDOCNUM'),
					'extranotes' => JText::translate('VBOGUESTEXTRANOTES'),
				),
				array(
					'first_name' => 'size="15"',
					'last_name'  => 'size="15"',
					'country' 	 => '',
					'docnum' 	 => 'size="15"',
					'extranotes' => 'size="35"',
				)
			);
		}
		// front-end key-value pairs for pre check-in
		return array(
			array(
				'first_name'  => JText::translate('VBCCFIRSTNAME'),
				'last_name'   => JText::translate('VBCCLASTNAME'),
				'date_birth'  => JText::translate('ORDER_DBIRTH'),
				'place_birth' => JText::translate('VBOCUSTPLACEBIRTH'),
				'country' 	  => JText::translate('ORDER_STATE'),
				'city' 		  => JText::translate('ORDER_CITY'),
				'zip' 		  => JText::translate('ORDER_ZIP'),
				'nationality' => JText::translate('VBOCUSTNATIONALITY'),
				'gender' 	  => JText::translate('VBOCUSTGENDER'),
				'doctype' 	  => JText::translate('VBOCUSTDOCTYPE'),
				'docnum' 	  => JText::translate('VBOCUSTDOCNUM'),
				'documents'   => JText::translate('VBO_CUSTOMER_UPLOAD_DOCS'),
			),
			array(
				'first_name'  => 'text',
				'last_name'   => 'text',
				'date_birth'  => 'calendar',
				'place_birth' => 'text',
				'country' 	  => 'country',
				'city' 		  => 'text',
				'zip' 		  => 'text',
				'nationality' => 'country',
				'gender' 	  => array(JText::translate('VBOCUSTGENDERM'), JText::translate('VBOCUSTGENDERF')),
				'doctype' 	  => 'text',
				'docnum' 	  => 'text',
				'documents'   => 'file',
			)
		);
	}

	public static function getCountriesArray() {
		$all_countries = array();
		$dbo = JFactory::getDbo();
		$q = "SELECT `country_name`, `country_3_code` FROM `#__vikbooking_countries` ORDER BY `country_name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		$countries = $dbo->loadAssocList();
		foreach ($countries as $v) {
			$all_countries[$v['country_3_code']] = $v;
		}
		return $all_countries;
	}

	public static function getCountriesSelect($name, $all_countries = array(), $current_value = '', $empty_value = ' ') {
		if (!(count($all_countries) > 0)) {
			$all_countries = self::getCountriesArray();
		}
		$countries = '<select name="'.$name.'">'."\n";
		if (strlen($empty_value)) {
			$countries .= '<option value="">'.$empty_value.'</option>'."\n";
		}
		foreach ($all_countries as $v) {
			$countries .= '<option value="'.$v['country_3_code'].'"'.($v['country_3_code'] == $current_value ? ' selected="selected"' : '').'>'.$v['country_name'].'</option>'."\n";
		}
		$countries .= '</select>';

		return $countries;
	}

	public static function getThumbSize($skipsession = false) {
		if (!$skipsession) {
			$session = JFactory::getSession();
			$s = $session->get('vbothumbsize', '');
			if (strlen($s)) {
				return (int)$s;
			}
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='thumbsize';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			if (!$skipsession) {
				$session->set('vbothumbsize', $s);
			}
			return (int)$s;
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('thumbsize', '500');";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$skipsession) {
			$session->set('vbothumbsize', '500');
		}
		return 500;
	}

	/**
	 * Checks whether an iCal file for the reservation should be
	 * attached to the confirmation email for customer and/or admin.
	 * 
	 * @return 	int 	1=admin+customer, 2=admin, 3=customer, 0=no
	 * 
	 * @since 	1.2.0
	 */
	public static function attachIcal() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='attachical';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return (int)$s;
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('attachical', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	/**
	 * How the calculation of the orphan dates should take place.
	 * 
	 * @return 	string 	"next" for only checking the bookings ahead, "prevnext" if
	 * 					also the previous bookings should be checked.
	 * 
	 * @since 	1.3.0
	 */
	public static function orphansCalculation() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='orphanscalculation'";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return $dbo->loadResult();
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('orphanscalculation', 'next');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 'next';
	}

	/**
	 * Returns the name of the template file to use for the front-end View "search".
	 * 
	 * @return 	string 	the name of the template file. New and upgraded user will both use the classic file.
	 * 
	 * @since 	1.3.0
	 */
	public static function searchResultsTmpl() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='searchrestmpl'";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return $dbo->loadResult();
		}
		// only those who have updated will be missing this configuration setting
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('searchrestmpl', 'classic');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 'classic';
	}

	/**
	 * Returns a string without any new-line characters
	 * to be used for JavaScript values without facing
	 * errors like 'unterminated string literal'.
	 * By passing nl2br($str) as argument, we can keep
	 * the wanted new-line HTML tags for PRE tags. 
	 * We use implode() with just one argument to 
	 * not use an empty string as glue for the string.
	 *
	 * @param 	$str 	string
	 *
	 * @return 	string 	
	 */
	public static function strTrimLiteral($str) {
		$str = str_replace(array("\r\n", "\r"), "\n", $str);
		$lines = explode("\n", $str);
		$new_lines = array();
		foreach ($lines as $i => $line) {
		    if (strlen($line)) {
				$new_lines[] = trim($line);
			}
		}
		return implode($new_lines);
	}

	public static function getVboApplication() {
		if (!class_exists('VboApplication')) {
			require_once(VBO_ADMIN_PATH.DS.'helpers'.DS.'jv_helper.php');
		}
		return new VboApplication();
	}
	
	public static function sayWeekDay($wd) {
		switch ($wd) {
			case '6' :
				$ret = JText::translate('VBWEEKDAYSIX');
				break;
			case '5' :
				$ret = JText::translate('VBWEEKDAYFIVE');
				break;
			case '4' :
				$ret = JText::translate('VBWEEKDAYFOUR');
				break;
			case '3' :
				$ret = JText::translate('VBWEEKDAYTHREE');
				break;
			case '2' :
				$ret = JText::translate('VBWEEKDAYTWO');
				break;
			case '1' :
				$ret = JText::translate('VBWEEKDAYONE');
				break;
			default :
				$ret = JText::translate('VBWEEKDAYZERO');
				break;
		}
		return $ret;
	}
	
	public static function sayMonth($idm) {
		switch ($idm) {
			case '12' :
				$ret = JText::translate('VBMONTHTWELVE');
				break;
			case '11' :
				$ret = JText::translate('VBMONTHELEVEN');
				break;
			case '10' :
				$ret = JText::translate('VBMONTHTEN');
				break;
			case '9' :
				$ret = JText::translate('VBMONTHNINE');
				break;
			case '8' :
				$ret = JText::translate('VBMONTHEIGHT');
				break;
			case '7' :
				$ret = JText::translate('VBMONTHSEVEN');
				break;
			case '6' :
				$ret = JText::translate('VBMONTHSIX');
				break;
			case '5' :
				$ret = JText::translate('VBMONTHFIVE');
				break;
			case '4' :
				$ret = JText::translate('VBMONTHFOUR');
				break;
			case '3' :
				$ret = JText::translate('VBMONTHTHREE');
				break;
			case '2' :
				$ret = JText::translate('VBMONTHTWO');
				break;
			default :
				$ret = JText::translate('VBMONTHONE');
				break;
		}
		return $ret;
	}
	
	public static function sayDayMonth($d) {
		switch ($d) {
			case '31' :
				$ret = JText::translate('VBDAYMONTHTHIRTYONE');
				break;
			case '30' :
				$ret = JText::translate('VBDAYMONTHTHIRTY');
				break;
			case '29' :
				$ret = JText::translate('VBDAYMONTHTWENTYNINE');
				break;
			case '28' :
				$ret = JText::translate('VBDAYMONTHTWENTYEIGHT');
				break;
			case '27' :
				$ret = JText::translate('VBDAYMONTHTWENTYSEVEN');
				break;
			case '26' :
				$ret = JText::translate('VBDAYMONTHTWENTYSIX');
				break;
			case '25' :
				$ret = JText::translate('VBDAYMONTHTWENTYFIVE');
				break;
			case '24' :
				$ret = JText::translate('VBDAYMONTHTWENTYFOUR');
				break;
			case '23' :
				$ret = JText::translate('VBDAYMONTHTWENTYTHREE');
				break;
			case '22' :
				$ret = JText::translate('VBDAYMONTHTWENTYTWO');
				break;
			case '21' :
				$ret = JText::translate('VBDAYMONTHTWENTYONE');
				break;
			case '20' :
				$ret = JText::translate('VBDAYMONTHTWENTY');
				break;
			case '19' :
				$ret = JText::translate('VBDAYMONTHNINETEEN');
				break;
			case '18' :
				$ret = JText::translate('VBDAYMONTHEIGHTEEN');
				break;
			case '17' :
				$ret = JText::translate('VBDAYMONTHSEVENTEEN');
				break;
			case '16' :
				$ret = JText::translate('VBDAYMONTHSIXTEEN');
				break;
			case '15' :
				$ret = JText::translate('VBDAYMONTHFIFTEEN');
				break;
			case '14' :
				$ret = JText::translate('VBDAYMONTHFOURTEEN');
				break;
			case '13' :
				$ret = JText::translate('VBDAYMONTHTHIRTEEN');
				break;
			case '12' :
				$ret = JText::translate('VBDAYMONTHTWELVE');
				break;
			case '11' :
				$ret = JText::translate('VBDAYMONTHELEVEN');
				break;
			case '10' :
				$ret = JText::translate('VBDAYMONTHTEN');
				break;
			case '9' :
				$ret = JText::translate('VBDAYMONTHNINE');
				break;
			case '8' :
				$ret = JText::translate('VBDAYMONTHEIGHT');
				break;
			case '7' :
				$ret = JText::translate('VBDAYMONTHSEVEN');
				break;
			case '6' :
				$ret = JText::translate('VBDAYMONTHSIX');
				break;
			case '5' :
				$ret = JText::translate('VBDAYMONTHFIVE');
				break;
			case '4' :
				$ret = JText::translate('VBDAYMONTHFOUR');
				break;
			case '3' :
				$ret = JText::translate('VBDAYMONTHTHREE');
				break;
			case '2' :
				$ret = JText::translate('VBDAYMONTHTWO');
				break;
			default :
				$ret = JText::translate('VBDAYMONTHONE');
				break;
		}
		return $ret;
	}

	public static function totElements($arr) {
		$n = 0;
		if (is_array($arr)) {
			foreach ($arr as $a) {
				if (!empty($a)) {
					$n++;
				}
			}
			return $n;
		}
		return false;
	}

	/**
	 * Returns a list of documents that were uploaded
	 * for the specified customer.
	 *
	 * @param 	integer  $id  The customer ID.
	 *
	 * @return 	array 	 A list of documents.
	 * 
	 * @since 	1.3.0
	 */
	public static function getCustomerDocuments($id)
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select($dbo->qn('docsfolder'))
			->from($dbo->qn('#__vikbooking_customers'))
			->where($dbo->qn('id') . ' = ' . (int) $id);

		$dbo->setQuery($q, 0, 1);
		$dbo->execute();

		if (!$dbo->getNumRows())
		{
			// customer not found
			return array();
		}

		// retrieve customer documents directory name
		$dirname = $dbo->loadResult();

		if (empty($dirname))
		{
			// no available directory
			return array();
		}

		// build documents folder path
		$dirname = VBO_CUSTOMERS_PATH . DIRECTORY_SEPARATOR . $dirname;

		if (!is_dir($dirname))
		{
			// the customer directory doesn't exist
			return array();
		}

		// read all files from customer directory
		$glob = glob($dirname . DIRECTORY_SEPARATOR . '*');

		$files = array();

		foreach ($glob as $path)
		{
			// skip "index.html"
			if (!preg_match("/[\/\\\\]index\.html$/i", $path))
			{
				// extract name and extension from file path
				if (preg_match("/(.*)\.([a-z0-9]{2,})$/i", basename($path), $match))
				{
					$name = $match[1];
					$ext  = $match[2];
				}
				else
				{
					$name = basename($path);
					$ext  = '';
				}

				$file = new stdClass;
				$file->path     = $path;
				$file->name     = $name;
				$file->ext      = $ext;
				$file->basename = $file->name . '.' . $file->ext;
				$file->size     = filesize($path);
				$file->date     = filemtime($path);
				$file->url 		= str_replace(DIRECTORY_SEPARATOR, '/', str_replace(VBO_CUSTOMERS_PATH . DIRECTORY_SEPARATOR, VBO_CUSTOMERS_URI, $file->path));

				$files[] = $file;
			}
		}

		// sort files by creation date
		usort($files, function($a, $b)
		{
			return $b->date - $a->date;
		});

		return $files;
	}
	
	public static function displayPaymentParameters($pfile, $pparams = '') {
		$html = '<p>---------</p>';

		/**
		 * @wponly 	The payment gateway is now loaded 
		 * 			using the apposite dispatcher.
		 *
		 * @since 1.0.5
		 */
		JLoader::import('adapter.payment.dispatcher');

		try
		{
			$payment = JPaymentDispatcher::getInstance('vikbooking', $pfile);
		}
		catch (Exception $e)
		{
			// payment not found
			$html = $e->getMessage();

			if ($code = $e->getCode())
			{
				$html = '<b>' . $code . '</b> : ' . $html;
			}

			return $html;
		}
		//

		$arrparams = !empty($pparams) ? json_decode($pparams, true) : array();
		
		// get admin parameters
		$pconfig = $payment->getAdminParameters();

		if (count($pconfig) > 0) {
			$html = '';
			foreach($pconfig as $value => $cont) {
				if (empty($value)) {
					continue;
				}
				$labelparts = explode('//', $cont['label']);
				$label = $labelparts[0];
				$labelhelp = isset($labelparts[1]) ? $labelparts[1] : '';
				$html .= '<div class="vbo-param-container">';
				if (strlen($label) > 0) {
					$html .= '<div class="vbo-param-label">' . $label . '</div>';
				}
				$html .= '<div class="vbo-param-setting">';
				switch ($cont['type']) {
					case 'custom':
						$html .= $cont['html'];
						break;
					case 'select':
						$html .= '<select name="vikpaymentparams['.$value.']">';
						foreach($cont['options'] as $poption) {
							$html .= '<option value="'.$poption.'"'.(array_key_exists($value, $arrparams) && $poption == $arrparams[$value] ? ' selected="selected"' : '').'>'.$poption.'</option>';
						}
						$html .= '</select>';
						break;
					default:
						$html .= '<input type="text" name="vikpaymentparams['.$value.']" value="'.(array_key_exists($value, $arrparams) ? $arrparams[$value] : '').'" />';
						break;
				}
				if (strlen($labelhelp) > 0) {
					$html .= '<span class="vbo-param-setting-comment">' . $labelhelp . '</span>';
				}
				$html .= '</div>';
				$html .= '</div>';
			}
		}

		return $html;
	}

	public static function displaySMSParameters($pfile, $pparams = '') {
		$html = '---------';
		$arrparams = !empty($pparams) ? json_decode($pparams, true) : array();
		if (file_exists(VBO_ADMIN_PATH.DS.'smsapi'.DS.$pfile) && !empty($pfile)) {
			require_once(VBO_ADMIN_PATH.DS.'smsapi'.DS.$pfile);
			if (method_exists('VikSmsApi', 'getAdminParameters')) {
				$pconfig = VikSmsApi::getAdminParameters();
				if (count($pconfig) > 0) {
					$html = '';
					foreach($pconfig as $value => $cont) {
						if (empty($value)) {
							continue;
						}
						$labelparts = explode('//', $cont['label']);
						$label = $labelparts[0];
						$labelhelp = isset($labelparts[1]) ? $labelparts[1] : '';
						$html .= '<div class="vikpaymentparam">';
						if (strlen($label) > 0) {
							$html .= '<span class="vikpaymentparamlabel">'.$label.'</span>';
						}
						switch ($cont['type']) {
							case 'custom':
								$html .= $cont['html'];
								break;
							case 'select':
								$html .= '<span class="vikpaymentparaminput">' .
										'<select name="viksmsparams['.$value.']">';
								foreach($cont['options'] as $poption) {
									$html .= '<option value="'.$poption.'"'.(array_key_exists($value, $arrparams) && $poption == $arrparams[$value] ? ' selected="selected"' : '').'>'.$poption.'</option>';
								}
								$html .= '</select></span>';
								break;
							default:
								$html .= '<span class="vikpaymentparaminput">' .
										'<input type="text" name="viksmsparams['.$value.']" value="'.(array_key_exists($value, $arrparams) ? $arrparams[$value] : '').'" size="40"/>' .
										'</span>';
								break;
						}
						if (strlen($labelhelp) > 0) {
							$html .= '<span class="vikpaymentparamlabelhelp">'.$labelhelp.'</span>';
						}
						$html .= '</div>';
					}
				}
			}
		}
		return $html;
	}

	public static function displayCronParameters($pfile, $pparams = '') {
		$html = '<p>---------</p>';
		$arrparams = !empty($pparams) ? json_decode($pparams, true) : array();
		if (file_exists(VBO_ADMIN_PATH.DS.'cronjobs'.DS.$pfile) && !empty($pfile)) {
			require_once(VBO_ADMIN_PATH.DS.'cronjobs'.DS.$pfile);
			if (method_exists('VikCronJob', 'getAdminParameters')) {
				$pconfig = VikCronJob::getAdminParameters();
				if (count($pconfig) > 0) {
					$html = '';
					foreach($pconfig as $value => $cont) {
						if (empty($value)) {
							continue;
						}
						$inp_attr = '';
						if (array_key_exists('attributes', $cont)) {
							foreach ($cont['attributes'] as $inpk => $inpv) {
								$inp_attr .= $inpk.'="'.$inpv.'" ';
							}
						}
						$labelparts = explode('//', $cont['label']);
						$label = $labelparts[0];
						$labelhelp = isset($labelparts[1]) ? $labelparts[1] : '';
						$html .= '<div class="vbo-param-container">';
						if (strlen($label) > 0) {
							$html .= '<div class="vbo-param-label">' . $label . '</div>';
						}
						$html .= '<div class="vbo-param-setting">';
						switch ($cont['type']) {
							case 'custom':
								$html .= $cont['html'];
								break;
							case 'select':
								$html .= '<select name="vikcronparams['.$value.']"'.(array_key_exists('attributes', $cont) ? ' '.$inp_attr : '').'>';
								foreach($cont['options'] as $kopt => $poption) {
									$html .= '<option value="'.$poption.'"'.(array_key_exists($value, $arrparams) && $poption == $arrparams[$value] ? ' selected="selected"' : '').'>'.(is_numeric($kopt) ? $poption : $kopt).'</option>';
								}
								$html .= '</select>';
								break;
							case 'number':
								$html .= '<input type="number" name="vikcronparams['.$value.']" value="'.(array_key_exists($value, $arrparams) ? $arrparams[$value] : (array_key_exists('default', $cont) ? $cont['default'] : '')).'" '.(array_key_exists('attributes', $cont) ? $inp_attr : '').'/>';
								break;
							case 'textarea':
								$html .= '<textarea name="vikcronparams['.$value.']" '.(array_key_exists('attributes', $cont) ? $inp_attr : 'rows="4" cols="60"').'>'.(array_key_exists($value, $arrparams) ? htmlentities($arrparams[$value]) : (array_key_exists('default', $cont) ? htmlentities($cont['default']) : '')).'</textarea>';
								break;
							default:
								$html .= '<input type="text" name="vikcronparams['.$value.']" value="'.(array_key_exists($value, $arrparams) ? $arrparams[$value] : (array_key_exists('default', $cont) ? $cont['default'] : '')).'" '.(array_key_exists('attributes', $cont) ? $inp_attr : 'size="40"').'/>';
								break;
						}
						if (strlen($labelhelp) > 0) {
							$html .= '<span class="vbo-param-setting-comment">' . $labelhelp . '</span>';
						}
						$html .= '</div>';
						$html .= '</div>';
					}
				}
			}
		}
		return $html;
	}
	
	public static function invokeChannelManager($skiporder = true, $order = array()) {
		$task = VikRequest::getString('task', '', 'request');
		$view = VikRequest::getString('view', '', 'request');
		$tmpl = VikRequest::getString('tmpl', '', 'request');
		$noimpression = array('vieworder', 'booking');
		if ($tmpl != 'component' && (!$skiporder || (!in_array($task, $noimpression) && !in_array($view, $noimpression))) && file_exists(VCM_SITE_PATH.DS.'helpers'.DS.'lib.vikchannelmanager.php')) {
			//VCM Channel Impression
			if (!class_exists('VikChannelManagerConfig')) {
				require_once(VCM_SITE_PATH.DS.'helpers'.DS.'vcm_config.php');
			}
			if (!class_exists('VikChannelManager')) {
				require_once(VCM_SITE_PATH.DS.'helpers'.DS.'lib.vikchannelmanager.php');
			}
			VikChannelManager::invokeChannelImpression();
		} elseif ($tmpl != 'component' && count($order) > 0 && file_exists(VCM_SITE_PATH.DS.'helpers'.DS.'lib.vikchannelmanager.php')) {
			//VCM Channel Conversion-Impression
			if (!class_exists('VikChannelManagerConfig')) {
				require_once(VCM_SITE_PATH.DS.'helpers'.DS.'vcm_config.php');
			}
			if (!class_exists('VikChannelManager')) {
				require_once(VCM_SITE_PATH.DS.'helpers'.DS.'lib.vikchannelmanager.php');
			}
			VikChannelManager::invokeChannelConversionImpression($order);
		}
	}

	public static function validEmail($email) {
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		} else {
			$domain = substr($email, $atIndex +1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else
				if ($domainLen < 1 || $domainLen > 255) {
					// domain part length exceeded
					$isValid = false;
				} else
					if ($local[0] == '.' || $local[$localLen -1] == '.') {
						// local part starts or ends with '.'
						$isValid = false;
					} else
						if (preg_match('/\\.\\./', $local)) {
							// local part has two consecutive dots
							$isValid = false;
						} else
							if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
								// character not valid in domain part
								$isValid = false;
							} else
								if (preg_match('/\\.\\./', $domain)) {
									// domain part has two consecutive dots
									$isValid = false;
								} else
									if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
										// character not valid in local part unless 
										// local part is quoted
										if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
											$isValid = false;
										}
									}
			if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
				// domain not found in DNS
				$isValid = false;
			}
		}
		return $isValid;
	}

	public static function caniWrite($path) {
		if ($path[strlen($path) - 1] == '/') {
			// ricorsivo return a temporary file path
			return self::caniWrite($path . uniqid(mt_rand()) . '.tmp');
		}
		if (is_dir($path)) {
			return self::caniWrite($path . '/' . uniqid(mt_rand()) . '.tmp');
		}
		// check tmp file for read/write capabilities
		$rm = file_exists($path);
		$f = @fopen($path, 'a');
		if ($f === false) {
			return false;
		}
		fclose($f);
		if (!$rm) {
			unlink($path);
		}
		return true;
	}

	/**
	 * Alias method of JFile::upload to unify any
	 * upload function into one.
	 * 
	 * @param   string   $src 			The name of the php (temporary) uploaded file.
	 * @param   string   $dest 			The path (including filename) to move the uploaded file to.
	 * @param   boolean  [$copy_only] 	Whether to skip the file upload and just copy the file.
	 * 
	 * @return  boolean  True on success.
	 * 
	 * @since 	1.10 - Revision April 24th 2018 for compatibility with the VikWP Framework.
	 * 			@wponly 1.0.7 added the third $copy_only argument to remove the use of copy()
	 */
	public static function uploadFile($src, $dest, $copy_only = false) {
		// always attempt to include the File class
		jimport('joomla.filesystem.file');

		// upload the file
		if (!$copy_only) {
			$result = JFile::upload($src, $dest);
		} else {
			// this is to avoid the use of the PHP function copy() and allow files mirroring in WP (triggerUploadBackup)
			$result = JFile::copy($src, $dest);
		}

		/**
		 * @wponly  in order to not lose uploaded files after installing an update,
		 * 			we need to move any uploaded file onto a recovery folder.
		 */
		if ($result) {
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerUploadBackup($dest);
		}
		//

		// return upload result
		return $result;
	}

	/**
	 * Helper method used to upload the given file (retrieved from $_FILES)
	 * into the specified destination.
	 *
	 * @param 	array 	$file 		An associative array with the file details.
	 * @param 	string 	$dest 		The destination path.
	 * @param 	string 	$filters 	A string (or a regex) containing the allowed extensions.
	 *
	 * @return 	array 	The uploading result.
	 *
	 * @throws  RuntimeException
	 * 
	 * @since 	1.3.0
	 */
	public static function uploadFileFromRequest($file, $dest, $filters = '*')
	{
		jimport('joomla.filesystem.file');

		$dest = rtrim($dest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		
		if (empty($file['name']))
		{
			throw new RuntimeException('Missing file', 400);
		}

		$src = $file['tmp_name'];

		// extract file name and extension
		if (preg_match("/(.*?)(\.[0-9a-z]{2,})$/i", basename($file['name']), $match))
		{
			$filename = $match[1];
			$fileext  = $match[2];
		}
		else
		{
			// probably no extension provided
			$filename = basename($file['name']);
			$fileext  = '';
		}

		$j = '';
		
		if (file_exists($dest . $filename . $fileext))
		{
			$j = 2;

			while (file_exists($dest . $filename . '-' . $j . $fileext))
			{
				$j++;
			}

			$j = '-' . $j;
		}

		$finaldest = $dest . $filename . $j . $fileext;

		if ($filters !== '*')
		{
			$ext = $file['type'];

			// check if we have a regex
			if (preg_match("/^[#\/]/", $filters) && preg_match("/[#\/][a-z]*$/", $filters))
			{
				if (!preg_match($filters, $ext))
				{
					// extension not supported
					throw new RuntimeException(sprintf('Extension [%s] is not supported', $ext), 400);
				}
			}
			else
			{
				// get all supported types
				$types = array_map('strtolower', array_filter(explode(',', $filters)));

				if (!in_array($ext, $types))
				{
					// extension not supported
					throw new RuntimeException(sprintf('Extension [%s] is not supported', $ext), 400);
				}
			}
		}
		
		// try to upload the file
		if (!JFile::upload($src, $finaldest))
		{
			throw new RuntimeException(sprintf('Unable to upload the file [%s] to [%s]', $src, $finaldest), 500);
		}

		$file = new stdClass;
		$file->name     = $filename . $j;
		$file->ext      = ltrim($fileext, '.');
		$file->filename = basename($finaldest);
		$file->path     = $finaldest;
		
		return $file;
	}

	/**
	 * Gets the instance of a specific report class.
	 * 
	 * @param 	string 	$report 	the name of the report to load.
	 * 
	 * @return 	mixed 	false or report object instance.
	 * 
	 * @since 	1.3.0
	 */
	public static function getReportInstance($report)
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR . 'report.php';

		return VikBookingReport::getInstanceOf($report);
	}

	/**
	 * Checks whether the guest reviews are enabled and VCM is installed.
	 * 
	 * @return 	boolean 	true if enabled, false otherwise.
	 * 
	 * @since 	1.3.0
	 */
	public static function allowGuestReviews()
	{
		$dbo = JFactory::getDbo();
		$vcm_installed = is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php');
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='grenabled';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$s = $dbo->loadResult();
			return ((int)$s === 1 && $vcm_installed);
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('grenabled', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return $vcm_installed;
	}

	/**
	 * Gets the minimum chars for the guest review message.
	 * 
	 * @return 	int 	minimum number of chars for the comment (0 = no limits, -1 = disabled).
	 * 
	 * @since 	1.3.0
	 */
	public static function guestReviewMinChars()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='grminchars';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return (int)$dbo->loadResult();
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('grminchars', '15');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 15;
	}

	/**
	 * The approval type for new guest reviews.
	 * 
	 * @return 	string 		auto or manual.
	 * 
	 * @since 	1.3.0
	 */
	public static function guestReviewsApproval()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='grappr';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return $dbo->loadResult() == 'manual' ? 'manual' : 'auto';
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('grappr', 'auto');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 'auto';
	}

	/**
	 * The type of reviews guests should leave.
	 * 
	 * @return 	string 		global or service.
	 * 
	 * @since 	1.3.0
	 */
	public static function guestReviewsType()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='grtype';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return $dbo->loadResult() == 'global' ? 'global' : 'service';
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('grtype', 'service');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 'service';
	}

	/**
	 * The services to be reviewed by the guests.
	 * 
	 * @return 	array 		list, empty or not, of the services to be reviewed.
	 * 
	 * @since 	1.3.0
	 */
	public static function guestReviewsServices()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`service_name` FROM `#__vikbooking_greview_service` GROUP BY `service_name` ORDER BY `id` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return $dbo->loadAssocList();
		}
		// insert the default services
		$default_services = array(
			JText::translate('VBOGREVVALUE'),
			JText::translate('VBOGREVLOCATION'),
			JText::translate('VBOGREVSTAFF'),
			JText::translate('VBOGREVCLEAN'),
			JText::translate('VBOGREVCOMFORT'),
			JText::translate('VBOGREVFACILITIES'),
		);
		foreach ($default_services as $def_service) {
			$q = "INSERT INTO `#__vikbooking_greview_service` (`service_name`) VALUES (" . $dbo->quote($def_service) . ");";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		return array();
	}

	/**
	 * Checks whether the guest reviews should be downloaded (max 1 per day).
	 * VCM must be installed in order to download the new reviews.
	 * 
	 * @return 	int 	-1 if VCM is not installed, 0 if already downloaded, 1 for download.
	 * 
	 * @since 	1.3.0
	 */
	public static function shouldDownloadReviews()
	{
		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			return -1;
		}

		$today = date('Y-m-d');

		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='gr_last_download' LIMIT 1;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$last_download = $dbo->loadResult();
			// update last download day
			$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $dbo->quote($today) . " WHERE `param`='gr_last_download';";
			$dbo->setQuery($q);
			$dbo->execute();
			//
			return ($last_download != $today ? 1 : 0);
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('gr_last_download', " . $dbo->quote($today) . ");";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	/**
	 * Tells whether a booking can be reviewed.
	 * 
	 * @param 	array 		$booking 	booking array details.
	 * 
	 * @return 	boolean 	true if a review can be left, false otherwise.
	 * 
	 * @since 	1.3.0
	 */
	public static function canBookingBeReviewed($booking)
	{
		// reviews must be enabled and supported
		if (!self::allowGuestReviews()) {
			return false;
		}

		// booking status must be confirmed
		if ($booking['status'] != 'confirmed') {
			return false;
		}

		// review can be left starting from the check-out day
		$checkout_info = getdate($booking['checkout']);
		$checkout_midn = mktime(0, 0, 0, $checkout_info['mon'], $checkout_info['mday'], $checkout_info['year']);
		if (time() < $checkout_midn) {
			return false;
		}

		// make sure a review for this booking does not exist
		$noreview = true;
		$dbo = JFactory::getDbo();
		try {
			$q = "SELECT `id` FROM `#__vikchannelmanager_otareviews` WHERE `idorder`=" . $dbo->quote($booking['id']) . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			$noreview = ($dbo->getNumRows() < 1);
		} catch (Exception $e) {
			// if the query fails, we do not allow the review to be left
			$noreview = false;
		}

		return $noreview;
	}

	/**
	 * Gets the review for a booking, no matter if it's published or not.
	 * Review will be returned only if reviews are enabled and supported.
	 * Only reviews left through the website, no OTA reviews as this method
	 * should be used in the front-end to display the review to the guest.
	 * 
	 * @param 	array 		$booking 	booking array details.
	 * 
	 * @return 	mixed 		associative array or false.
	 * 
	 * @since 	1.3.0
	 */
	public static function getBookingReview($booking)
	{
		// reviews must be enabled and supported
		if (!self::allowGuestReviews()) {
			return false;
		}

		if (empty($booking['id'])) {
			return false;
		}

		// make sure a review for this booking exists
		$dbo = JFactory::getDbo();
		$review = array();
		try {
			$q = "SELECT * FROM `#__vikchannelmanager_otareviews` WHERE `idorder`=" . $dbo->quote($booking['id']) . " LIMIT 1;";
			$dbo->setQuery($q);
			$dbo->execute();
			$review = $dbo->getNumRows() ? $dbo->loadAssoc() : $review;
		} catch (Exception $e) {
			// query has failed
			$review = array();
		}

		if (count($review)) {
			// make sure the review was left from the website
			$review['uniquekey'] = (int)$review['uniquekey'];
		}
		
		if (count($review) && !empty($review['uniquekey'])) {
			// this is an OTA review
			return false;
		}

		if (count($review) && !empty($review['content'])) {
			// decode content
			$review['content'] = json_decode($review['content'], true);
		}

		return count($review) ? $review : false;
	}

	/**
	 * Attempts to find a translation for a raw customer data label.
	 * Bookings downloaded from the OTAs will save a raw string of information
	 * composed of pairs of label-value separated by new line feeds.
	 * We try to translate the given label into the current language.
	 * 
	 * @param 	string 	$label 	the raw string label to translate.
	 * 
	 * @return 	string 			either the original or the translated label.
	 * 
	 * @since 	1.3.5
	 * @since 	1.4.0  			back-end support added.
	 */
	public static function tnCustomerRawDataLabel($label)
	{
		// this is a map of the known labels
		$known_lbls = array(
			'NAME' => 'VBOCUSTOMERNOMINATIVE',
			'COUNTRY' => 'ORDER_STATE',
			'EMAIL' => 'VBMAIL',
			'TELEPHONE' => 'VBPHONE',
			'PHONE' => 'VBPHONE',
			'SPECIALREQUEST' => 'ORDER_SPREQUESTS',
			'MEAL_PLAN' => 'VBOMEALPLAN',
			'CITY' => 'VBCITY',
			'BEDPREFERENCE' => 'VBOBEDPREFERENCE',
			'BOOKER_IS_GENIUS' => 'VBOBOOKERISGENIUS',
		);

		if (self::isAdmin()) {
			// override the map of known translation strings
			$known_lbls = array(
				'NAME' => 'ORDER_NAME',
				'COUNTRY' => 'ORDER_STATE',
				'ADDRESS' => 'ORDER_ADDRESS',
				'CITY' => 'ORDER_CITY',
				'LOCATION' => 'ORDER_CITY',
				'EMAIL' => 'ORDER_EMAIL',
				'TELEPHONE' => 'ORDER_PHONE',
				'PHONE' => 'ORDER_PHONE',
				'SPECIALREQUEST' => 'ORDER_SPREQUESTS',
			);
		}

		// we get rid of any empty space by keeping the underscores
		$converted = str_replace(' ', '', strtoupper($label));

		if (isset($known_lbls[$converted])) {
			// this language definition has been mapped
			return JText::translate($known_lbls[$converted]);
		}

		// we try to guess the translation string by prepending VBO
		$guessed = JText::translate('VBO' . $converted);
		if ($guessed != 'VBO' . $converted) {
			// the label was translated correctly
			return $guessed;
		}

		// this label could not be translated, so we return the plain string
		return $label;
	}

	/**
	 * We check whether some bookings are available for import from third party plugins.
	 * 
	 * @return 	mixed 		array with list of plugins supported, false otherwise.
	 * 
	 * @wponly 				this method is only useful for WordPress.
	 * 
	 * @since 	1.3.5
	 */
	public static function canImportBookingsFromThirdPartyPlugins()
	{
		$dbo = JFactory::getDbo();

		$plugins = array();

		/**
		 * As requested from hundreds of our clients, for the moment we check only the custom
		 * post types of type "mphb_booking" to see if some bookings are available for import.
		 */
		$q = "SELECT `post_type` FROM `#__posts` WHERE `post_type`=" . $dbo->quote('mphb_booking') . ";";
		$dbo->setQuery($q);
		$dbo->execute();

		if (!$dbo->getNumRows()) {
			return false;
		}

		// push this third party plugin
		$plugins['mphb'] = 'MotoPress Hotel Booking';

		return count($plugins) ? $plugins : false;
	}

	/**
	 * This method returns a list of the known languages sorted by the
	 * administrator custom preferences. Useful for the phone input fields.
	 * 
	 * @param 	boolean 	$code_assoc 	whether to get an associative array with the lang name.
	 * 
	 * @return 	array 		the sorted list of preferred countries.
	 * 
	 * @since 	1.3.11
	 */
	public static function preferredCountriesOrdering($code_assoc = false)
	{
		$preferred_countries = array();

		// try to get the preferred countries from db
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='preferred_countries';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// create empty configuration record
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('preferred_countries', '[]');";
			$dbo->setQuery($q);
			$dbo->execute();
		} else {
			$preferred_countries = json_decode($dbo->loadResult());
		}

		// get the default known languages
		$sorted_known_langs = self::getVboApplication()->getKnownLanguages();
		
		if (!is_array($preferred_countries) || !count($preferred_countries)) {
			// sort the default known languages by country code alphabetically
			ksort($sorted_known_langs);
			foreach ($sorted_known_langs as $k => $v) {
				$langsep = strpos($k, '_') !== false ? '_' : '-';
				$langparts = explode($langsep, $k);
				array_push($preferred_countries, isset($langparts[1]) ? strtolower($langparts[1]) : strtolower($langparts[0]));
			}
			// update the database record
			$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $dbo->quote(json_encode($preferred_countries)) . " WHERE `param`='preferred_countries';";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		if ($code_assoc) {
			// this is useful for displaying the preferred countries codes together with the language name
			$map = array();
			foreach ($preferred_countries as $ccode) {
				// look for the current country code in the keys of the known language tags
				$match_found = false;
				foreach ($sorted_known_langs as $langtag => $langinfo) {
					$langsep = strpos($langtag, '_') !== false ? '_' : '-';
					$langparts = explode($langsep, $langtag);
					if (isset($langparts[1]) && strtoupper($ccode) == strtoupper($langparts[1])) {
						// match found
						$match_found = true;
						$map[$ccode] = !empty($langinfo['nativeName']) ? $langinfo['nativeName'] : $langinfo['name'];
					} elseif (strtoupper($ccode) == strtoupper($langparts[0])) {
						// match found
						$match_found = true;
						$map[$ccode] = !empty($langinfo['nativeName']) ? $langinfo['nativeName'] : $langinfo['name'];
					}
				}
				if (!$match_found) {
					// in case someone would like to add a custom country code via DB, we allow to do so by returning the raw value
					$map[$ccode] = strtoupper($ccode);
				}
			}
			if (count($map)) {
				// set the associatve array to be returned
				$preferred_countries = $map;
			}
		}

		return $preferred_countries;
	}

	/**
	 * Gets the instance of the admin widgets helper class.
	 * 
	 * @return 	VikBookingHelperAdminWidgets
	 * 
	 * @since 	1.4.0
	 */
	public static function getAdminWidgetsInstance()
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'admin_widgets.php';

		return VikBookingHelperAdminWidgets::getInstance();
	}

	/**
	 * Gets the instance of the conditional rules helper class.
	 * 
	 * @param 	bool 	$require_only 	whether to return the object.
	 * 
	 * @return 	mixed 	VikBookingHelperConditionalRules or true.
	 * 
	 * @since 	1.4.0
	 */
	public static function getConditionalRulesInstance($require_only = false)
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'conditional_rules.php';

		return $require_only ? true : VikBookingHelperConditionalRules::getInstance();
	}

	/**
	 * Gets the instance of the geocoding helper class.
	 * 
	 * @param 	bool 	$require_only 	whether to return the object.
	 * 
	 * @return 	mixed 	VikBookingHelperGeocoding or true.
	 * 
	 * @since 	1.4.0
	 */
	public static function getGeocodingInstance($require_only = false)
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'geocoding.php';

		return $require_only ? true : VikBookingHelperGeocoding::getInstance();
	}

	/**
	 * Helper method to cope with the removal of the same method
	 * in the JApplication class introduced with Joomla 4. Using
	 * isClient() would break the compatibility with J < 3.7 so
	 * we can rely on this helper method to avoid Fatal Errors.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	October 2020
	 */
	public static function isAdmin()
	{
		$app = JFactory::getApplication();
		if (method_exists($app, 'isClient')) {
			return $app->isClient('administrator');
		}

		return $app->isAdmin();
	}

	/**
	 * Helper method to cope with the removal of the same method
	 * in the JApplication class introduced with Joomla 4. Using
	 * isClient() would break the compatibility with J < 3.7 so
	 * we can rely on this helper method to avoid Fatal Errors.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	October 2020
	 */
	public static function isSite()
	{
		$app = JFactory::getApplication();
		if (method_exists($app, 'isClient')) {
			return $app->isClient('site');
		}

		return $app->isSite();
	}

	/**
	 * Gets the Google Maps API Key.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function getGoogleMapsKey()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='gmapskey';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return $dbo->loadResult();
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('gmapskey', '');";
		$dbo->setQuery($q);
		$dbo->execute();
		return '';
	}

	/**
	 * Checks whether the interactive map booking is enabled.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function interactiveMapEnabled()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='interactive_map';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return ((int)$dbo->loadResult() > 0);
		}
		
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('interactive_map', '0');";
		$dbo->setQuery($q);
		$dbo->execute();
		
		return false;
	}

	/**
	 * Gets the preferred colors saved in the configuration, if any.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function getPreferredColors()
	{
		$dbo = JFactory::getDbo();
		$pref_colors = array(
			'textcolor' => '',
			'bgcolor' => '',
			'fontcolor' => '',
			'bgcolorhov' => '',
			'fontcolorhov' => '',
		);

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='pref_colors';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$colors = json_decode($dbo->loadResult(), true);
			if (!is_array($colors) || !isset($colors['textcolor'])) {
				return $pref_colors;
			}
			return $colors;
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('pref_colors', '{}');";
		$dbo->setQuery($q);
		$dbo->execute();
		return $pref_colors;
	}

	/**
	 * Adds to the document inline styles for the preferred colors, if any.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function loadPreferredColorStyles()
	{
		$view = VikRequest::getString('view', '', 'request');
		$pref_colors = self::getPreferredColors();
		
		$css_classes = array();
		
		if (!empty($pref_colors['textcolor'])) {
			// titles and headings
			array_push($css_classes, '.vbo-pref-color-text { color: ' . $pref_colors['textcolor'] . ' !important; }');
			// stepbar, oconfirm
			array_push($css_classes, 'ol.vbo-stepbar li.vbo-step-complete, ol.vbo-stepbar li.vbo-step-current, ol.vbo-stepbar li.vbo-step-current:before, .vbo-coupon-outer, .vbo-enterpin-block { border-color: ' . $pref_colors['textcolor'] . ' !important; }');
			// buttons secondary color
			array_push($css_classes, '.vbo-pref-color-btn-secondary { border: 2px solid ' . $pref_colors['textcolor'] . ' !important; color: ' . $pref_colors['textcolor'] . ' !important; background: transparent !important; }');
			if (!empty($pref_colors['fontcolor'])) {
				array_push($css_classes, '.vbo-pref-color-btn-secondary:hover { color: ' . $pref_colors['fontcolor'] . ' !important; background: ' . $pref_colors['textcolor'] . ' !important; }');
			}
			// datepicker
			array_push($css_classes, '.ui-datepicker .ui-datepicker-today {
				color: ' . $pref_colors['textcolor'] . ' !important;
				border-color: ' . $pref_colors['textcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-today a {
				color: ' . $pref_colors['textcolor'] . ' !important;
			}');
			// operators tableaux
			if ($view == 'tableaux') {
				array_push($css_classes, '.vbo-roomdaynote-empty .vbo-roomdaynote-trigger i { color: ' . $pref_colors['textcolor'] . ' !important; }');
			}
		}

		if (!empty($pref_colors['bgcolor']) && !empty($pref_colors['fontcolor'])) {
			// elements with backgrounds
			array_push($css_classes, '.vbo-pref-color-element { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			array_push($css_classes, '.vbo-pref-bordercolor { border-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			array_push($css_classes, '.vbo-pref-bordertext { color: ' . $pref_colors['bgcolor'] . ' !important; border-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			// buttons with backgrounds
			array_push($css_classes, '.vbo-pref-color-btn { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			// stepbar
			array_push($css_classes, 'ol.vbo-stepbar li.vbo-step-complete:before { background-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			// datepicker
			array_push($css_classes, '.ui-datepicker table td:hover {
				border-color: ' . $pref_colors['bgcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-current-day {
				background: ' . $pref_colors['bgcolor'] . ' !important;
				color: ' . $pref_colors['fontcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-current-day a {
				color: ' . $pref_colors['fontcolor'] . ' !important;
			}');
			// operators tableaux
			if ($view == 'tableaux') {
				array_push($css_classes, '.vbo-tableaux-roombooks > div, .vbo-tableaux-togglefullscreen { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			}
		}

		if (!empty($pref_colors['bgcolorhov']) && !empty($pref_colors['fontcolorhov'])) {
			// buttons with backgrounds during hover state
			array_push($css_classes, '.vbo-pref-color-btn:hover { background-color: ' . $pref_colors['bgcolorhov'] . ' !important; color: ' . $pref_colors['fontcolorhov'] . ' !important; }');
			// operators tableaux
			if ($view == 'tableaux') {
				array_push($css_classes, '.vbo-tableaux-togglefullscreen:hover { background-color: ' . $pref_colors['bgcolorhov'] . ' !important; color: ' . $pref_colors['fontcolorhov'] . ' !important; }');
			}
		}

		if (!count($css_classes)) {
			return;
		}

		// add in-line style declaration
		JFactory::getDocument()->addStyleDeclaration(implode("\n", $css_classes));
	}

	/**
	 * Given the full endpoint URL for the AJAX request,
	 * it returns an appropriate URI for the platform.
	 * 
	 * @param 	mixed 	 $query 	The query string or a routed URL.
	 * @param 	boolean  $xhtml  	Replace & by &amp; for XML compliance.
	 * 
	 * @return 	string 				The AJAX end-point URI.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function ajaxUrl($query = '', $xhtml = false)
	{
		// instantiate path based on specified query
		$path = new JUri($query);

		// delete option var from query
		$path->delVar('option');

		// force action in query
		$path->setVar('action', 'vikbooking');

		// force application client in case of front-end
		if (JFactory::getApplication()->isClient('site')) {
			$path->setVar('vik_ajax_client', 'site');
		}

		/**
		 * @wponly 	create AJAX URI by using admin-ajax
		 */
		$uri = admin_url('admin-ajax.php') . '?' . $path->getQuery();

		if ($xhtml) {
			// try to make "&" XML safe
			$uri = preg_replace("/&(?!amp;)/", '&amp;', $uri);
		}

		return $uri;
	}

	/**
	 * Tells whether no tax rates have been defined so far.
	 * 
	 * @return 	bool 	True if no tax rates defined, false otherwise.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function noTaxRates()
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`, `aliq` FROM `#__vikbooking_iva` WHERE `aliq` > 0;";
		$dbo->setQuery($q);
		$dbo->execute();

		return ($dbo->getNumRows() < 1);
	}

	/**
	 * Booking Type feature is strictly connected to VCM and OTAs. Updated
	 * versions of VBO will always support it as long as VCM is installed.
	 * Needed by VCM to understand whether certain SQL queries can be performed.
	 * 
	 * @return 	bool 	true if VCM exists, false otherwise.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function isBookingTypeSupported()
	{
		return is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php');
	}
}

if (!class_exists('vikResizer')) {
	class vikResizer {

		public function __construct() {
			//objects of this class can also be instantiated without calling the methods statically.
		}

		/**
		 * Resizes an image proportionally. For PNG files it can optionally
		 * trim the image to exclude the transparency, and add some padding to it.
		 * All PNG files keep the alpha background in the resized version.
		 *
		 * @param 	string 		$fileimg 	path to original image file
		 * @param 	string 		$dest 		path to destination image file
		 * @param 	int 		$towidth 	
		 * @param 	int 		$toheight 	
		 * @param 	bool 		$trim_png 	remove empty background from image
		 * @param 	string 		$trim_pad 	CSS-style version of padding (top right bottom left) ex: '1 2 3 4'
		 *
		 * @return 	boolean
		 */
		public static function proportionalImage($fileimg, $dest, $towidth, $toheight, $trim_png = false, $trim_pad = null) {
			if (!file_exists($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				copy($fileimg, $dest);
				return true;
			}

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid > $towidth || $ohei > $toheight) {
				$xscale = $owid / $towidth;
				$yscale = $ohei / $toheight;
				if ($yscale > $xscale) {
					$new_width = round($owid * (1 / $yscale));
					$new_height = round($ohei * (1 / $yscale));
				} else {
					$new_width = round($owid * (1 / $xscale));
					$new_height = round($ohei * (1 / $xscale));
				}

				$imageresized = imagecreatetruecolor($new_width, $new_height);

				switch ($type) {
					case '1' :
						$imagetmp = imagecreatefromgif ($fileimg);
						break;
					case '2' :
						$imagetmp = imagecreatefromjpeg($fileimg);
						break;
					default :
						//keep alpha for PNG files
						$background = imagecolorallocate($imageresized, 0, 0, 0);
						imagecolortransparent($imageresized, $background);
						imagealphablending($imageresized, false);
						imagesavealpha($imageresized, true);
						//
						$imagetmp = imagecreatefrompng($fileimg);
						break;
				}

				imagecopyresampled($imageresized, $imagetmp, 0, 0, 0, 0, $new_width, $new_height, $owid, $ohei);

				switch ($type) {
					case '1' :
						imagegif ($imageresized, $dest);
						break;
					case '2' :
						imagejpeg($imageresized, $dest);
						break;
					default :
						if ($trim_png) {
							self::imageTrim($imageresized, $background, $trim_pad);
						}
						imagepng($imageresized, $dest);
						break;
				}

				imagedestroy($imageresized);
			} else {
				copy($fileimg, $dest);
			}
			/**
			 * @wponly  in order to not lose resized files after installing an update,
			 * 			we need to move any uploaded file onto a recovery folder.
			 */
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerUploadBackup($dest);
			//
			return true;
		}

		/**
		 * (BETA) Resizes an image proportionally. For PNG files it can optionally
		 * trim the image to exclude the transparency, and add some padding to it.
		 * All PNG files keep the alpha background in the resized version.
		 *
		 * @param 	resource 	$im 		Image link resource (reference)
		 * @param 	int 		$bg 		imagecolorallocate color identifier
		 * @param 	string 		$pad 		CSS-style version of padding (top right bottom left) ex: '1 2 3 4'
		 *
		 * @return 	void
		 */
		public static function imagetrim(&$im, $bg, $pad = null){
			// Calculate padding for each side.
			if (isset($pad)) {
				$pp = explode(' ', $pad);
				if (isset($pp[3])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[3]);
				} elseif (isset($pp[2])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[1]);
				} elseif (isset($pp[1])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[0], (int) $pp[1]);
				} else {
					$p = array_fill(0, 4, (int) $pp[0]);
				}
			} else {
				$p = array_fill(0, 4, 0);
			}

			// Get the image width and height.
			$imw = imagesx($im);
			$imh = imagesy($im);

			// Set the X variables.
			$xmin = $imw;
			$xmax = 0;

			// Start scanning for the edges.
			for ($iy=0; $iy<$imh; $iy++) {
				$first = true;
				for ($ix=0; $ix<$imw; $ix++) {
					$ndx = imagecolorat($im, $ix, $iy);
					if ($ndx != $bg) {
						if ($xmin > $ix) {
							$xmin = $ix;
						}
						if ($xmax < $ix) {
							$xmax = $ix;
						}
						if (!isset($ymin)) {
							$ymin = $iy;
						}
						$ymax = $iy;
						if ($first) {
							$ix = $xmax;
							$first = false;
						}
					}
				}
			}

			// The new width and height of the image. (not including padding)
			$imw = 1+$xmax-$xmin; // Image width in pixels
			$imh = 1+$ymax-$ymin; // Image height in pixels

			// Make another image to place the trimmed version in.
			$im2 = imagecreatetruecolor($imw+$p[1]+$p[3], $imh+$p[0]+$p[2]);

			// Make the background of the new image the same as the background of the old one.
			$bg2 = imagecolorallocate($im2, ($bg >> 16) & 0xFF, ($bg >> 8) & 0xFF, $bg & 0xFF);
			imagefill($im2, 0, 0, $bg2);

			// Copy it over to the new image.
			imagecopy($im2, $im, $p[3], $p[0], $xmin, $ymin, $imw, $imh);

			// To finish up, we replace the old image which is referenced.
			$im = $im2;
		}

		public static function bandedImage($fileimg, $dest, $towidth, $toheight, $rgb) {
			if (!file_exists($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				copy($fileimg, $dest);
				return true;
			}

			$exp = explode(",", $rgb);
			if (count($exp) == 3) {
				$r = trim($exp[0]);
				$g = trim($exp[1]);
				$b = trim($exp[2]);
			} else {
				$r = 0;
				$g = 0;
				$b = 0;
			}

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid > $towidth || $ohei > $toheight) {
				$xscale = $owid / $towidth;
				$yscale = $ohei / $toheight;
				if ($yscale > $xscale) {
					$new_width = round($owid * (1 / $yscale));
					$new_height = round($ohei * (1 / $yscale));
					$ydest = 0;
					$diff = $towidth - $new_width;
					$xdest = ($diff > 0 ? round($diff / 2) : 0);
				} else {
					$new_width = round($owid * (1 / $xscale));
					$new_height = round($ohei * (1 / $xscale));
					$xdest = 0;
					$diff = $toheight - $new_height;
					$ydest = ($diff > 0 ? round($diff / 2) : 0);
				}

				$imageresized = imagecreatetruecolor($towidth, $toheight);

				$bgColor = imagecolorallocate($imageresized, (int) $r, (int) $g, (int) $b);
				imagefill($imageresized, 0, 0, $bgColor);

				switch ($type) {
					case '1' :
						$imagetmp = imagecreatefromgif ($fileimg);
						break;
					case '2' :
						$imagetmp = imagecreatefromjpeg($fileimg);
						break;
					default :
						$imagetmp = imagecreatefrompng($fileimg);
						break;
				}

				imagecopyresampled($imageresized, $imagetmp, $xdest, $ydest, 0, 0, $new_width, $new_height, $owid, $ohei);

				switch ($type) {
					case '1' :
						imagegif ($imageresized, $dest);
						break;
					case '2' :
						imagejpeg($imageresized, $dest);
						break;
					default :
						imagepng($imageresized, $dest);
						break;
				}

				imagedestroy($imageresized);

				return true;
			} else {
				copy($fileimg, $dest);
			}
			return true;
		}

		public static function croppedImage($fileimg, $dest, $towidth, $toheight) {
			if (!file_exists($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				copy($fileimg, $dest);
				return true;
			}

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid <= $ohei) {
				$new_width = $towidth;
				$new_height = ($towidth / $owid) * $ohei;
			} else {
				$new_height = $toheight;
				$new_width = ($new_height / $ohei) * $owid;
			}

			switch ($type) {
				case '1' :
					$img_src = imagecreatefromgif ($fileimg);
					$img_dest = imagecreate($new_width, $new_height);
					break;
				case '2' :
					$img_src = imagecreatefromjpeg($fileimg);
					$img_dest = imagecreatetruecolor($new_width, $new_height);
					break;
				default :
					$img_src = imagecreatefrompng($fileimg);
					$img_dest = imagecreatetruecolor($new_width, $new_height);
					break;
			}

			imagecopyresampled($img_dest, $img_src, 0, 0, 0, 0, $new_width, $new_height, $owid, $ohei);

			switch ($type) {
				case '1' :
					$cropped = imagecreate($towidth, $toheight);
					break;
				case '2' :
					$cropped = imagecreatetruecolor($towidth, $toheight);
					break;
				default :
					$cropped = imagecreatetruecolor($towidth, $toheight);
					break;
			}

			imagecopy($cropped, $img_dest, 0, 0, 0, 0, $owid, $ohei);

			switch ($type) {
				case '1' :
					imagegif ($cropped, $dest);
					break;
				case '2' :
					imagejpeg($cropped, $dest);
					break;
				default :
					imagepng($cropped, $dest);
					break;
			}

			imagedestroy($img_dest);
			imagedestroy($cropped);

			return true;
		}

	}
}