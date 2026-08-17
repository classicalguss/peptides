<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const TOPICS = [
        'order' => 'An existing order',
        'product' => 'Product or research collection question',
        'coa' => 'Lab report / certificate request',
        'wholesale' => 'Wholesale or bulk enquiry',
        'general' => 'Something else',
    ];

    protected $fillable = [
        'name',
        'email',
        'topic',
        'order_reference',
        'message',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function topicLabel(): string
    {
        return self::TOPICS[$this->topic] ?? self::TOPICS['general'];
    }
}
