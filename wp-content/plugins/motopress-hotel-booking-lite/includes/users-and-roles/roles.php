<?php

/**
 * @since 4.0.0
 */

namespace MPHB\UsersAndRoles;
class Roles
{
    const VERSION = 1; 
    
    const MANAGER = 'mphb_manager';
    const WORKER = 'mphb_worker';
    const CUSTOMER = 'mphb_customer';

    /**
     * @var array
     */
    public $roles = array();

    public function __construct()
    {
        $this->fillRoles();
    }

    public function fillRoles()
    {
        $this->roles[self::MANAGER] = new Role(array(
            'name' => self::MANAGER,
            'description' => __('Hotel Manager', 'motopress-hotel-booking')
        ));

        $this->roles[self::WORKER] = new Role(array(
            'name' => self::WORKER,
            'description' => __('Hotel Worker', 'motopress-hotel-booking')
        ));
    }

    /**
     * 
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * 
     * @return int
     */
    public static function getCurrentVersion()
    {
        return self::VERSION;
    }
}
