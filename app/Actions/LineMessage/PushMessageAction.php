<?php

namespace App\Actions\LineMessage;

use App\Actions\LineMessagingRequest\BuildPushMessageRequestAction;
use App\Actions\Media\UploadMediaAction;
use App\Actions\Talk\CreateTalkAction;
use App\DataTransferObjects\TalkData;
use App\Enums\FlagEnum;
use App\Models\Friend;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Laravel\Facades\LINEMessagingApi;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PushMessageAction
{
    public function __construct(
        protected BuildPushMessageRequestAction $buildPushMessageRequestAction,
        protected CreateTalkAction $createTalkAction,
        protected UploadMediaAction $uploadMediaAction,
        private PushMessageRequest $push
    ) {
    }

    public function execute(string|TemporaryUploadedFile $message, Friend $friend): Talk
    {
        $user = auth()->user();

        $type = 'text';

        if (! is_string($message)) {
            $type = $this->getFileExtensionType($message->getFileName());
            if (! $type || $type === 'unknown') {
                throw new ModelNotFoundException('File Type is Unkown');
            }
        }

        $talk = $this->createTalkAction->execute(TalkData::fromArray([
            'sender_id' => $user?->id,
            'sender_type' => User::class,
            'receiver_id' => $friend->id,
            'receiver_type' => Friend::class,
            'message' => [
                'type' => $type,
            ],
            'flag' => FlagEnum::ADMIN,
        ]));

        if (is_string($message)) {
            $this->push = $this->buildPushMessageRequestAction->execute($friend->user_id, $message, $type);
        } else {
            $media = $this->uploadMediaAction->execute($talk, $message->getRealPath());
            $this->push = $this->buildPushMessageRequestAction->execute($friend->user_id, $media, $type);
        }

        $talk->update([
            'message' => $this->push->getMessages()[0],
        ]);

        $response = LINEMessagingApi::pushMessage($this->push);

        if (! $response) {
            throw new ModelNotFoundException('message not go through');
        }

        return $talk;
    }

    protected function getFileExtensionType($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'png':
                return 'image';
            case 'mp4':
                return 'video';
            case 'mp3':
            case 'm4a':
                return 'audio';
            default:
                return 'unknown'; // Handle unsupported file types
        }
    }
}
