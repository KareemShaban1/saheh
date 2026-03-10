<?php

namespace App\Services;

use App\Exceptions\InvalidScopeException;
use App\Http\Traits\ApiHelperTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BaseService
{
    use ApiHelperTrait;
    /**
     * Return response for failed operation
     *
     * @param string|null $message
     * @param array $errors
     * @param int $code
     * @return JsonResponse
     */
    public function error(string|null $message = 'Your Request Is Invalid', array $errors = [], int $code = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $response = [
            'code' => (string) $code,
            'status' => 'failed',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function handleException(\Exception $e, $message = null): JsonResponse
    {
        Log::error($e);

        $statusCode = $e instanceof ModelNotFoundException ? 404 : ($e instanceof InvalidScopeException ? $e->getCode() : 500);

        $message = $e instanceof ModelNotFoundException ? __('message.Resource not found') : ($e instanceof InvalidScopeException ? $e->getMessage() : $message);

        return $this->error($message, [], $statusCode);
    }

    protected function withPagination($query, $request)
    {
        $perPage = $request->per_page ?? 10;

        return $query->skip(($request->page - 1) * $perPage)
            ->take($perPage)
            ->paginate($perPage);

        // if ($request->has('paginate') && $request->paginate == 'false') {
        //     return $query->get();
        // } else {
        //     $perPage = $request->per_page ?? 10;

        //     return $query->skip(($request->page - 1) * $perPage)
        //         ->take($perPage)
        //         ->paginate($perPage);
        // }
    }

    protected function withTrashed($query, $request)
    {
        if ($request->has('with_trashed') && $request->with_trashed == 'true') {
            return $query->withTrashed();
        }
        return $query;
    }

    public function baseDestroy($class, $id)
    {
        return $this->baseBulkDelete($class, [$id]);
    }

    public function baseRestore($class, $id)
    {
        try {
            $model = $class::withTrashed()->findOrFail($id);

            $model->restore();

            return $model;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->handleException($e, __('message.Error happened while restoring organization'));
        }
    }

    public function baseForceDelete($class, $id)
    {
        return $this->baseBulkDelete($class, [$id]);
    }

    public function baseBulkDelete($class, $ids)
    {
        try {
            $trashedRecords = $class::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                $class::withTrashed()->whereIn('id', $trashedRecords->pluck('id'))
                    ->get()
                    ->each(function ($model) {
                        $model->forceDelete();
                    });
            }

            $nonTrashedIds = $class::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                $class::whereIn('id', $nonTrashedIds)->get()->each(function ($model) {
                    $model->delete();
                });
            }
            return $ids;
        } catch (\Exception $e) {

            Log::error('Error in Deleting ' . $class . ' ' . $e->getMessage());

            Schema::enableForeignKeyConstraints(); // Ensure foreign key checks are re-enabled even if an error occurs
            return $this->handleException($e, __("message.Error happened while deleting $class"));
        }
    }
}
