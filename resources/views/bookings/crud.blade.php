<x-slot name="header">
    <h2 class="text-center">Bookings</h2>
</x-slot>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" >
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-4 py-4" style="overflow:scroll">
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
            @if($isViewModalOpen)
                @include('bookings.view')
            @endif
            <div class="table-filter py-4 space-y-4">
                <div>
                    <div class="w-1/4">
                        <input type="text" placeholder="Search" wire:model="search">
                    </div>   
                    <div class="w-1/4">
                        <span> From Utc : </span>
                        <input type="date" placeholder="From" wire:model="from_utc"> 
                    </div> 
                    <div class="w-1/4">
                        <span> To Utc : </span>
                        <input type="date" placeholder="To" wire:model="to_utc"> 
                    </div>               
                </div>
            </div>


            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 w-20">
                            <a wire:click.prevent="sortBy('bookings.when')" role="button" href="#">
                              #  
                            </a>
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('courses.title')" role="button" href="#">
                                Course Title
                            </a>
                             
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('bookings.when')" role="button" href="#">
                                When
                            </a>
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('users.first_name')" role="button" href="#">
                                User
                            </a>
                        </th>
                        <th class="px-4 py-4">
                            <a wire:click.prevent="sortBy('users.email')" role="button" href="#">
                                Email
                            </a>
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('instructor.first_name')" role="button" href="#">
                                Instructor
                            </a>
                        </th>
                        <th class="px-4 py-4">
                            <a wire:click.prevent="sortBy('instructor.first_name')" role="button" href="#">
                                Instructor Email
                            </a>
                        </th>    
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('bookings.amount')" role="button" href="#">
                                Price
                            </a>
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('bookings.payment_method')" role="button" href="#">
                                Payment Method
                            </a>
                        </th>
                        <th class="px-4 py-2">
                            <a wire:click.prevent="sortBy('bookings.transaction_id')" role="button" href="#">
                                Transaction ID
                            </a>
                        </th>
                        <th class="px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $key => $booking)
                        <tr>
                            <td class="border px-4 py-2">{{ $bookings->firstItem() + $key }}</td>
                            <td class="border px-4 py-2">{{ $booking->title }}</td>
                            <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($booking->when)->format('M d Y g:i A') }}</td>
                            <td class="border px-4 py-2">{{ $booking->first_name.' '.$booking->last_name }}</td>
                            <td class="border px-4 py-4">{{ $booking->email }}</td>

                            <td class="border px-4 py-2">{{ $booking->instructor_first_name.' '.$booking->instructor_last_name }}</td>
                            <td class="border px-4 py-4">{{ $booking->instructor_email }}</td>

                            <td class="border px-4 py-2 text-right">{{ '$'.$booking->amount }}</td>
                            <td class="border px-4 py-2">{{ ($booking->payment_method == '0' ) ? "--" : $booking->payment_method }}</td>
                            <td class="border px-4 py-2">{{ $booking->transaction_id }}</td>
                            <td class="border px-4 py-2">
                                <button wire:click="view({{ $booking->id }})"
                                    class="bg-blue-500  text-white font-bold py-2 px-4 rounded">Show</button>
                               <!-- <button wire:click="delete({{ $booking->id }})"
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button> -->
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $bookings->links() }}
        </div>
    </div>
</div>
