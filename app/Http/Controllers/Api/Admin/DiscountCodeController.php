<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DiscountCodeController extends Controller
{
    public function index()
    {
        return response()->json(DiscountCode::orderBy('created_at', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper($request->input('code'))]);
        }

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $code = DiscountCode::create($data);

        return response()->json(['message' => 'Discount code created successfully', 'discount_code' => $code], 201);
    }

    public function show(DiscountCode $discountCode)
    {
        return response()->json($discountCode);
    }

    public function update(Request $request, DiscountCode $discountCode)
    {
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper($request->input('code'))]);
        }

        $validator = Validator::make($request->all(), $this->rules($discountCode->id));

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $discountCode->update($data);

        return response()->json(['message' => 'Discount code updated successfully', 'discount_code' => $discountCode]);
    }

    public function destroy(DiscountCode $discountCode)
    {
        $discountCode->delete();

        return response()->json(['message' => 'Discount code deleted successfully']);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('discount_codes', 'code')->ignore($ignoreId)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ];
    }
}
