<?php

namespace Modules\Clinic\Announcement\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Clinic\Announcement\Models\Announcement;
use Modules\Clinic\Announcement\Http\Requests\Backend\StoreAnnouncementRequest;
use Modules\Clinic\Announcement\Http\Requests\Backend\UpdateAnnouncementRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //

        return view('backend.dashboards.clinic.pages.announcements.index');
    }

    public function data()
    {
        $query = Announcement::where('organization_id', auth()->user()->organization_id)
            ->where('organization_type', auth()->user()->organization_type)
            ->get();

        return DataTables::of($query)
            ->addColumn('action', function ($announcement) {
                return '<button class="btn btn-warning btn-sm" onclick="editAnnouncement(' . $announcement->id . ')">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteAnnouncement(' . $announcement->id . ')">
                    <i class="fa fa-trash"></i>
                </button>';
            })
            ->addColumn('is_active', function ($announcement) {
                return $announcement->is_active ? __('Yes') : __('No');
            })
            ->addColumn('send_notification', function ($announcement) {
                return $announcement->send_notification ? __('Yes') : __('No');
            })

            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
        $validatedData['organization_id'] = auth()->user()->organization_id;
        $validatedData['organization_type'] = auth()->user()->organization_type;
        $announcement = Announcement::create($validatedData);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Announcement created successfully',
            ]);
        }

        return redirect()->route('clinic.announcements.index')->with('toast_success', 'Announcement created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        $announcement = Announcement::find($id);
        return response()->json($announcement);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnnouncementRequest $request, $id)
    {
        //
        $announcement = Announcement::find($id);
        $validatedData = $request->validated();
        $validatedData['is_active'] = $request->has('is_active') ? 1 : 0;
        $validatedData['send_notification'] = $request->has('send_notification') ? 1 : 0;
        $announcement->update($validatedData);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Announcement updated successfully',
            ]);
        }

        return redirect()->route('clinic.announcements.index')->with('toast_success', 'Announcement updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        //
        $announcement = Announcement::find($id);
        $announcement->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Announcement deleted successfully',
            ]);
        }

        return redirect()->route('clinic.announcements.index')->with('toast_success', 'Announcement deleted successfully');
    }
}
