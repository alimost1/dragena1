<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'language',
        'status',
        'video_url',
        'error_message',
    ];

    /**
     * Get the user that owns the video.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the video is ready to play (completed and has URL).
     */
    public function isReadyToPlay(): bool
    {
        return $this->status === 'completed' && !empty($this->video_url);
    }

    /**
     * Check if the video is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the video failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}