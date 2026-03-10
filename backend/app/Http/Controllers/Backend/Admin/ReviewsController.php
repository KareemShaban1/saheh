<?php

namespace App\Http\Controllers\Backend\Admin;

use Illuminate\Http\Request;
use App\Models\Shared\PatientReview;
use App\Http\Traits\AuthorizeCheck;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController;
use Yajra\DataTables\Facades\DataTables;

class ReviewsController extends BaseController
{
    use AuthorizeCheck;

    public function __construct()
    {
        $this->model = PatientReview::class;
        $this->viewPath = 'backend.dashboards.admin.pages.reviews';
        $this->routePrefix = 'reviews';
        $this->validationRules = [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ];
    }

    public function index()
    {
        $this->authorizeCheck('view-reviews');
        return view($this->viewPath . '.index');
    }

    public function data()
    {
        $this->authorizeCheck('view-reviews');
        $query = $this->model::with(['patient:id,name', 'doctor.user:id,name', 'organization']);

        return DataTables::of($query)
            ->addColumn('action', function ($item) {
                $btn = '<div class="d-flex gap-2">';

                $btn .= '<button onclick="editReview('.$item->id.')" class="btn btn-sm btn-info">
                            <i class="mdi mdi-square-edit-outline"></i>
                        </button>';

                $btn .= '<button onclick="deleteRecord('.$item->id.')" class="btn btn-sm btn-danger">
                            <i class="mdi mdi-delete"></i>
                        </button>';

                return $btn . '</div>';
            })
            ->addColumn('organization_name', function ($item) {
                return $item->organization ? $item->organization->name : 'N/A';
            })
            ->addColumn('doctor_name', function ($item) {
                return $item->doctor && $item->doctor->user ? $item->doctor->user->name : 'N/A';
            })
            ->addColumn('patient_name', function ($item) {
                return $item->patient ? $item->patient->name : 'N/A';
            })
            ->addColumn('is_active', function ($item) {
                $checked = $item->is_active === 1 ? 'checked' : '';
                $statusClass = $item->is_active === 1 ? 'text-success' : 'text-danger';

                return '<div class="d-flex align-items-center">
                    <label class="switch me-2">
                        <input type="checkbox" class="toggle-status" data-id="' . $item->id . '" ' . $checked . '>
                        <span class="slider round"></span>
                    </label>
                </div>';
            })
            ->editColumn('created_at', function($item) {
                return $item->created_at->format('Y-m-d H:i:s');
            })
            ->rawColumns(['action', 'is_active'])
            ->make(true);
    }

    public function show($id)
    {
        try {
            $review = $this->model::findOrFail($id);
            return response()->json($review);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.Review not found'),
                'errors' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate($this->validationRules);

        try {
            $data = $request->all();
            $data['changed_by'] = Auth::id();

            $item = $this->model::create($data);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.' . class_basename($this->model) . ' created successfully'),
                    'data' => $item
                ]);
            }

            return redirect()->route($this->routePrefix . '.index')
                ->with('success', __('messages.' . class_basename($this->model) . ' created successfully'));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.' . class_basename($this->model) . ' creation failed'),
                    'errors' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => __('messages.' . class_basename($this->model) . ' creation failed')])->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->validationRules);

        try {
            $review = $this->model::findOrFail($id);
            $data = $request->only(['rating', 'comment']);
            $data['changed_by'] = Auth::id();

            $review->update($data);

            return response()->json([
                'success' => true,
                'message' => __('messages.Review updated successfully'),
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.Review update failed'),
                'errors' => $e->getMessage()
            ], 422);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $review = $this->model::findOrFail($request->id);
            $review->is_active = $request->is_active;
            $review->changed_by = Auth::id();
            $review->save();

            return response()->json([
                'status' => 'success',
                'message' => __('messages.Review status updated successfully'),
                'newStatus' => $review->status === 1 ? 'Active' : 'Inactive',
                'statusClass' => $review->status === 1 ? 'text-success' : 'text-danger'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.Review status update failed'),
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $item = $this->model::findOrFail($id);
            $item->changed_by = Auth::id();
            $item->save();
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => __('messages.Review deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.Review deletion failed'),
                'errors' => $e->getMessage()
            ], 422);
        }
    }
}
