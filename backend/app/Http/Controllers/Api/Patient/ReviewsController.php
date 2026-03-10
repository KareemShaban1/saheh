<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\PatientOrganization;
use App\Models\Shared\PatientReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewsController extends Controller
{
    use ApiResponseTrait;

    /**
     * List patient reviews.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $patient = Auth::guard('patient_api')->user();
            $reviews = PatientReview::query()
                ->where('patient_id', $patient->id)
                ->latest('id')
                ->get();

            return $this->apiResponse($reviews, 'Reviews fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->apiResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Create a new review
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'organization_type' => ['required', Rule::in(['App\Models\Clinic', 'App\Models\MedicalLaboratory', 'App\Models\RadiologyCenter'])],
                'organization_id' => 'required|integer',
                'doctor_id' => 'nullable|integer|exists:doctors,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->returnJSON(null, 'Reservation created successfully.', true);
            }

            $patient = Auth::user();
            $organizationType = $request->organization_type;
            $organizationId = $request->organization_id;
            $doctorId = $request->doctor_id;

            // Check if patient is assigned to the organization
            $isAssigned = PatientOrganization::where('patient_id', $patient->id)
                ->where('organization_type',  $organizationType)
                ->where('organization_id', $organizationId)
                ->where('assigned', true)
                ->exists();

            if (!$isAssigned) {
                return $this->apiResponse(null, 'You are not assigned to this organization', 403);
            }

            // If doctor_id is provided and organization is clinic, verify doctor belongs to clinic
            if ($doctorId && $organizationType === Clinic::class) {
                $doctor = Doctor::where('id', $doctorId)
                    ->where('clinic_id', $organizationId)
                    ->first();

                if (!$doctor) {
                    return $this->apiResponse(null, 'Doctor does not belong to this clinic', 422);
                }

                // Check if patient is assigned to this doctor
                $isAssignedToDoctor = PatientOrganization::where('patient_id', $patient->id)
                    ->where('organization_type', $organizationType)
                    ->where('organization_id', $organizationId)
                    ->where('doctor_id', $doctorId)
                    ->where('assigned', true)
                    ->exists();

                if (!$isAssignedToDoctor) {
                    return $this->apiResponse(null, 'You are not assigned to this doctor', 403);
                }
            }

            // Check if review already exists
            $existingReview = PatientReview::where('patient_id', $patient->id)
                ->where(function ($query) use ($organizationType, $organizationId, $doctorId) {
                    if ($doctorId) {
                        $query->where('doctor_id', $doctorId);
                    }
                    $query->where('organization_type', $organizationType)
                        ->where('organization_id', $organizationId);
                })
                ->first();

            if ($existingReview) {
                return $this->apiResponse(null, 'You have already submitted a review for this entity', 422);
            }

            // Create the review
            $review = PatientReview::create([
                'organization_type' => $organizationType,
                'organization_id' => $organizationId,
                'doctor_id' => $doctorId,
                'patient_id' => $patient->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return $this->apiResponse($review, 'Review created successfully', 201);
        } catch (\Exception $e) {
            return $this->apiResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Update an existing review
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(null, $validator->errors(), 422);
            }

            $patient = Auth::guard('patient_api')->user();
            $review = PatientReview::findOrFail($id);

            // Check if patient has permission to update the review
            if ($review->patient_id !== $patient->id) {
                return $this->apiResponse(null, 'You do not have permission to update this review', 403);
            }

            $review->update([
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return $this->apiResponse($review, 'Review updated successfully', 200);
        } catch (\Exception $e) {
            return $this->apiResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Delete a review
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $patient = Auth::guard('patient_api')->user();
            $review = PatientReview::findOrFail($id);

            // Check if patient has permission to delete the review
            if ($review->patient_id !== $patient->id) {
                return $this->apiResponse(null, 'You do not have permission to delete this review', 403);
            }

            $review->delete();

            return $this->apiResponse(null, 'Review deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->apiResponse(null, $e->getMessage(), 500);
        }
    }
}
