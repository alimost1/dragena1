<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Video;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('videos:list', function () {
    $videos = Video::latest()->get();
    
    $this->table(
        ['ID', 'User', 'Status', 'Created At'],
        $videos->map(function ($video) {
            return [
                'id' => $video->id,
                'user' => $video->user ? $video->user->name : 'No User', // Add null check here
                'status' => $video->status,
                'created_at' => $video->created_at->format('Y-m-d H:i:s')
            ];
        })
    );
})->purpose('List all videos in the system');

Artisan::command('users:add-tokens {user} {tokens=10}', function ($user, $tokens) {
    $user = User::where('email', $user)->orWhere('id', $user)->first();
    
    if (!$user) {
        $this->error("User not found!");
        return 1;
    }
    
    $user->tokens += $tokens;
    $user->save();
    
    $this->info("Added {$tokens} tokens to {$user->name}. New balance: {$user->tokens}");
})->purpose('Add tokens to a user account');

Artisan::command('videos:cleanup {days=7}', function ($days) {
    $date = now()->subDays($days);
    $count = Video::where('created_at', '<', $date)
                  ->where('status', 'completed')
                  ->delete();
    
    $this->info("Deleted {$count} old videos.");
})->purpose('Clean up old completed videos');

