<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Course as ModelCourse;
use DateTime;
use When\When;
class Course extends Component
{
	public $isViewModalOpen = false ,$course=[],$search='';
    public $sortField ='title';
    public $sortAsc = false;
    public function render(){
    	$fields = ['title','price_to_student','email','first_name','last_name'];
    	$search = $this->search;
    	/*$courses	= 	ModelCourse::search($fields,$search,true)
    			->orWhereHas('users' , function($query) use ($search){
        $query->where(\DB::raw("concat(first_name, ' ',last_name)"), 'like', '%'.$search.'%');
        $query->orWhere('email', 'like', '%'.$search.'%');
    })->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10); */

    	$courses	= 	ModelCourse::search($fields,$search,true)->orWhere(\DB::raw("concat(first_name, ' ',last_name)"), 'like', '%'.$search.'%')
		    			->join('users','users.id','=','courses.user_id')->select('courses.*','users.email','users.first_name','users.last_name')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10);

    	return view('courses.courses', [
            'courses' => $courses,
        ]);

    }

    public function view($id){
    	$image_placeholder = url('/images/image_placeholder.jpg');
    	$courseData	 = ModelCourse::find($id);
    	$course['title']  			= $courseData->title;
    	$course['description']  	= $courseData->description;
    	$course['image']  			= !empty($courseData->image)?
                                                url($courseData->image):
                                                $image_placeholder;
    	$course['price_to_student'] = $courseData->price_to_student;
    	$course['private_session']  = $courseData->private_session == 0?'No':'Yes';
    	$course['instructor_image'] = !empty($courseData->users->image)?
                                                url('/images/profile'.$courseData->users->image):
                                                $image_placeholder;
    	$course['instructor_name']  = $courseData->users->first_name .' '. $courseData->users->last_name;
    	$course['instructor_email'] = $courseData->users->email;
    	$course['when']				= $courseData->avail;
    	$course['availability']     = $this->getAvailability($courseData->avail);
    	$this->course = $course;
    	$this->openViewCourseModel();
    }

    public function getAvailability($availability){
    	$avalibilty = [];
    	foreach($availability as $courseAvail){
    		$endDate	= date('Y-m-d', strtotime('+2 months'));
    		if(!empty($courseAvail->end_date)){
                if(date('Y-m-d', strtotime($courseAvail->end_date)) < $endDate){
                        $endDate  = $courseAvail->end_date;
                }
            }
            $endDate = date('Ymd', strtotime($endDate)).'T'.'235959';
            if($courseAvail->rule == 'FREQ=NO REPEAT'){
	    		if(strtotime($courseAvail->when) >= strtotime('now') && strtotime($courseAvail->when) <= strtotime('+2 months')){
	    			$avalibilty[] = $courseAvail->when;
	    		}
            }else{
				if($courseAvail->rule == 'FREQ=WEEKLY'){    
	                // get day in String 'SU,MO....'        
	                $day  = date('l',strtotime($courseAvail->when));
	                $day  = strtoupper(substr($day,0,2));
	                $rule = $courseAvail->rule.';BYDAY='.$day.';UNTIL='.$endDate;
	            }else{
	                $rule  =  $courseAvail->rule.';UNTIL='.$endDate;
	            }
	            if(strtotime($courseAvail->when) >= strtotime('now')){
	            	$start_date = $courseAvail->when;
	            }else{
	            	$start_date = date('Y-m-d',strtotime('now')).' '.date('H:i:s',strtotime($courseAvail->when));
	            }
				$r = new When();
		        $r->RFC5545_COMPLIANT = When::IGNORE;
		        $r->startDate(new DateTime($start_date))
		        ->rrule($rule)
		        //->exclusions($exclusion)
		          ->generateOccurrences();
		        foreach($r->occurrences as $occurrences){
		            $avalibilty[]	=   $occurrences->format('Y-m-d H:i:s');
		        }
            }
	    }
	    return $avalibilty;
    }
    public function openViewCourseModel(){
        $this->isViewModalOpen = true;
    }

    public function closeViewCourseModel(){
        $this->isViewModalOpen = false;
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
