<?php

namespace App\Http\Livewire;

use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Categories extends Component
{
    use WithPagination, WithFileUploads, AuthorizesRequests;

    public $name, $image, $rowId, $UpdatedPhoto, $editImage, $editName, $updateMode, $confirming,$search;
    public $isModalOpen         = 0;
    public $isModelOpenCreate   = 0;
    public $sortField ='id';
    public $sortAsc = false; 

    public function render()
    {
        $this->authorize('isAdmin');
        //$this->users = User::paginate(10);
        $fields = ['name'];
        return view('category', [
            'category' => Category::search($fields,$this->search,true)
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10),
        ]);
    }

    //****** create category *******//
    public function create()
    {
        $this->resetCreateForm();
        $this->openModalPopoverCreate();
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
        $this->image        = '';
        $this->editName     = '';
        $this->editImage    = '';
        $this->UpdatedPhoto = '';
    }

    public function store()
    {
        $this->validate([
            'name'          => 'required',
            'image'         => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            if($this->image) {
                $imageName = $this->image->store("images/categories", 'public');
            }

            Category::create([
                'name'  => $this->name,
                'image' => $imageName,
            ]);
            session()->flash('message', 'Category created.');
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
            'rowId'         => 'required',
        ]);

        if ($this->rowId) {

            $category = Category::find($this->rowId);

            if($this->UpdatedPhoto) {
                $this->validate([
                    'UpdatedPhoto'  => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]);
                $imageName = $this->UpdatedPhoto->store("images/categories", 'public');
                $category->update([
                    'image'       => $imageName, //tmp/image
                ]);
            }

            $category->update([
                'name'        => $this->editName,
            ]);

            $this->updateMode = false;
            session()->flash('message', 'Category Updated Successfully.');
            $this->resetCreateForm();
            $this->closeModalPopover();
            $this->dispatchBrowserEvent('categoryUpdated');
        }
    }

    public function edit($id)
    {
        $this->updateMode     = true;
        $category             = Category::findOrFail($id);
        $this->rowId          = $id;
        $this->editName       = $category->name;
        $this->editImage      = $category->image;

        $this->openModalPopover();
    }

    public function delete($id)
    {
        Category::find($id)->delete();
        session()->flash('message', 'Category deleted.');
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
