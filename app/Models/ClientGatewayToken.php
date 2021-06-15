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

use App\Utils\Traits\MakesDates;
use Illuminate\Database\Eloquent\Model;

class ClientGatewayToken extends BaseModel
{
    use MakesDates;

    protected $casts = [
        'meta' => 'object',
        'updated_at' => 'timestamp',
        'created_at' => 'timestamp',
        'deleted_at' => 'timestamp',
    ];

    protected $appends = [
        'hashed_id',
    ];

    protected $fillable = [
        'token',
        'routing_number',
        'gateway_customer_reference',
        'gateway_type_id',
        'meta',
    ];

    public function getEntityType()
    {
        return self::class;
    }

    public function client()
    {
        return $this->hasOne(Client::class)->withTrashed();
    }

    public function gateway()
    {
        return $this->hasOne(CompanyGateway::class, 'id', 'company_gateway_id');
    }

    public function gateway_type()
    {
        return $this->hasOne(GatewayType::class, 'id', 'gateway_type_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function user()
    {
        return $this->hasOne(User::class)->withTrashed();
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param mixed $value
     * @param null $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this
            ->where('id', $this->decodePrimaryKey($value))->firstOrFail();
    }
}
