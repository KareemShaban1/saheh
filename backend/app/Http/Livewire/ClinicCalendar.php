<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Shared\Event;


class ClinicCalendar extends Component
{
    public $events = '';

    public function getevent()
    {
        $events = Event::select('id','title','date')->get();

        return  json_encode($events);
    }

    /**
    * Write code on Method
    *
    * @return response()
    */
    public function addevent($event)
    {
        $input['title'] = $event['title'];
        $input['date'] = $event['date'];
        $input['organization_id'] = auth()->user()->organization_id;
        $input['organization_type'] = auth()->user()->organization_type;
        Event::create($input);
    }

    /**
    * Write code on Method
    *
    * @return response()
    */
    public function eventDrop($event, $oldEvent)
    {
      $eventdata = Event::find($event['id']);
      $eventdata->date = $event['start'];
      $eventdata->save();
    }

    /**
    * Write code on Method
    *
    * @return response()
    */
    public function render()
    {
        $events = Event::select('id','title','date')->get();

        $this->events = json_encode($events);

        return view('backend.dashboards.clinic.livewire.calendar');
    }
}
