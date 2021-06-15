<div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow" xmlns:x-transition="http://www.w3.org/1999/xhtml">
    <button @click.stop="sidebarOpen = true" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:bg-gray-100 focus:text-gray-600 md:hidden">
        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
        </svg>
    </button>
    <div class="flex-1 px-3 md:px-8 flex justify-between items-center">
        <span class="text-xl text-gray-900" data-ref="meta-title">@yield('meta_title')</span>
        <div class="flex items-center md:ml-6 md:mr-2">
            @if($multiple_contacts->count() > 1)
            <div class="relative inline-block text-left" x-data="{ open: false }">
                <div>
                    <span class="rounded shadow-sm">
                        <button x-on:click="open = !open" x-on:click.away="open = false" type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 px-4 py-2 bg-white text-sm leading-5 font-medium text-gray-700 hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150">
                            <span class="hidden md:block mr-1">{{ auth('contact')->user()->company->present()->name }}</span>
                            <svg class="md:-mr-1 md:ml-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                </div>
                <div class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg" x-show="open">
                    <div class="rounded bg-white shadow-xs">
                        <div class="py-1">
                            @foreach($multiple_contacts as $contact)
                                <a data-turbolinks="false" href="{{ route('client.switch_company', $contact->hashed_id) }}" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900">{{ $contact->company->present()->name() }} - {{ $contact->client->present()->name()}}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <div @click.away="open = false" class="ml-3 relative" x-data="{ open: false }">
                <div>
                    <button @click="open = !open" class="max-w-xs flex items-center text-sm rounded-full focus:outline-none focus:shadow-outline">
                        <img class="h-8 w-8 rounded-full" src="{{ auth()->user()->avatar() }}" alt="" />
                        <span class="ml-2">{{ auth()->user()->present()->name() }}</span>
                    </button>
                </div>
                <div x-show="open" style="display:none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg">
                    <div class="py-1 rounded-md bg-white shadow-xs">
                        <a href="{{ route('client.profile.edit', ['client_contact' => auth()->user()->hashed_id]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition ease-in-out duration-150">
                            {{ ctrans('texts.profile') }}
                        </a>

                        <a href="{{ route('client.logout') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition ease-in-out duration-150">
                            {{ ctrans('texts.logout') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
