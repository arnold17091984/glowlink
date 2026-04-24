<?php

namespace App\Filament\Resources\IndividualTalkResource\Pages;

use App\Actions\LineMessage\PushMessageAction;
use App\Enums\FlagEnum;
use App\Filament\Resources\IndividualTalkResource;
use App\Models\Friend;
use App\Models\Talk;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Messages extends Page
{
    protected static string $resource = IndividualTalkResource::class;

    protected static string $view = 'filament.resources.individual-talk-resource.pages.messages';

    public string $message = '';

    public int $noOfMessage = 20;

    public int $noOfFriends = 7;

    public FlagEnum $mark;

    public array $queryString = [];

    public $activeTab;

    public ?string $temporaryUrl = null;

    public ?TemporaryUploadedFile $file = null;

    public function mount(): void
    {
        $this->queryString = FacadesRequest::query();
        if (isset($this->queryString['uid'])) {
            $this->mark = Friend::whereUserId($this->queryString['uid'])->first()->mark;
        }
    }

    public function talk(): LengthAwarePaginator
    {
        if (! is_null($this->file)) {
            try {
                $friend = Friend::whereUserId($this->queryString['uid'])->firstOrFail();
                app(PushMessageAction::class)->execute($this->file, $friend);
            } catch (ModelNotFoundException $e) {
                Notification::make()
                    ->title($e->getMessage())
                    ->danger()
                    ->send()
                    ->body('Failed to send file to '.$friend->name.'. line api only accept file type JPEG, PNG, MP4, MP3, M4A format!')
                    ->sendToDatabase(User::all());
            }
            $this->file = null;
        }

        $friend = Friend::whereUserId($this->queryString['uid'])->firstOrFail();

        $talk = Talk::whereSenderId($friend->id)
            ->whereSenderType(Friend::class)
            ->orWhere('receiver_id', $friend->id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->noOfMessage);

        $newTalks = $talk->where('read_at', null);

        foreach ($newTalks as $newTalk) {
            $newTalk->update([
                'read_at' => now(),
            ]);
        }

        return $talk;
    }

    public function friend()
    {
        $subQuery = DB::table('talks')
            ->select('sender_id', DB::raw('MAX(created_at) as latest_talk'))
            ->groupBy('sender_id');

        $friends = Friend::joinSub($subQuery, 'latest_talks', function ($join) {
            $join->on('friends.id', '=', 'latest_talks.sender_id');
        })
            ->join('talks', function ($join) {
                $join->on('friends.id', '=', 'talks.sender_id')
                    ->on('latest_talks.latest_talk', '=', 'talks.created_at');
            })
            ->orderBy('talks.created_at', 'desc')
            ->select('friends.*')
            ->paginate($this->noOfFriends);

        return $friends;
    }

    public function friendModel()
    {
        return Friend::class;
    }

    public function userModel()
    {
        return User::class;
    }

    public function sendMessage(): void
    {
        if (! is_null($this->file)) {
            $friend = Friend::whereUserId($this->queryString['uid'])->firstOrFail();
            app(PushMessageAction::class)->execute($this->file, $friend);
            $this->file = null;
        }
        if (! empty($this->message) && is_null($this->file)) {
            $friend = Friend::whereUserId($this->queryString['uid'])->firstOrFail();
            app(PushMessageAction::class)->execute($this->message, $friend);
            $this->message = '';
        }
    }

    public function onChangeMessage($message)
    {
        $this->message = $message;
    }

    public function parseDate($date)
    {
        $carbonDate = Carbon::parse($date);
        $now = Carbon::now();

        if ($carbonDate->isToday()) {
            $diffInMinutes = round($carbonDate->diffInMinutes($now));
            $diffInHours = round($carbonDate->diffInHours($now));

            if ($diffInMinutes < 1) {
                return 'now';
            } elseif ($diffInMinutes < 60) {
                return $diffInMinutes.' minutes ago';
            } else {
                return $diffInHours.' hours ago';
            }
        } elseif ($carbonDate->isYesterday()) {
            return 'yesterday';
        } else {
            return $carbonDate->format('Y-m-d H:i');
        }
    }

    public function addMessage()
    {
        $this->noOfMessage += 20;
    }

    public function addFriend()
    {
        $this->noOfFriends += 7;
    }

    public function markStatus()
    {
        return collect(FlagEnum::cases())
            ->mapWithKeys(fn (FlagEnum $target) => [$target->value => ucfirst(str_replace('_', ' ', $target->value))])
            ->toArray();
    }

    public function markColor($mark): string
    {
        if ($mark === FlagEnum::UNRESOLVED) {
            return 'warning';
        } elseif ($mark === FlagEnum::REQUIRES_ACTION) {
            return 'danger';
        } elseif ($mark === FlagEnum::ALREADY_RESOLVED) {
            return 'success';
        } else {
            return 'gray';
        }
    }

    public function updateMark()
    {
        $friend = Friend::whereUserId($this->queryString['uid'])->firstOrFail();
        $friend->update([
            'mark' => $this->mark,
        ]);
    }

    public function latestMessage(Friend $friend)
    {
        if ($friend) {
            $friend->sendBy()->latest();

            return $this->parseDate($friend->sendBy()->latest()->first()?->read_at);
        }
    }

    public function filter()
    {
        return isset($this->queryString['filter']) ? Friend::whereMark($this->queryString['filter'])->get() : $this->friend();
    }
}
