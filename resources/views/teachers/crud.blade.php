<x-slot name="header">
    <h2 class="text-center">Instructors</h2>
</x-slot>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-4 py-4">
            @if(session()->has('message'))
                <div class="flex items-center bg-blue-500 text-white text-sm font-bold px-4 py-3 relative" role="alert" x-data="{show: true}" x-show="show">
                    <svg class="fill-current w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M12.432 0c1.34 0 2.01.912 2.01 1.957 0 1.305-1.164 2.512-2.679 2.512-1.269 0-2.009-.75-1.974-1.99C9.789 1.436 10.67 0 12.432 0zM8.309 20c-1.058 0-1.833-.652-1.093-3.524l1.214-5.092c.211-.814.246-1.141 0-1.141-.317 0-1.689.562-2.502 1.117l-.528-.88c2.572-2.186 5.531-3.467 6.801-3.467 1.057 0 1.233 1.273.705 3.23l-1.391 5.352c-.246.945-.141 1.271.106 1.271.317 0 1.357-.392 2.379-1.207l.6.814C12.098 19.02 9.365 20 8.309 20z"/></svg>
                    <p>{{ session('message') }}</p>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                        <svg class="fill-current h-6 w-6 text-white" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                    </span>
                </div>
            @endif
            <div></div>
            <!-- <button wire:click="create()"
                class="bg-green-700 text-white font-bold py-2 px-4 rounded my-3">Create Users</button> -->
            @if($isModalOpen)
                @include('livewire.create')
            @endif
            @if($isProfileModalOpen)
                @include('teachers.profile')
            @endif
            <div class="py-4 space-y-4">
                <div>
                    <div class="w-1/4">
                        <input type="text" placeholder="Search" wire:model="search">
                    </div>
                    
                </div>
            </div>
            <table class="table-auto w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 w-20">
                            <a wire:click.prevent="sortBy('id')" role="button" href="#">
                                #
                            </a>
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('first_name')" role="button" href="#">
                                Name
                            </a>
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('email')" role="button" href="#">
                                Email
                            </a>
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('phone')" role="button" href="#">
                                Phone
                            </a>
                        </th>
                        <th class="px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $key => $user)
                        <tr>
                            <td class="border px-4 py-2">{{ $users->firstItem() + $key }}</td>
                            <td class="border px-4 py-2">{{ $user->first_name.' '.$user->last_name }}</td>
                            <td class="border px-4 py-2">{{ $user->email }}</td>
                            <td class="border px-4 py-2">{{ $user->phone }}</td>
                            <td class="border px-4 py-2">
                                <button wire:click="edit({{ $user->id }})"
                                    class="bg-blue-500  text-white font-bold py-2 px-4 rounded">Edit</button>
                                <button wire:click="teacherProfile({{ $user->id }})"
                                    class="bg-blue-500 text-white font-bold py-2 px-4 rounded">View Details</button>
                                @if($confirming == $user->id)
                                    <button wire:click="delete({{ $user->id }})"
                                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Sure?</button>
                                @else
                                    <button wire:click="confirmDelete({{ $user->id }})"
                                        class="bg-gray-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
                                @endif
                                <!-- <button wire:click="delete({{ $user->id }})"
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button> -->
                            </td>
                        </tr>
                    @empty
                        <tr><td class="border px-4 py-2 text-center" colspan="5">
                            <span class="py-8 text-xl">No Results Found!</span>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>

            {{ $users->links() }}
        </div>
    </div>
</div>
