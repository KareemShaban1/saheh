<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Http\Requests\StorespecialtyRequest;
use App\Http\Requests\UpdatespecialtyRequest;
use Yajra\DataTables\Facades\DataTables;

class SpecialtyController extends BaseController
{
    public function __construct()
    {
        $this->model = Specialty::class;
        $this->viewPath = 'backend.dashboards.admin.pages.specialties';
        $this->routePrefix = 'specialties';
        $this->validationRules = [
            'name_en' => 'required|string|max:255|unique:specialties,name_en',
            'name_ar' => 'required|string|max:255|unique:specialties,name_ar',
            'description' => 'nullable|string'


        ];
    }


    public function data()
    {
        $query = $this->model::query();

        return DataTables::of($query)
            ->addColumn('action', function ($item) {

                $btn = '<div class="d-flex gap-2">';

                // if (auth()->user()->can('update specialty')) {
                    $btn .= '<a href="javascript:void(0);" onclick="editspecialty('.$item->id.', \''.$item->name_en.'\', \''.$item->name_ar.'\', \''.$item->description.'\')"
                            class="btn btn-sm btn-info">
                                <i class="mdi mdi-square-edit-outline"></i>
                            </a>';
                // }

                // if (auth()->user()->can('delete specialty')) {
                    $btn .= '<a href="javascript:void(0);" onclick="delete('.$item->id.', \'specialties\')"
                            class="btn btn-sm btn-danger">
                                <i class="mdi mdi-delete"></i>
                            </a>';
                // }

               return $btn . '</div>';
            })
            ->editColumn('created_at', function($item) {
                return $item->created_at->format('Y-m-d H:i:s');
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    protected function getUpdateValidationRules($id)
    {
        return [
            'name_en' => 'required|string|max:255|unique:specialties,name_en,'. $id,
            'name_ar' => 'required|string|max:255|unique:specialties,name_ar,'. $id,
            'description' => 'nullable|string'
        ];
    }
}
