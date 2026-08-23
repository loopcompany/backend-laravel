<?php

namespace App\Repositories;

use App\Models\UserAddress;

class UserAddressRepository
{
    public function getAllByUserId(int $userId)
    {
        return UserAddress::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById(int $id)
    {
        return UserAddress::find($id);
    }

    public function findByIdAndUserId(int $id, int $userId)
    {
        return UserAddress::where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data)
    {
        return UserAddress::create($data);
    }

    public function update(UserAddress $address, array $data)
    {
        $address->update($data);
        return $address->fresh();
    }

    public function delete(UserAddress $address)
    {
        return $address->delete();
    }

    public function countByUserId(int $userId)
    {
        return UserAddress::where('user_id', $userId)->count();
    }
}