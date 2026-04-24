<x-filament-panels::page>
    <style>
        /* WebKit-based browsers */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }


    </style>

    <div style="display: flex;  box-shadow: 0 0 3px currentColor; border-radius: 20px; max-height: 720px">
        <div style=" width: 40%; opacity: 80%; box-shadow: 0.2px 0 currentColor; height: 100%; ">
            <div style="box-shadow: 0px 0.2px 0px; height: 70px; width: 100%; display: flex; align-items: center; padding: 10px; position: relative;">
                <x-filament::dropdown>
                    <x-slot name="trigger">
                        <x-heroicon-o-bars-3 class="w-6 h-6" style="color: currentColor; opacity: 40%; cursor: pointer; margin-right: 10px;"></x-heroicon-o-bars-3>
                        @if (isset($this->queryString['filter']))
                            <div>{{ucfirst(str_replace('_', ' ', $this->queryString['filter']))}}</div>
                        @else
                            <div>All</div>
                        @endif
                    </x-slot>

                    <x-filament::dropdown.list>
                            <x-filament::dropdown.list.item
                            :href="url('/admin/individual-talks?')"
                            tag="a">
                                {{ trans('All') }}
                            </x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>

                    <x-filament::dropdown.list>
                        @foreach ($this->markStatus() as $value => $label)
                            <x-filament::dropdown.list.item
                            :href="url('/admin/individual-talks?')  . 'filter=' . $value"
                            tag="a">
                                {{ $label }}
                            </x-filament::dropdown.list.item>
                        @endforeach
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>
            <x-filament::tabs label="Content tabs" style="display: flex; flex-direction: column; box-shadow: none; height: 650px;  overflow-y: scroll; border-radius: 20px; ">
                @foreach ($this->filter() as $friend)

                    <x-filament::tabs.item
                    :active="isset($this->queryString['uid']) && $friend->user_id === $this->queryString['uid']"
                    :href="url('/admin/individual-talks?') . 'uid=' . $friend->user_id"
                    tag="a"
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;"
                    >
                    <div style="display: flex; align-items: center;">
                        @if ($friend->profile_url)
                            <x-filament::avatar
                                src="{{$friend->profile_url}}"
                                alt="{{$friend->name}}"
                                size="lg"
                            />
                        @else
                            <x-filament::avatar
                            src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQGhmTe4FGFtGAgbIwVBxoD3FmED3E5EE99UGPItI0xnQ&s"
                            alt="{{$friend->name}}"
                            size="lg"
                            />
                        @endif
                        <div style="margin-left: 10px;">
                            <div style="margin-bottom: 3px">{{$friend->name}}</div>
                            <x-filament::badge size="xs" color="{{$this->markColor($friend->mark)}}"
                            >
                                {{ucfirst(str_replace('_', ' ', $friend->mark->value))}}
                            </x-filament::badge>
                            <div style="opacity: 50%; font-size: 12px; text-align: start">{{$this->latestMessage($friend)}}</div>
                        </div>
                    </div>
                    <div>
                        <x-slot name="badge">
                        {{$friend->sendBy->where('read_at', null)->count()}}
                        </x-slot>
                    </div>
                    </x-filament::tabs.item>
                @endforeach

                @if ($this->friend()->currentPage() < $this->friend()->lastPage())
                    <div style="width: 100%; display: flex; justify-content: center; margin-bottom: 20px">
                    <x-filament::icon-button
                        icon="heroicon-o-arrow-path"
                        wire:click="addFriend"
                        label="New label"
                        size="xl"
                    />
                    </div>
                @endif

            </x-filament::tabs>

        </div>
        <div style="width: 100%;">
            <div style="box-shadow: 0px 0.2px 0px; height: 70px; width: 100%; display: flex; align-items: center;  padding: 10px">
                @foreach ($this->friend() as $friend)
                    @if ($friend->user_id === isset($this->queryString['uid']))
                        @if ($friend->profile_url)
                            <span style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-right: 10px; display: flex; justify-content: center; align-items: center;">
                                <img src={{$friend->profile_url}} alt="Avatar" style="width: 100%; height: auto;">
                            </span>
                        @else
                            <span style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px; background-color: gray; display: flex; justify-content: center; align-items: center; font-size: 20px; color: white;">
                                {{ strtoupper(substr($friend->name, 0, 2)) }}
                            </span>
                        @endif
                        <span>
                            <h4 style="margin: 0;">{{$friend->name}}</h4>
                        </span>
                    @endif
                @endforeach
            </div>
            <div style="  border-radius: 10px; display: flex; flex-direction: column; justify-content: space-between">
                <!-- Chat messages -->
                <div wire:poll.keep-alive style="box-shadow: 0px 0.2px 0px; height: 500px; overflow-y: scroll; padding: 20px; display: flex; flex-direction: column-reverse">
                    @if (isset($this->queryString['uid']))
                        @foreach ($this->talk() as $item)
                            @if ($item->sender_type == $this->friendModel())
                                <div style="margin-bottom: 10px;">
                                    <div style="max-width: 400px; background-color: #dcf8c6; border-radius: 10px; padding: 10px; display: inline-block;">
                                        @if($item->message['type'] === 'text')
                                            <p class="bg-black" style="color:black; margin: 0; word-wrap: break-word;">{{$item->message['text']}}</p>
                                        @elseif ($item->message['type'] === 'file')
                                        <a href="{{$item->getFirstMediaUrl('talk')}}" download style="color:black; display: flex">
                                            <x-heroicon-s-document-arrow-down class="w-6 h-6"/>
                                            <span style="max-width: 350px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;">
                                                {{$item->message['fileName']}}
                                            </span>
                                            </a>
                                        @elseif ($item->message['type'] === 'image')
                                            <img class="bg-black" style="color:black; margin: 0; word-wrap: break-word;" src="{{$item->getFirstMediaUrl('talk')}}"/>
                                        @elseif ($item->message['type'] === 'video')
                                            <div class="video-player">
                                                <video controls>
                                                    <source src="{{$item->getFirstMediaUrl('talk')}}" type="video/mp4">
                                                    Your browser does not support the video element.
                                                </video>
                                            </div>
                                        @elseif ($item->message['type'] === 'audio')
                                            <div>
                                                <audio controls style="max-height: 30px">
                                                <source src="{{$item->getFirstMediaUrl('talk')}}" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                                </audio>
                                           </div>
                                        @elseif ($item->message['type'] === 'image')
                                            <img class="bg-black" style="color:black; margin: 0; word-wrap: break-word;" src="{{$item->getFirstMediaUrl('talk')}}"/>
                                        @endif
                                    </div>
                                    <span style="opacity: 50%; font-size: 12px">{{$this->parseDate($item->created_at)}}</span>
                                </div>
                            @endif

                            @if ($item->sender_type === $this->userModel() )
                                <div style="margin-bottom: 10px; text-align: right;">
                                    <span style="opacity: 50%; font-size: 12px">{{$this->parseDate($item->created_at)}}</span>
                                    <div style="max-width: 400px; background-color: #cfe7ff; border-radius: 10px; padding: 10px; display: inline-block;">
                                        @if ($item->message['type'] === 'text')
                                            <p style="color:black; margin: 0; word-wrap: break-word;">{{$item->message['text']}}</p>
                                        @endif
                                        @if ($item->message['type'] === 'image')
                                            <img class="bg-black" style="color:black; margin: 0; word-wrap: break-word;" src="{{$item->getFirstMediaUrl('talk')}}"/>
                                            @elseif ($item->message['type'] === 'video')
                                            <div class="video-player">
                                                <video controls>
                                                    <source src="{{$item->getFirstMediaUrl('talk')}}" type="video/mp4">
                                                    Your browser does not support the video element.
                                                </video>
                                            </div>
                                        @elseif ($item->message['type'] === 'audio')
                                            <div>
                                                <audio controls style="max-height: 30px">
                                                <source src="{{$item->getFirstMediaUrl('talk')}}" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                                </audio>
                                           </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                        @endforeach
                    @endif
                    @if (isset($this->queryString['uid']) && $this->talk()->currentPage() < $this->talk()->lastPage())
                        <div style="width: 100%; display: flex; justify-content: center; margin-bottom: 20px">
                           <x-filament::icon-button
                            icon="heroicon-o-arrow-path"
                            wire:click="addMessage"
                            label="New label"
                            size="xl"
                        />
                        </div>
                    @endif

                </div>
                <!-- End of chat messages -->

                <!-- Custom input and send button -->
                <div style="display: flex; flex-direction: column; align-items: center; margin-top: 20px;">
                    <div style="width: 100%; padding-inline: 10px">
                        <textarea wire:model='message' placeholder="Type your message..." style="flex: 1; resize: none;
                        padding: 10px; border-radius: 5px; border: none; width: 100%; background-color: transparent;"
                            @if(!isset($this->queryString['uid']))disabled @endif
                        >
                        </textarea>
                        <div wire:loading wire:target="file">
                            <x-filament::loading-indicator class="w-5 h-5" />
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; width: 100%; padding: 10px">
                        <label for="media">
                            <x-heroicon-o-paper-clip class="w-6 h-6" style="color: currentColor; opacity: 40%; cursor: pointer;"/>
                        </label>
                            <x-filament::input
                                id="media"
                                type="file"
                                wire:model="file"
                                style="display: none"
                            />
                        <x-filament::button wire:click="sendMessage()">
                            Send
                        </x-filament::button>
                    </div>
                </div>
                <!-- End of custom input and send button -->
            </div>
        </div>
        <div style="box-shadow: -0.2px 0 0 currentColor; text-align: center; width: 400px; height: 100%; padding: 20px; display: flex; flex-direction: column; align-items: center;">
            @foreach ($this->friend() as $friend)
                @if (isset($queryString['uid']) && $friend->user_id === $this->queryString['uid'])
                    @if ($friend->profile_url)
                        <x-filament::avatar
                            src="{{$friend->profile_url}}"
                            alt="{{$friend->name}}"
                            size="xl"
                        />
                    @else
                        <x-filament::avatar
                        src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQGhmTe4FGFtGAgbIwVBxoD3FmED3E5EE99UGPItI0xnQ&s"
                        alt="{{$friend->name}}"
                        size="xl"
                        />
                    @endif
                        <div style=" margin-top: 20px; font-size: 25px; font-weight: bold;">{{$friend->name}}</div>
                        <x-filament::fieldset>
                            <x-slot name="label">
                                Mark
                            </x-slot>
                            <x-filament::input.wrapper>
                                <x-filament::input.select wire:model="mark" wire:change="updateMark">
                                    @foreach ($this->markStatus() as $value => $label)
                                    <option value={{$value}}>{{$label}}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </x-filament::fieldset>
                @endif
            @endforeach
        </div>

    </div>
</x-filament-panels::page>
