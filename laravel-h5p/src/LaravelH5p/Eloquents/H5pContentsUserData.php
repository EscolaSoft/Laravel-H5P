<?php

namespace EscolaSoft\LaravelH5p\Eloquents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class H5pContentsUserData extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['content_id', 'user_id', 'sub_content_id', 'data_id'];
    protected $fillable = [
        'content_id',
        'user_id',
        'sub_content_id',
        'data_id',
        'data',
        'preload',
        'invalidate',
        'updated_at',
    ];

    protected function setKeysForSaveQuery(Builder $query)
    {
        return $query->where('content_id', $this->getAttribute('content_id'))
            ->where('user_id', $this->getAttribute('user_id'))
            ->where('sub_content_id', $this->getAttribute('sub_content_id'))
            ->where('data_id', $this->getAttribute('data_id'));
    }
}
