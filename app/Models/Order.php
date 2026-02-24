<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'handling_instructions' => 'array'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function charges()
    {
        return $this->hasMany(OrderCharge::class, 'order_id');
    }

    public function paymentLogs()
    {
        return $this->hasMany(OrderPaymentLog::class, 'order_id')->orderBy('created_at', 'desc');
    }

    public function activityLogs()
    {
        return $this->hasMany(OrderLog::class, 'order_id')->orderBy('created_at', 'desc');
    }

    public function senderStore()
    {
        return $this->belongsTo(Store::class, 'sender_store_id');
    }

    public function receiverStore()
    {
        return $this->belongsTo(Store::class, 'receiver_store_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bill2()
    {
        return $this->belongsTo(User::class, 'bill_to_id');
    }

    public function deliveryUser()
    {
        return $this->belongsTo(User::class, 'delivery_user');
    }

    public function dealer()
    {
        return $this->belongsTo(User::class, 'dealer_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function utencils()
    {
        return $this->hasMany(OrderUtencil::class, 'order_id');
    }

    public function utencilHistories()
    {
        return $this->hasMany(OrderUtencilHistory::class, 'order_id');
    }

    public function services()
    {
        return $this->hasMany(OrderService::class, 'order_id');
    }

    public function packagingMaterials()
    {
        return $this->hasMany(OrderPackagingMaterial::class, 'order_id');
    }

    public function otherItems()
    {
        return $this->hasMany(OrderOtherItem::class, 'order_id');
    }

    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = now()->format('yMd');

        $lastOrder = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder
            ? ((int) substr($lastOrder->order_number, -4)) + 1
            : 1;

        return "{$prefix}-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_DISPATCHED = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_CANCELLED = 4;
    const STATUS_COMPLETED = 5;

    const PAYMENT_STATUS_UNPAID = 0;
    const PAYMENT_STATUS_PARTIAL = 1;
    const PAYMENT_STATUS_PAID = 2;

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_PENDING => '<span class="badge bg-warning text-dark">Pending</span>',
            self::STATUS_APPROVED => '<span class="badge bg-info">Approved</span>',
            self::STATUS_DISPATCHED => '<span class="badge bg-primary">Dispatched</span>',
            self::STATUS_DELIVERED => '<span class="badge bg-success">Delivered</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-danger">Cancelled</span>',
            self::STATUS_COMPLETED => '<span class="badge bg-success">Completed</span>',
        ];
        return $labels[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getPaymentStatusLabelAttribute()
    {
        $labels = [
            self::PAYMENT_STATUS_UNPAID => '<span class="badge bg-danger">Unpaid</span>',
            self::PAYMENT_STATUS_PARTIAL => '<span class="badge bg-warning text-dark">Partial</span>',
            self::PAYMENT_STATUS_PAID => '<span class="badge bg-success">Paid</span>',
        ];
        return $labels[$this->payment_status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getDispatchedFromAttribute()
    {
        return $this->senderStore->name ?? 'N/A';
    }

    public function getOrderFromAttribute()
    {
        if ($this->order_type === 'dealer') {
            return $this->dealer ? $this->dealer->name . ' (Dealer)' : 'N/A';
        }

        if ($this->receiverStore) {
            return $this->receiverStore->name;
        }

        if ($this->for_customer) {
            return trim(($this->customer_first_name ?? '') . ' ' . ($this->customer_second_name ?? '')) . ' (Customer)';
        }

        return 'N/A';
    }
}
