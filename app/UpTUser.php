<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UpTUser extends Model
{
    public    $incrementing = false;
    public    $timestamps   = false;
    protected $primaryKey   = 'id'; // or null

    /**
     * The database name used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql_other';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user';

    protected $hidden = ['reward_check', 'regist_unix', 'edit_unix', 'login_unix',
                         'state', 'mobile_client_balance', 'premium_flag', 'search_state',
                         'rank_count', 'pass', 'point', 'social_id', 'profile_image', 'search_word'];
}
