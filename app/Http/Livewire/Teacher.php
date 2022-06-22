<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Teacher extends Component
{
    use WithPagination, AuthorizesRequests;
    public $name, $first_name, $last_name,$joined_at, $email,$courses, $phone, $user_id, $confirming, $search, $fields;
    public $isModalOpen = 0;
    public $isProfileModalOpen = false;
    public $sortField ='id';
    public $sortAsc = false; 

    public function render()
    {
        $fields = ['first_name', 'last_name', 'email', 'phone'];
        $this->authorize('isAdmin');
        return view('teachers.crud', [
            'users' => User::search($fields, $this->search, true)->teachers()
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10),
        ]);
    }

    // User Profile
    public function teacherProfile($id){
        $user                   = User::findOrFail($id);
        $course_id              = $user->course()->pluck('id');
        $this->courses          = Course::where('user_id',$user->id)->get();
        $this->user_id          = $id;
        $this->first_name       = $user->first_name;
        $this->last_name        = $user->last_name;
        $this->email            = $user->email;
        $this->phone            = $user->phone;
        $this->profile_pic      = !empty($user->profile_photo_path)?
                                  url('/images/profile'.$profile_pic):
                                  url('/images/image_placeholder.jpg');
        $this->joined_at        = $user->created_at;
        $this->openModalProfile();
    }
    public function openModalProfile()
    {
        $this->isProfileModalOpen = true;
    }
    public function closeModalProfile()
    {
        $this->isProfileModalOpen = false;
    }

    //****** create users *******//
    public function create()
    {
        $this->resetCreateForm();
        $this->openModalPopover();
    }

    public function confirmDelete($id)
    {
        $this->confirming = $id;
    }

    public function openModalPopover()
    {
        $this->isModalOpen = true;
    }

    public function closeModalPopover()
    {
        $this->isModalOpen = false;
    }

    private function resetCreateForm()
    {
        $this->user_id    = '';
        $this->first_name = '';
        $this->last_name  = '';
        $this->email      = '';
        $this->phone      = '';
    }

    public function store()
    {
        $this->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => 'required',
            'phone'      => 'required|digits:10',
        ]);

        try {
            User::updateOrCreate(['id' => $this->user_id], [
                'first_name' => $this->first_name,
                'last_name'  => $this->last_name,
                'email'      => $this->email,
                'phone'      => $this->phone,
                //'password'   => Hash::make('123456'),
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            session()->flash('message', $e->getMessage());
        }

        session()->flash('message', $this->user_id ? 'Teacher updated.' : 'Teacher created.');

        $this->closeModalPopover();
        $this->resetCreateForm();
    }

    public function edit($id)
    {
        $user             = User::findOrFail($id);
        $this->user_id    = $id;
        $this->first_name = $user->first_name;
        $this->last_name  = $user->last_name;
        $this->email      = $user->email;
        $this->phone      = $user->phone;

        $this->openModalPopover();
    }

    public function delete($id)
    {
        User::find($id)->delete();
        session()->flash('message', 'Teacher deleted.');
    }
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

}
