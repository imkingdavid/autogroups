<?php
/**
*
* Auto Groups extension for the phpBB Forum Software package.
*
* @copyright (c) 2014 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace phpbb\autogroups\conditions\type;

/**
 * Auto Groups Birthdays class
 */
class birthdays extends \phpbb\autogroups\conditions\type\base
{
	/**
	 * Get condition type
	 *
	 * @return string Condition type
	 * @access public
	 */
	public function get_condition_type()
	{
		return 'phpbb.autogroups.type.birthdays';
	}

	/**
	 * Get condition field (this is the field to check)
	 *
	 * @return string Condition field name
	 * @access public
	 */
	public function get_condition_field()
	{
		return 'user_birthday';
	}

	/**
	 * Get condition type name
	 *
	 * @return string Condition type name
	 * @access public
	 */
	public function get_condition_type_name()
	{
		return $this->user->lang('AUTOGROUPS_TYPE_BIRTHDAYS');
	}

	/**
	 * Get users to apply to this condition
	 * Birthdays is typically called via cron with no $options arguments.
	 * By default, get all users, otherwise use user_id(s) supplied in $options arg.
	 *
	 * @param array $options Array of optional data
	 * @return array Array of users ids as keys and their condition data as values
	 * @access public
	 */
	public function get_users_for_condition($options = array())
	{
		// The user data this condition needs to check
		$condition_data = array(
			$this->get_condition_field(),
		);

		// Merge default options, empty user array as the default
		$options = array_merge(array(
			'users'		=> array(),
		), $options);

		// Prepare the user ids data for use in the query
		$user_ids = $this->helper->prepare_users_for_query($options['users']);

		// Get data for the users to be checked (exclude bots, guests and inactive users)
		$sql = 'SELECT user_id, ' . implode(', ', $condition_data) . '
			FROM ' . USERS_TABLE . '
			WHERE ' . $this->db->sql_in_set('user_type', array(USER_INACTIVE, USER_IGNORE), true) . '
				AND ' . $this->db->sql_in_set('user_id', $user_ids, !sizeof($user_ids), true);
		$result = $this->db->sql_query($sql);

		$user_data = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_data[$row['user_id']] = array(
				$this->get_condition_field() => $this->get_user_age($row['user_birthday'])
			);
		}
		$this->db->sql_freeresult($result);

		return $user_data;
	}

	/**
	 * Helper to get the users current age
	 *
	 * @param string $user_birthday The users birth date (e.g.: 20-10-1990)
	 * @return int The users age in years
	 */
	protected function get_user_age($user_birthday)
	{
		static $now;

		if (!isset($now))
		{
			// Get the current UTC timestamp
			$now = phpbb_gmgetdate();
		}

		// Get birth year from the stored birth date
		$birthday_year = (int) substr($user_birthday, -4);

		// Return the users age
		return ($birthday_year) ? (int) max(0, $now['year'] - $birthday_year) : 0;
	}
}
