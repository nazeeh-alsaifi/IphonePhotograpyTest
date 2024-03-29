<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\CommentWritten;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CommentWrittenListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CommentWritten $event): void
    {
        // get user model
        $user_id = $event->comment->user_id;
        $user = User::findOrFail($user_id);

        //get number of comments
        $number_of_comments = Comment::where('user_id', $user->id)->count();

        // get the achievement using number of comments or null
        $achievement = $this->getAchievementByNumberOfCommentsAndType($number_of_comments, 'comments_written');

        if (!is_null($achievement)) {
            $user->achievements()->attach($achievement);
            AchievementUnlocked::dispatch($achievement->name, $user);
        }
        $this->handleBadgeUnlocking($user);

    }

    function getAchievementByNumberOfCommentsAndType(int $number_of_comments, string $type): Achievement|null
    {
        $achievement = Achievement::
            where('count_to_reach', $number_of_comments)
            ->where('type', 'comments_written')
            ->first();

        return $achievement;
    }
    function handleBadgeUnlocking($user): void
    {
        $number_of_achievements = $user->achievements()->count();
        if ($number_of_achievements > 0) {
            $badge = Badge::where('achievements_count', $number_of_achievements)->first();
        }
        if (isset($badge)) {
            $user->badges()->attach($badge);
            BadgeUnlocked::dispatch($badge->name, $user);
        }
    }

}