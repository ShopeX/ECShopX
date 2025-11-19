<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Ego;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Tymon\JWTAuth\Contracts\JWTSubject;


class GenericUser implements UserContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * All of the user's attributes.
     *
     * @var array
     */
    public $attributes;

    /**
     * Create a new generic User object.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes)
    {
          app('authorization')->checkUserAuth($attributes);
          $this->attributes = $attributes;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->attributes['id'];
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return $this->attributes;
    }

    /**
     * Dynamically access the user's attributes.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key=null)
    {
        if( !$key )
        {
            return $this->attributes;
        }

        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }
}
