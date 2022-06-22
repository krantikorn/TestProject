<div class="fixed z-10 inset-0 overflow-y-auto ease-out duration-400" >
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>?
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            role="dialog" aria-modal="true" aria-labelledby="modal-headline" >
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <span class="mt-3 flex w-full rounded-md shadow-sm sm:mt-0 sm:w-auto">
                        <button wire:click="closeModalProfile()" type="button"
                            class="inline-flex justify-center w-full rounded-md border border-gray-300 px-4 py-2 bg-white text-base leading-6 font-bold text-gray-700 shadow-sm hover:text-gray-700 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150 sm:text-sm sm:leading-5" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </span>
                </div>
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4" style="width:500px; height:600px; overflow:scroll;"> 
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">	
                    	<div class="mb-4">
                        	<div class=""><img src={{$profile_pic}} alt="Girl in a jacket" width="150" height="150">
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">First Name</label>
                            	{{ $first_name }}
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Last Name</label>
                                {{ $last_name }}
                          </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput2"
                                class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                            	{{$email}}
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Phone:</label>
                            	{{$phone}}
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Joined At:</label>
                            	{{date('F,d,Y',strtotime($joined_at))}}
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Courses :-</label>
                </div>
                @if(! $courses->isEmpty())
                <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 w-20">#</th>
                        <th class="px-4 py-2">Image</th>
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Instructor Name</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Price To Student</th>
                        <th class="px-4 py-2">Created At</th>
                        <!-- <th class="px-4 py-2">Action</th> -->
                    </tr>
                </thead>
                <tbody>

                    @foreach($courses as $key => $course)
                        <tr>
                            <td class="border px-4 py-2">{{ $key }}</td>
                           <td class="border px-4 py-2"><img src="{{ $course->image}}" onerror="this.src='images/image_placeholder.jpg'" class="mCS_img_loaded" width="50"/></td>
                            <td class="border px-4 py-2">{{ $course->title }}</td>
                            <td class="border px-4 py-2">{{ $course->users->first_name.' '.$course->users->last_name }}</td>
                            <td class="border px-4 py-2">{{ $course->users->email }}</td>
                            <td class="border px-4 py-2">{{ $course->price_to_student }}</td>
                            <td class="border px-4 py-2">{{\Carbon\Carbon::parse($course->created_at)->format('M d Y g:i A')}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            	No Courses
            @endif
            <!-- div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <span class="mt-3 flex w-full rounded-md shadow-sm sm:mt-0 sm:w-auto">
                    <button wire:click="closeModalProfile()" type="button"
                        class="inline-flex justify-center w-full rounded-md border border-gray-300 px-4 py-2 bg-white text-base leading-6 font-bold text-gray-700 shadow-sm hover:text-gray-700 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150 sm:text-sm sm:leading-5">
                        Close
                    </button>
                </span>
            </div -->
        </div>
        </div>
    </div>
</div>
