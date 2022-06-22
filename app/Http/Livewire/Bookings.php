<?php

namespace App\Http\Livewire;

use App\Models\Booking;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Bookings extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search,$bookingDetails = [],$from_utc,$to_utc;
    public $isViewModalOpen = 0;

    public $sortField ='bookings.when';
    public $sortAsc = false; 
    public function sortBy($field)
    {
        if($this->sortField === $field)
        {
            $this->sortAsc = ! $this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
    }
    public function render()
    {
        $this->authorize('isAdmin');
        $from_utc = !empty($this->from_utc)?$this->from_utc:date('Y-m-d',strtotime('now'));
        $to_utc = !empty($this->to_utc)?$this->to_utc:date('Y-m-d',strtotime('+2 months'));
        $booking = Booking::searchQuery($this->search)
                            ->join('users', 'users.id', '=', 'bookings.user_id')
                            ->join('courses', 'courses.id', '=', 'bookings.course_id')
                            ->join('users as instructor','instructor.id','=','courses.user_id')
                            ->where('bookings.when','>=',$from_utc)
                            ->where('bookings.when','<=',$to_utc)
                            ->select(
                                'bookings.*',
                                'users.first_name','users.last_name',
                                'courses.title',
                                'instructor.first_name as instructor_first_name','instructor.last_name as instructor_last_name','instructor.email as instructor_email'
                            )->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            //->orderBy('bookings.when', 'desc')
                            ->paginate(10);
        return view('bookings.crud', [
            'bookings' => $booking,
            'from_utc'=>$from_utc,
            'to_utc'=>$to_utc
        ]);
 
       /* return view('bookings.crud', [
            'bookings' => Booking::search($fields, $this->search, true)
            ->whereHas('course', function ($query) use ($inner_fields) { 
                $query->search($inner_fields, $this->search, true); 
            })
        ]);*/
    }
    public function view($id){
        $booking = Booking::find($id);
        $bookingDetails['course_title']     =   $booking->course->title;
        $bookingDetails['description']      =   $booking->course->description;
        $bookingDetails['image']            =   !empty($booking->course->image)?
                                                url($booking->course->image):
                                                url('/images/image_placeholder.jpg');
        $bookingDetails['instructor_name']  =   $booking->course->users->first_name.
                                                ' '.$booking->course->users->last_name;
        $bookingDetails['instructor_email'] =   $booking->course->users->email;
        $bookingDetails['when']             =   $booking->when;
        $bookingDetails['student_name']     =   $booking->users->first_name.
                                                ' '.$booking->users->last_name;
        $bookingDetails['student_email']    =   $booking->users->email;
        $bookingDetails['payment_method']   =   $booking->payment_method;
        $bookingDetails['transaction_id']   =   $booking->transaction_id;
        $this->bookingDetails = $bookingDetails;
        $this->openModalPopover();  
    }
    public function openModalPopover()
    {
        $this->isViewModalOpen = true;
    }

    public function closeModalPopover()
    {
        $this->isViewModalOpen = false;
    }

    private function resetCreateForm()
    {
        $this->user_id    = '';
        $this->first_name = '';
        $this->last_name  = '';
        $this->email      = '';
    }

    public function store()
    {
        $this->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
            //'email'      => 'required',
        ]);

        try {
            Booking::updateOrCreate(['id' => $this->user_id], [
                'first_name' => $this->first_name,
                'last_name'  => $this->last_name,
                //'email'      => $this->email,
                //'password'   => Hash::make('123456'),
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            session()->flash('message', $e->getMessage());
        }

        session()->flash('message', $this->user_id ? 'User updated.' : 'User created.');

        $this->closeModalPopover();
        $this->resetCreateForm();
    }

    public function edit($id)
    {
        $user             = Booking::findOrFail($id);
        $this->user_id    = $id;
        $this->first_name = $user->first_name;
        $this->last_name  = $user->last_name;
        $this->email      = $user->email;

        $this->openModalPopover();
    }

    public function delete($id)
    {
        Booking::find($id)->delete();
        session()->flash('message', 'User deleted.');
    }

}
