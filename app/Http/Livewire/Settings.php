<?php

namespace App\Http\Livewire;

use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Settings extends Component
{
    use WithPagination, WithFileUploads, AuthorizesRequests;

    public $name, $value, $rowId, $editName, $editValue, $updateMode, $confirming;
    public $isModalOpen         = 0;
    public $isModelOpenCreate   = 0;

    public function render()
    {
        $this->authorize('isAdmin');
        //$this->users = User::paginate(10);
        return view('settings.crud', [
            'settings' => Setting::paginate(10),
        ]);
    }

    public function confirmDelete($id)
    {
        $this->confirming = $id;
    }

    //****** create category *******//
    public function create()
    {
        $this->resetCreateForm();
        $this->openModalPopoverCreate();
    }

    public function openModalPopover()
    {
        $this->isModalOpen = true;
    }

    public function closeModalPopover()
    {
        $this->isModalOpen = false;
    }

    /******/
    public function openModalPopoverCreate()
    {
        $this->isModelOpenCreate = true;
    }

    public function closeModalPopoverCreate()
    {
        $this->isModelOpenCreate = false;
    }

    private function resetCreateForm()
    {
        $this->rowId        = '';
        $this->name         = '';
        $this->value        = '';
        $this->editName     = '';
        $this->editValue    = '';
    }

    public function store()
    {
        $this->validate([
            'name'          => 'required',
            'value'         => 'required',
        ]);

        try {
            Setting::create([
                'name'  => $this->name,
                'value' => $this->value,
            ]);
            session()->flash('message', 'Settings created.');
        } catch (\Exception $e) {
            //dd($e->getMessage());
            session()->flash('message', $e->getMessage());
        }

        

        $this->closeModalPopoverCreate();
        $this->resetCreateForm();
    }

    public function update()
    {
        $this->validate([
            'editName'      => 'required',
            'editValue'     => 'required',
            'rowId'         => 'required',
        ]);

        if ($this->rowId) {

            $setting = Setting::find($this->rowId);

            $setting->update([
                'name'        => $this->editName,
                'value'       => $this->editValue,
            ]);

            $this->updateMode = false;
            session()->flash('message', 'Settings Updated Successfully.');
            $this->resetCreateForm();
            $this->closeModalPopover();
            $this->dispatchBrowserEvent('settingUpdated');
        }
    }

    public function edit($id)
    {
        $this->updateMode     = true;
        $category             = Setting::findOrFail($id);
        $this->rowId          = $id;
        $this->editName       = $category->name;
        $this->editValue      = $category->value;

        $this->openModalPopover();
    }

    public function delete($id)
    {
        Setting::find($id)->delete();
        session()->flash('message', 'Category deleted.');
    }

}
