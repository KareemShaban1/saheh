<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Http\Requests\Backend\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Backend\Admin\UpdateAnnouncementRequest;
use Yajra\DataTables\Facades\DataTables;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('backend.dashboards.admin.pages.announcements.index');
    }

    public function data(){
        $query = Announcement::query();

        return DataTables::of($query)
        ->addColumn('action', function ($announcement) {
            return '<button class="btn btn-warning btn-sm" onclick="editAnnouncement(' . $announcement->id . ')">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteAnnouncement(' . $announcement->id . ')">
                    <i class="fa fa-trash"></i>
                </button>';
        })
            ->rawColumns(['action'])
            ->make(true);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAnnouncementRequest $request)
    {
        //
        $validatedData = $request->validated();
        $validatedData['is_active'] = $request->has('is_active') ? 1 : 0;
        $validatedData['send_notification'] = $request->has('send_notification') ? 1 : 0;
        Announcement::create($validatedData);

        if($request->ajax()){
            return response()->json([
                'success' => true,
                'message' => 'Announcement created successfully',
            ]);
        }

        return redirect()->route('admin.announcements.index')->with('toast_success', 'Announcement created successfully');
    }

  

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        //
    }
}
