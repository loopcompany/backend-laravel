<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserAddressRequest;
use App\Http\Requests\UpdateUserAddressRequest;
use App\Services\UserAddressService;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public function index(Request $request, UserAddressService $service)
    {
        $userId = $request->user()->id;
        $addresses = $service->getUserAddresses($userId);
        
        return response()->json(['success' => true, 'data' => $addresses]);
    }

    public function store(CreateUserAddressRequest $request, UserAddressService $service)
    {
        $userId = $request->user()->id;
         
        $address = $service->createAddress($request->validated(), $userId);
        
        return response()->json([
            'success' => true, 
            'message' => 'آدرس با موفقیت ایجاد شد.',
            'data' => $address
        ], 201);
    }

    public function show(int $id, Request $request, UserAddressService $service)
    {
        $userId = $request->user()->id;
        $address = $service->getAddressById($id, $userId);
        
        if (!$address) {
            return response()->json([
                'success' => false, 
                'message' => 'آدرس مورد نظر یافت نشد.'
            ], 404);
        }
        
        return response()->json(['success' => true, 'data' => $address]);
    }

    public function update(int $id, UpdateUserAddressRequest $request, UserAddressService $service)
    {
        $userId = $request->user()->id;
        $address = $service->updateAddress($id, $request->validated(), $userId);
        
        if (!$address) {
            return response()->json([
                'success' => false, 
                'message' => 'آدرس مورد نظر یافت نشد.'
            ], 404);
        }
        
        return response()->json([
            'success' => true, 
            'message' => 'آدرس با موفقیت به‌روزرسانی شد.',
            'data' => $address
        ]);
    }

    public function destroy(int $id, Request $request, UserAddressService $service)
    {
        $userId = $request->user()->id;
        $deleted = $service->deleteAddress($id, $userId);
        
        if (!$deleted) {
            return response()->json([
                'success' => false, 
                'message' => 'آدرس مورد نظر یافت نشد.'
            ], 404);
        }
        
        return response()->json([
            'success' => true, 
            'message' => 'آدرس با موفقیت حذف شد.'
        ]);
    }

    public function count(Request $request, UserAddressService $service)
    {
        $userId = $request->user()->id;
        $count = $service->getAddressesCount($userId);
        
        return response()->json([
            'success' => true, 
            'data' => ['count' => $count]
        ]);
    }
}