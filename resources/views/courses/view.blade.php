<div class="fixed z-10 inset-0 overflow-y-auto ease-out duration-400">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>?
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <span class="mt-3 flex w-full rounded-md shadow-sm sm:mt-0 sm:w-auto">
                        <button wire:click="closeViewCourseModel()" type="button"
                            class="inline-flex justify-center w-full rounded-md border border-gray-300 px-4 py-2 bg-white text-base leading-6 font-bold text-gray-700 shadow-sm hover:text-gray-700 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150 sm:text-sm sm:leading-5" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                    </span>
                </div>
                
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4" style="width:500px; height:600px; overflow:scroll;">    
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Title:</label>
                                {{ $course['title'] }}
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Description:</label>
                                {{ $course['description'] }}
                        </div>
                        <div class="mb-4">
                        	<label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Course Image:</label>
                            <div class="">
                                <img src={{$course['image']}} alt="Girl in a jacket" width="150" height="150"> 
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Price To Student:</label>
                                {{ $course['price_to_student'] }}
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Private Session Available:</label>
                                {{ $course['private_session'] }}
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">When:</label>
                                @foreach($course['when'] as $when)
                                {{\Carbon\Carbon::parse($when->when)->format('M d Y g:i A')}}<br/>
                                @endforeach
                        </div>
                        <div class="mb-4">
                        	<label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Instructor Image:</label>
                            <div class="">
                                <img src={{$course['instructor_image']}} alt="Girl in a jacket" width="150" height="150"> 
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput2"
                                class="block text-gray-700 text-sm font-bold mb-2">Instructor Name:</label>
                                {{$course['instructor_name']}}
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Instructor Email:</label>
                                {{$course['instructor_email']}}
                        </div>
                        <div class="mb-4">
                            <label for="exampleFormControlInput1"
                                class="block text-gray-700 text-sm font-bold mb-2">Course Availability (for next 2 months):</label>
                            @if(empty($course['availability']))
                            No availability found!
                            @else
                            <ul>
                            	@foreach($course['availability'] as $availability)
                            	<li>
                            		{{\Carbon\Carbon::parse($availability)->format('M d Y g:i A')}}
                            	</li>
                            	@endforeach
							</ul>
							@endif
                        </div>
                        <!--div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <span class="mt-3 flex w-full rounded-md shadow-sm sm:mt-0 sm:w-auto">
                                <button wire:click="closeViewCourseModel()" type="button"
                                    class="inline-flex justify-center w-full rounded-md border border-gray-300 px-4 py-2 bg-white text-base leading-6 font-bold text-gray-700 shadow-sm hover:text-gray-700 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150 sm:text-sm sm:leading-5">
                                    Close
                                </button>
                            </span>
                        </div-->
                </div>
        </div>
    </div>
</div>
