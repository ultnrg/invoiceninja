<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyUser extends Pivot
{
    use SoftDeletes;

    //   protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions_updated_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'created_at' => 'timestamp',
        'deleted_at' => 'timestamp',
        'settings' => 'object',
        'notifications' => 'object',
        'permissions' => 'string',
    ];

    protected $fillable = [
        'account_id',
        'permissions',
        'notifications',
        'settings',
        'is_admin',
        'is_owner',
        'is_locked',
        'slack_webhook_url',
        'shop_restricted',
    ];

    protected $touches = ['user'];

    public function getEntityType()
    {
        return self::class;
    }

    public function tax_rates()
    {
        return $this->hasMany(TaxRate::class, 'company_id', 'company_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user_pivot()
    {
        return $this->hasOne(User::class)->withPivot('permissions', 'settings', 'is_admin', 'is_owner', 'is_locked', 'slack_webhook_url', 'migrating');
    }

    public function company_pivot()
    {
        return $this->hasOne(Company::class)->withPivot('permissions', 'settings', 'is_admin', 'is_owner', 'is_locked', 'slack_webhook_url', 'migrating');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /*todo monitor this function - may fail under certain conditions*/
    public function token()
    {
        return $this->hasMany(CompanyToken::class, 'user_id', 'user_id');

        //return $this->hasMany(CompanyToken::class);
        //return $this->hasOne(CompanyToken::class, 'user_id', 'user_id','company_id', 'company_id');


       //return $this->belongsTo(CompanyToken::class, 'user_id', 'user_id');

        // return $this->hasOneThrough(
        //     CompanyToken::class,
        //     CompanyUser::class,
        //     'user_id', // Foreign key on CompanyUser table...
        //     'company_id', // Foreign key on CompanyToken table...
        //     'user_id', // Local key on CompanyToken table...
        //     'company_id' // Local key on CompanyUser table...
        // );
    }

    public function tokens()
    {
        return $this->hasMany(CompanyToken::class, 'user_id', 'user_id');
    }

    public function scopeAuthCompany($query)
    {
        $query->where('company_id', auth()->user()->companyId());

        return $query;
    }
}
